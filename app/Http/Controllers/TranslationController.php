<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisException;
use App\Analysis\Translator;
use App\Http\Requests\BackTranslateReplyRequest;
use App\Http\Requests\TranslateTicketRequest;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use App\Zammad\ZammadClient;
use App\Zammad\ZammadException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class TranslationController extends Controller
{
    /**
     * Translate the messages of a ticket that are not in German (PROJ-28);
     * an admin can have a single message translated anew.
     */
    public function store(string $number, TranslateTicketRequest $request, ZammadClient $zammad, Translator $translator, StaffDirectory $staff, TestMode $testMode): RedirectResponse
    {
        set_time_limit((int) config('analysis.timeout') * 3 + 30);

        $only = $request->validated('nachricht');
        abort_if($only !== null && ! $testMode->isAvailable($request), 403);

        $back = redirect()->to(route('tickets.show', array_filter([
            'number' => $number,
            'bestellungen' => array_values(array_filter((array) $request->input('bestellungen', []), 'is_string')),
            'stand' => $request->input('stand'),
        ])).'#verlauf');

        try {
            $ticket = $testMode->rewind($zammad->ticket($number), $request, $request->input('stand'))['ticket'];
            $counts = $translator->translate($ticket, (string) $staff->current($request), $only === null ? null : (int) $only);
        } catch (ZammadException $exception) {
            return $back->with('translation_error', $exception->problem->message($number));
        } catch (AnalysisException $exception) {
            return $back->with('translation_error', 'Die Übersetzung ist gerade nicht möglich. Bitte erneut versuchen.');
        }

        return $counts['failed'] > 0
            ? $back->with('translation_error', $counts['failed'] === 1 ? 'Eine Nachricht konnte nicht übersetzt werden. Bitte erneut versuchen.' : "{$counts['failed']} Nachrichten konnten nicht übersetzt werden. Bitte erneut versuchen.")
            : $back->with('translation_done', $counts['translated']);
    }

    /**
     * Translate the current reply draft back into German for checking.
     */
    public function backTranslate(string $number, string $analysis, BackTranslateReplyRequest $request, Translator $translator, StaffDirectory $staff): JsonResponse
    {
        try {
            $result = $translator->backTranslate($number, $analysis, $request->validated('text'), (string) $staff->current($request));
        } catch (AnalysisException) {
            return response()->json(['message' => 'Die Übersetzung ist gerade nicht möglich. Bitte erneut versuchen.'], 503);
        }

        if ($result === null) {
            return response()->json(['message' => 'Diese Analyse ist nicht mehr verfügbar.'], 404);
        }

        return response()->json(['text' => $result['text']]);
    }
}
