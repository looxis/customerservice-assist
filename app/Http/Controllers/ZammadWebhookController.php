<?php

namespace App\Http\Controllers;

use App\Http\Requests\ZammadWebhookRequest;
use App\Jobs\SyncZammadTicket;
use App\Models\ZammadWebhookDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ZammadWebhookController extends Controller
{
    /**
     * Note the delivery and leave the work to the job. A repeated delivery is
     * acknowledged without a second job, so Zammad stops retrying it.
     */
    public function __invoke(ZammadWebhookRequest $request): JsonResponse
    {
        $ticketId = (int) $request->validated('ticket_id');
        $trigger = $request->header('X-Zammad-Trigger');

        $isNew = ZammadWebhookDelivery::query()->insertOrIgnore([
            'delivery_id' => mb_substr((string) $request->header('X-Zammad-Delivery') ?: (string) Str::uuid(), 0, 100),
            'trigger_name' => is_string($trigger) ? mb_substr($trigger, 0, 120) : null,
            'zammad_ticket_id' => $ticketId,
            'status' => ZammadWebhookDelivery::RECEIVED,
            'received_at' => now(),
        ]) === 1;

        if (! $isNew) {
            return response()->json(['status' => 'duplicate']);
        }

        SyncZammadTicket::dispatch($ticketId);

        return response()->json(['status' => 'accepted'], 202);
    }
}
