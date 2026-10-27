<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisStore;
use App\Analysis\ReplyDelivery;
use App\Dhl\DhlClient;
use App\Dhl\DhlException;
use App\Dhl\DhlProblem;
use App\Eocs\EocsClient;
use App\Eocs\EocsException;
use App\Eocs\OrderLookup;
use App\Http\Requests\CreateReturnLabelRequest;
use App\Http\Requests\DhlReturnSearchRequest;
use App\Http\Requests\SendReturnLabelRequest;
use App\Models\ReturnLabel;
use App\Orders\OrderNumber;
use App\Orders\OrderNumberDetector;
use App\Returns\ReturnInstructions;
use App\Returns\ReturnLabelInput;
use App\Returns\ReturnLabelService;
use App\Staff\StaffDirectory;
use App\Zammad\ArticleKind;
use App\Zammad\OutgoingAttachment;
use App\Zammad\OutgoingReply;
use App\Zammad\OutgoingTicket;
use App\Zammad\ReplyException;
use App\Zammad\ReplyProblem;
use App\Zammad\ReplyTarget;
use App\Zammad\Ticket;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use App\Zammad\ZammadReplySender;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * The page "DHL Retoure" (PROJ-64): find the order by ticket or order number, check the address, order the
 * return label at DHL, download it or send it to the customer.
 */
class DhlReturnController extends Controller
{
    /**
     * The search, server-rendered: the orders of a ticket or one order, with the address as EOCS has it.
     */
    public function index(DhlReturnSearchRequest $request, ZammadClient $zammad, EocsClient $eocs, OrderNumberDetector $detector, AnalysisStore $store, StaffDirectory $staff, DhlClient $dhl): View
    {
        $query = trim((string) $request->validated('suche', ''));
        $view = [
            'query' => $query,
            'ticket' => null,
            'lookups' => [],
            'problem' => null,
            'labels' => collect(),
            'seenArticle' => 0,
            'configured' => $dhl->isConfigured(),
            'hasToken' => $staff->zammadToken($staff->current($request)) !== null,
            'signature' => ($name = $staff->current($request)) === null ? '' : app(ReplyDelivery::class)->signature($name),
            'returnGroup' => (string) config('services.zammad.return_group'),
            'receiver' => null,
        ];

        if ($query === '') {
            return view('dhl-return.index', $view);
        }

        $ticketPattern = '/^\s*(?:(ticket)\s*)?#?\s*(\d{1,20})\s*$/iu';
        $isTicket = preg_match($ticketPattern, $query, $match) === 1;
        $order = $detector->normalize($query);
        $numbers = [];

        if ($order !== null && ! ($isTicket && ($match[1] !== '' || str_starts_with(ltrim($query), '#')))) {
            $numbers = [$order];
        } elseif ($isTicket) {
            try {
                $ticket = $zammad->ticket($match[2]);
            } catch (ZammadException $exception) {
                return view('dhl-return.index', [...$view, 'problem' => $exception->problem->message($match[2])]);
            }

            $view['ticket'] = $ticket;
            $view['seenArticle'] = $this->newestPublicArticle($ticket);
            $numbers = $this->ticketOrders($ticket, $detector, $store);
        } else {
            return view('dhl-return.index', [...$view, 'problem' => 'Bitte eine Ticketnummer oder eine Bestellnummer eingeben.']);
        }

        $view['receiver'] = $dhl->receiverLine();

        try {
            $view['lookups'] = $eocs->lookup($numbers);
        } catch (EocsException $exception) {
            $view['problem'] = $exception->problem->message();
        }

        $orderNumbers = collect($view['lookups'])->flatMap(fn (OrderLookup $lookup): array => array_map(fn ($found): string => $found->externalNumber, $lookup->orders))->all();
        $ticketNumber = $view['ticket']?->number;

        $view['labels'] = ReturnLabel::query()
            ->where(fn ($labels) => $labels->whereIn('order_number', $orderNumbers)->when($ticketNumber !== null, fn ($inner) => $inner->orWhere('ticket_number', $ticketNumber)))
            ->latest()
            ->limit(20)
            ->get();

        return view('dhl-return.index', $view);
    }

    /**
     * The delivery address of one order, fetched when the employee opens the label option on a ticket page: the
     * address is not part of the ticket page itself.
     */
    public function address(Request $request, EocsClient $eocs, OrderNumberDetector $detector): JsonResponse
    {
        $number = $detector->normalize((string) $request->query('bestellung'));

        if ($number === null) {
            return response()->json(['message' => 'Die Bestellnummer ist ungültig.'], 422);
        }

        try {
            $order = collect($eocs->lookup([$number]))->flatMap(fn (OrderLookup $lookup): array => $lookup->orders)->first();
        } catch (EocsException $exception) {
            return response()->json(['message' => $exception->problem->message()], 502);
        }

        $address = $order?->shippingAddress;

        return response()->json([
            'found' => $address !== null,
            'address' => $address === null ? null : [
                'name' => $address->name(),
                'street' => $address->street,
                'house_number' => $address->streetNumber,
                'postal_code' => $address->postalCode,
                'city' => $address->city,
                'country' => $address->countryCode !== '' ? $address->countryCode : 'DE',
            ],
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    /**
     * Orders the label (or returns the one that exists for the same request).
     */
    public function store(CreateReturnLabelRequest $request, ReturnLabelService $labels, StaffDirectory $staff): JsonResponse
    {
        $name = $staff->current($request);

        if ($name === null) {
            return response()->json(['message' => 'Bitte zuerst deinen Namen wählen.'], 409);
        }

        $data = $request->validated();

        try {
            $label = $labels->create(new ReturnLabelInput(
                $data['ticket_number'] ?? null,
                $data['order_number'],
                (int) $data['eocs_order_id'],
                $data['name'],
                $data['street'],
                $data['house_number'],
                $data['postal_code'],
                $data['city'],
            ), $name, $request->boolean('additional'));
        } catch (DhlException $exception) {
            return response()->json([
                'message' => $exception->problem->message(),
                'detail' => $exception->detail,
                'code' => $exception->problem->value,
                'shipment_no' => $exception->shipmentNo,
            ], $exception->problem === DhlProblem::Rejected ? 422 : 502);
        }

        return response()->json($label->payload(! $label->wasRecentlyCreated));
    }

    /**
     * The PDF exactly as DHL delivered it. A label that belongs to a ticket is only handed out with that ticket's number.
     */
    public function download(string $label, Request $request, ReturnLabelService $labels): Response
    {
        $label = ReturnLabel::query()->findOrFail($label);

        abort_if($label->ticket_number !== null && (string) $request->query('ticket') !== $label->ticket_number, 404);

        $pdf = $labels->pdf($label);

        abort_if($pdf === null, 404, 'Die Datei ist nicht mehr vorhanden.');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.ReturnInstructions::filename($label->order_number).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Sends the label with the text, as a reply to a ticket or as a new ticket, in the name of the employee. Only on click.
     */
    public function send(string $label, SendReturnLabelRequest $request, ReturnLabelService $labels, StaffDirectory $staff, ZammadClient $zammad, ZammadReplySender $sender): JsonResponse
    {
        $label = ReturnLabel::query()->findOrFail($label);
        $name = $staff->current($request);

        if ($name === null) {
            return response()->json(['message' => 'Bitte zuerst deinen Namen wählen.'], 409);
        }

        $token = $staff->zammadToken($name);

        if ($token === null) {
            return response()->json(['message' => ReplyProblem::NoToken->message($name), 'code' => 'no_token'], 409);
        }

        $pdf = $labels->pdf($label);

        if ($pdf === null) {
            return response()->json(['message' => 'Zu diesem Label gibt es keine Datei mehr. Bitte ein neues Label erzeugen.'], 409);
        }

        if ($label->sent_at !== null) {
            return response()->json(['message' => 'Dieses Label wurde bereits gesendet.', 'code' => 'sent'], 409);
        }

        $lock = Cache::lock('return-label-send.'.$label->id, 120);

        if (! $lock->get()) {
            return response()->json(['message' => 'Der Versand läuft gerade. Bitte kurz warten.', 'code' => 'busy'], 409);
        }

        try {
            return $this->deliver($label->fresh(), $pdf, $request, $name, $token, $zammad, $sender);
        } finally {
            $lock->release();
        }
    }

    private function deliver(ReturnLabel $label, string $pdf, SendReturnLabelRequest $request, string $name, string $token, ZammadClient $zammad, ZammadReplySender $sender): JsonResponse
    {
        if ($label->sent_at !== null) {
            return response()->json(['message' => 'Dieses Label wurde bereits gesendet.', 'code' => 'sent'], 409);
        }

        $body = trim((string) $request->validated('text'));
        $signature = trim((string) $request->validated('signature', ''));
        $body = $signature === '' ? $body : $body."\n\n".$signature;
        $after = $request->validated('after');
        $state = $after === 'close' ? 'closed' : 'pending reminder';
        $until = $after === 'close' ? null : ReplyDelivery::reminderDate((int) $request->validated('days'));
        $file = [new OutgoingAttachment(ReturnInstructions::filename($label->order_number), 'application/pdf', $pdf)];
        $asNew = $request->validated('mode') === 'new';
        $ticket = null;
        $target = null;

        if (! $asNew) {
            $ticket = $this->replyTicket((string) $request->validated('ticket_number'), $zammad);

            if ($ticket instanceof JsonResponse) {
                return $ticket;
            }
        }

        // An earlier attempt with an unclear outcome: look into Zammad before anything is sent again.
        if ($label->send_started_at !== null) {
            if ($label->send_started_at->diffInSeconds(now()) < 90) {
                return response()->json(['message' => 'Der Versand läuft gerade. Bitte kurz warten.', 'code' => 'busy'], 409);
            }

            if (! $asNew && $label->send_started_target === $ticket->number) {
                try {
                    $earlier = $sender->sentSince($token, $ticket->id, CarbonImmutable::instance($label->send_started_at));
                } catch (ReplyException $exception) {
                    return response()->json(['message' => $exception->problem->message($name), 'code' => $exception->problem->value], 502);
                }

                if ($earlier !== null) {
                    $label->update(['sent_at' => now(), 'sent_by' => $name, 'sent_ticket_number' => $ticket->number, 'sent_as_new_ticket' => false]);

                    return response()->json(['message' => 'Die Nachricht war bereits in Zammad angekommen. Sie wurde nicht noch einmal gesendet.', 'code' => 'sent'], 409);
                }
            } elseif (! $request->boolean('confirm_resend')) {
                return response()->json([
                    'message' => $asNew || $label->send_started_target === 'new'
                        ? 'Der letzte Versand hatte einen unklaren Ausgang. Bitte in Zammad nachsehen, ob schon ein Ticket „'.ReturnInstructions::subject($label->order_number).'“ mit dem Label existiert. Nur wenn nicht, erneut senden.'
                        : 'Der letzte Versand an Ticket '.$label->send_started_target.' hatte einen unklaren Ausgang. Bitte dort nachsehen, ob die Nachricht angekommen ist. Nur wenn nicht, erneut senden.',
                    'code' => 'unclear_resend',
                ], 409);
            }
        }

        if (! $asNew) {
            $newer = ReplyTarget::newerThan($ticket, $request->integer('seen_article') > 0 ? $request->integer('seen_article') : null);

            if ($newer !== [] && ! $request->boolean('confirm_newer')) {
                $latest = $newer[array_key_last($newer)];

                return response()->json([
                    'message' => 'Seit dem Laden der Seite gibt es eine neue Nachricht von '.$latest->senderName.' ('.$latest->createdAt->format('d.m.Y, H:i').' Uhr). Bitte lesen und entscheiden, ob das Label noch passt.',
                    'code' => 'newer',
                ], 409);
            }

            $target = ReplyTarget::for($ticket);

            if ($target === null) {
                return response()->json(['message' => 'Zu diesem Ticket gibt es keine eindeutige Antwortadresse. Bitte „Neues Ticket erstellen“ wählen oder in Zammad antworten.'], 409);
            }
        }

        $label->update(['send_started_at' => now(), 'send_started_target' => $asNew ? 'new' : $ticket->number]);

        try {
            if ($asNew) {
                $created = $sender->createTicket($token, new OutgoingTicket((string) config('services.zammad.return_group'), (string) $request->validated('email'), ReturnInstructions::subject($label->order_number), $body, $state, $until, $file));
                $ticketNumber = $created->number;
                $url = $created->url;
            } else {
                $sender->send($token, new OutgoingReply($ticket->id, $target->to, $target->subject, $body, $target->inReplyTo, $state, $until, $file));
                $ticketNumber = $ticket->number;
                $url = $ticket->zammadUrl;
            }
        } catch (ReplyException $exception) {
            if ($exception->problem->notSent()) {
                $label->update(['send_started_at' => null, 'send_started_target' => null]);
            }

            return response()->json([
                'message' => $asNew && ! $exception->problem->notSent()
                    ? 'Zammad hat nicht rechtzeitig geantwortet. Es ist unklar, ob das Ticket angelegt wurde. Bitte in Zammad nach „'.ReturnInstructions::subject($label->order_number).'“ suchen, bevor erneut gesendet wird.'
                    : $exception->problem->message($name),
                'code' => $exception->problem->value,
            ], 502);
        }

        $label->update(['sent_at' => now(), 'sent_by' => $name, 'sent_ticket_number' => $ticketNumber, 'sent_as_new_ticket' => $asNew, 'send_started_at' => null, 'send_started_target' => null]);

        return response()->json(['sent' => true, 'by' => $name, 'ticket_number' => $ticketNumber, 'ticket_url' => $url, 'new_ticket' => $asNew]);
    }

    private function replyTicket(string $number, ZammadClient $zammad): Ticket|JsonResponse
    {
        try {
            return $zammad->ticket($number);
        } catch (ZammadException $exception) {
            return response()->json(['message' => $exception->problem->message($number).' Das Label wurde nicht gesendet.'], 502);
        }
    }

    /**
     * @return list<OrderNumber>
     */
    private function ticketOrders(Ticket $ticket, OrderNumberDetector $detector, AnalysisStore $store): array
    {
        $orders = [];

        foreach ([...array_map(fn (OrderNumber $order): string => $order->value, array_filter($detector->detect($ticket), fn (OrderNumber $order): bool => ! $order->isEocsId())), ...$store->rememberedOrders($ticket->number)] as $value) {
            $order = $detector->normalize($value);

            if ($order !== null) {
                $orders[$order->value] ??= $order;
            }
        }

        return array_slice(array_values($orders), 0, OrderNumberDetector::MAX_SUGGESTIONS);
    }

    private function newestPublicArticle(Ticket $ticket): int
    {
        $ids = array_map(fn ($article): int => $article->id, array_filter($ticket->articles, fn ($article): bool => $article->kind !== ArticleKind::Internal));

        return $ids === [] ? 0 : max($ids);
    }
}
