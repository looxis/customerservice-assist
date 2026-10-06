<?php

namespace App\Analysis;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Exceptions\FailoverableException;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

/**
 * One structured call to the configured provider, with the app's error
 * kinds and a log line without any content.
 */
class LanguageModel
{
    /**
     * @return array{data: array<string, mixed>, input_tokens: int, output_tokens: int, duration_ms: int}
     *
     * @throws AnalysisException
     */
    public function ask(Agent $agent, string $input, string $model, string $purpose, string $ticketNumber): array
    {
        if (blank(config('ai.providers.'.config('analysis.provider').'.key'))) {
            throw new AnalysisException(AnalysisProblem::Misconfigured);
        }

        $started = hrtime(true);

        try {
            /** @var AgentResponse $response */
            $response = $agent->prompt($input, provider: config('analysis.provider'), model: $model, timeout: (int) config('analysis.timeout'));
        } catch (Throwable $exception) {
            $problem = $this->problem($exception);
            $this->log($purpose, $ticketNumber, $model, $started, $problem->value, $exception::class);

            throw new AnalysisException($problem, $exception::class);
        }

        if (! $response instanceof StructuredAgentResponse || ! is_array($response->structured) || $response->structured === []) {
            $this->log($purpose, $ticketNumber, $model, $started, AnalysisProblem::InvalidResult->value);

            throw new AnalysisException(AnalysisProblem::InvalidResult);
        }

        $duration = $this->log($purpose, $ticketNumber, $model, $started, 'ok', usage: [$response->usage->inputTokens, $response->usage->outputTokens]);

        return [
            'data' => $response->structured,
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
            'duration_ms' => $duration,
        ];
    }

    private function problem(Throwable $exception): AnalysisProblem
    {
        $status = $exception instanceof RequestException ? $exception->response->status() : null;

        return match (true) {
            $status === 401, $status === 403 => AnalysisProblem::Misconfigured,
            $exception instanceof ConnectionException, $exception instanceof FailoverableException, $status !== null => AnalysisProblem::Unavailable,
            $exception instanceof \JsonException, $exception instanceof \InvalidArgumentException => AnalysisProblem::InvalidResult,
            default => AnalysisProblem::Unavailable,
        };
    }

    /**
     * @param  array{0: int, 1: int}|null  $usage
     */
    private function log(string $purpose, string $ticketNumber, string $model, int $started, string $outcome, ?string $exception = null, ?array $usage = null): int
    {
        $duration = (int) ((hrtime(true) - $started) / 1_000_000);

        Log::info('Language model call', array_filter([
            'purpose' => $purpose,
            'ticket' => $ticketNumber,
            'model' => $model,
            'outcome' => $outcome,
            'duration_ms' => $duration,
            'input_tokens' => $usage[0] ?? null,
            'output_tokens' => $usage[1] ?? null,
            'exception' => $exception,
        ], fn (mixed $value): bool => $value !== null));

        return $duration;
    }
}
