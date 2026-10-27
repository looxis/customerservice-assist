<?php

namespace App\Returns;

use App\Dhl\DhlClient;
use App\Dhl\DhlException;
use App\Dhl\DhlProblem;
use App\Dhl\ReturnRequest;
use App\Models\ReturnLabel;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Orders DHL return labels and keeps them (PROJ-64). A label is ordered once per confirmed request: the same
 * request again returns the label that exists, and a timeout never leads to a second order on its own. The
 * PDF is stored before anything else happens with it, because DHL does not hand it out a second time.
 */
class ReturnLabelService
{
    private const string DISK = 'return-labels';

    public function __construct(private readonly DhlClient $dhl) {}

    /**
     * @param  bool  $additional  The employee wants one more label for the same request (second parcel, lost label).
     *
     * @throws DhlException
     */
    public function create(ReturnLabelInput $input, string $staff, bool $additional = false): ReturnLabel
    {
        $lock = Cache::lock('return-label.'.$input->fingerprint(), 60);

        try {
            $lock->block(5);
        } catch (LockTimeoutException) {
            throw new DhlException(DhlProblem::RateLimited);
        }

        try {
            if (! $additional) {
                $existing = $this->existing($input);

                if ($existing !== null) {
                    return $existing;
                }
            }

            return $this->order($input, $staff);
        } finally {
            $lock->release();
        }
    }

    /**
     * The PDF of a label, unchanged as DHL delivered it.
     */
    public function pdf(ReturnLabel $label): ?string
    {
        if (! $label->hasFile()) {
            return null;
        }

        $content = Storage::disk(self::DISK)->get((string) $label->file_path);

        return is_string($content) ? $content : null;
    }

    /**
     * Deletes the files of labels older than the retention period; the shipment numbers stay.
     *
     * @return array{files: int}
     */
    public function purge(): array
    {
        $days = max(1, (int) config('services.dhl.retention_days', 30));
        $files = 0;

        ReturnLabel::query()->whereNotNull('file_path')->where('created_at', '<', now()->subDays($days))->each(function (ReturnLabel $label) use (&$files): void {
            Storage::disk(self::DISK)->delete((string) $label->file_path);
            $label->update(['file_path' => null]);
            $files++;
        });

        return ['files' => $files];
    }

    /**
     * A label of the same request that is still valid or whose outcome is unclear: never ordered again silently.
     */
    private function existing(ReturnLabelInput $input): ?ReturnLabel
    {
        return ReturnLabel::query()
            ->where('fingerprint', $input->fingerprint())
            ->where(fn ($query) => $query
                ->whereIn('status', [ReturnLabel::CREATED, ReturnLabel::OUTCOME_UNKNOWN, ReturnLabel::INVALID])
                ->orWhere(fn ($creating) => $creating->where('status', ReturnLabel::CREATING)->where('created_at', '>', now()->subMinutes(2))))
            ->latest()
            ->first();
    }

    /**
     * @throws DhlException
     */
    private function order(ReturnLabelInput $input, string $staff): ReturnLabel
    {
        $label = ReturnLabel::create([
            'ticket_number' => $input->ticketNumber,
            'order_number' => $input->orderNumber,
            'eocs_order_id' => $input->eocsOrderId,
            'status' => ReturnLabel::CREATING,
            'fingerprint' => $input->fingerprint(),
            'created_by' => $staff,
        ]);

        try {
            $result = $this->dhl->createReturn(new ReturnRequest($input->name, $input->street, $input->houseNumber, $input->postalCode, $input->city, $input->customerReference(), $input->shipmentReference()));
        } catch (DhlException $exception) {
            $label->update([
                'status' => match (true) {
                    $exception->problem === DhlProblem::InvalidLabel => ReturnLabel::INVALID,
                    $exception->problem->notCreated() => ReturnLabel::FAILED,
                    default => ReturnLabel::OUTCOME_UNKNOWN,
                },
                'shipment_no' => $exception->shipmentNo,
                'error' => mb_substr($exception->problem->message(), 0, 300),
            ]);

            throw $exception;
        }

        $path = $label->id.'.pdf';

        if (! Storage::disk(self::DISK)->put($path, $result->pdf)) {
            $label->update(['status' => ReturnLabel::INVALID, 'shipment_no' => $result->shipmentNo, 'error' => 'Die Label-Datei konnte nicht gespeichert werden.']);

            throw new DhlException(DhlProblem::InvalidLabel, shipmentNo: $result->shipmentNo);
        }

        $label->update(['status' => ReturnLabel::CREATED, 'shipment_no' => $result->shipmentNo, 'file_path' => $path, 'file_sha256' => hash('sha256', $result->pdf)]);

        return $label;
    }
}
