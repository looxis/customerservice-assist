<?php

namespace App\Labeling;

use App\Zammad\ReplyException;
use App\Zammad\ReplyProblem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sets and removes tags of Zammad tickets in the name of the admin whose token is used (PROJ-78). Tags are the only
 * thing the ticket tagging writes. The token is only part of the request header.
 */
class LabelTagWriter
{
    /**
     * @throws ReplyException
     */
    public function add(string $token, int $ticketId, string $tag): void
    {
        $this->send($token, 'tags/add', $ticketId, $tag);
    }

    /**
     * @throws ReplyException
     */
    public function remove(string $token, int $ticketId, string $tag): void
    {
        $this->send($token, 'tags/remove', $ticketId, $tag);
    }

    /**
     * @throws ReplyException
     */
    private function send(string $token, string $path, int $ticketId, string $tag): void
    {
        $config = config('services.zammad');

        if (blank($config['url'])) {
            throw new ReplyException(ReplyProblem::Unavailable);
        }

        $timeout = max(1, (int) $config['timeout']);

        try {
            $response = Http::baseUrl(rtrim((string) $config['url'], '/').'/api/v1/')
                ->withHeaders(['Authorization' => 'Token token='.$token])
                ->acceptJson()
                ->connectTimeout($timeout)
                ->timeout($timeout)
                ->post($path, ['object' => 'Ticket', 'o_id' => $ticketId, 'item' => $tag]);
        } catch (ConnectionException) {
            Log::warning('Zammad tag: connection problem.');

            throw new ReplyException(ReplyProblem::Unavailable);
        }

        $this->guard($response);
    }

    /**
     * @throws ReplyException
     */
    private function guard(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        Log::warning('Zammad tag: status '.$response->status().'.');

        throw new ReplyException(match (true) {
            $response->status() === 401 => ReplyProblem::TokenRejected,
            $response->status() === 403 => ReplyProblem::Forbidden,
            $response->status() === 404 => ReplyProblem::NotFound,
            in_array($response->status(), [400, 422], true) => ReplyProblem::Rejected,
            default => ReplyProblem::Unavailable,
        });
    }
}
