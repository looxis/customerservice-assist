<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisException;
use App\Analysis\AnalysisPanel;
use App\Analysis\AnalysisProblem;
use App\Analysis\AnalysisRequest;
use App\Analysis\AnalysisStore;
use App\Analysis\CaseAnalyzer;
use App\Analysis\ContextVariant;
use App\Analysis\QuestionNumber;
use App\Analysis\QuestionTicket;
use App\Http\Requests\AskQuestionRequest;
use App\Http\Requests\FollowUpQuestionRequest;
use App\Knowledge\KnowledgeSelector;
use App\ProductForge\ProductForgeClient;
use App\ProductForge\ProductResolver;
use App\Staff\StaffDirectory;
use App\Staff\TestMode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A question to the knowledge base without a ticket (PROJ-40): the text takes the place of the ticket,
 * everything else is the analysis of a ticket.
 */
class QuestionController extends Controller
{
    public function create(Request $request, KnowledgeSelector $selector, AnalysisStore $store, StaffDirectory $staff, ProductForgeClient $forge): View
    {
        $name = $staff->current($request);

        return view('questions.create', [
            'form' => [
                'groups' => $selector->customerGroups(),
                'suggestedGroup' => '',
                'groupSource' => null,
                'products' => $selector->products(),
                'suggestedProducts' => [],
                'productReasons' => [],
                'mentionedProducts' => [],
                'forge' => ['enabled' => $forge->isConfigured(), 'candidates' => [], 'selected' => [], 'notices' => []],
            ],
            'recent' => $name === null ? [] : $store->recentQuestions($name, (int) config('analysis.question_history')),
        ]);
    }

    /**
     * Ask a new question and show the proposal.
     */
    public function store(AskQuestionRequest $request, CaseAnalyzer $analyzer, KnowledgeSelector $selector, StaffDirectory $staff, ProductResolver $productResolver): RedirectResponse
    {
        set_time_limit((int) config('analysis.timeout') * 2 + 30);

        $number = QuestionNumber::generate();
        $text = trim((string) $request->validated('text'));
        $replyWanted = $request->boolean('antwortentwurf');
        $products = array_values($request->validated('produkte', []));
        $package = $productResolver->resolve($request->forgeSelection(), null);
        $unknownProduct = $request->unknownProduct();

        if ($unknownProduct === null && $products === [] && ! $request->boolean('kein_produktbezug')) {
            $unknownProduct = $package->displayName();
        }

        try {
            $id = $analyzer->analyze(new AnalysisRequest(
                ticket: QuestionTicket::make($number, $text),
                staffName: (string) $staff->current($request),
                customerGroup: $selector->customerGroup($request->validated('kundengruppe')),
                products: $products,
                orders: [],
                manualOrder: [],
                employeeContext: '',
                variant: ContextVariant::FullThread,
                summary: null,
                formInput: ['kundengruppe' => $request->validated('kundengruppe'), 'produkte' => $products, 'kein_produktbezug' => $request->boolean('kein_produktbezug'), 'produkt_ohne_wissen' => $request->unknownProduct(), 'forge_produkte' => array_column($request->forgeSelection(), 'id'), 'forge_sku' => array_filter(array_column($request->forgeSelection(), 'sku', 'id')), 'antwortentwurf' => $replyWanted],
                questionText: $text,
                replyWanted: $replyWanted,
                unknownProduct: $unknownProduct,
                productPackage: $package,
            ));
        } catch (AnalysisException $exception) {
            return redirect()->route('questions.create')->withInput()
                ->with('question_error', $exception->problem->message())
                ->with('question_retry', $exception->problem !== AnalysisProblem::Misconfigured);
        }

        return redirect()->to(route('questions.show', ['number' => $number, 'analyse' => $id]).'#ergebnis');
    }

    /**
     * The proposal of a question; a version can be chosen by its id.
     */
    public function show(string $number, Request $request, AnalysisPanel $panel, AnalysisStore $store, StaffDirectory $staff, TestMode $testMode): View
    {
        $analysis = $request->query('analyse');
        $page = $panel->forQuestion($number, is_string($analysis) ? $analysis : null);

        abort_if($page === null, 404);
        abort_unless($testMode->isAvailable($request) || $store->questionBelongsTo($number, (string) $staff->current($request)), 403);

        return view('questions.show', [...$page, 'number' => $number, 'admin' => $testMode->isAvailable($request)]);
    }

    /**
     * A follow-up or the result of a clarification: a new version of the proposal with everything so far.
     */
    public function followUp(string $number, FollowUpQuestionRequest $request, CaseAnalyzer $analyzer, KnowledgeSelector $selector, StaffDirectory $staff, ProductResolver $productResolver): RedirectResponse
    {
        set_time_limit((int) config('analysis.timeout') * 2 + 30);

        $parent = (array) $request->parentAnalysis();
        $by = (string) $staff->current($request);
        $text = (string) ($parent['question']['text'] ?? '');
        $inputs = (array) ($parent['inputs'] ?? []);
        $replyWanted = $request->replyWanted();
        $products = array_values((array) ($inputs['produkte'] ?? []));
        $selection = array_map(fn (mixed $id): array => ['id' => (int) $id, 'sku' => is_string($inputs['forge_sku'][$id] ?? null) ? $inputs['forge_sku'][$id] : null], array_values((array) ($inputs['forge_produkte'] ?? [])));
        $package = $productResolver->resolve($selection, (array) ($parent['product_data'] ?? []) ?: null);
        $typed = is_string($inputs['produkt_ohne_wissen'] ?? null) && trim($inputs['produkt_ohne_wissen']) !== '' ? trim($inputs['produkt_ohne_wissen']) : null;

        try {
            $id = $analyzer->analyze(new AnalysisRequest(
                ticket: QuestionTicket::make($number, $text),
                staffName: $by,
                customerGroup: $selector->customerGroup((string) ($inputs['kundengruppe'] ?? 'unclear')) ?? $selector->customerGroup('unclear'),
                products: $products,
                orders: [],
                manualOrder: [],
                employeeContext: '',
                variant: ContextVariant::FullThread,
                summary: null,
                formInput: [...$inputs, 'antwortentwurf' => $replyWanted],
                decisions: $request->decisions($by),
                questionText: $text,
                replyWanted: $replyWanted,
                unknownProduct: $typed ?? ($products === [] && empty($inputs['kein_produktbezug']) ? $package->displayName() : null),
                productPackage: $package,
            ));
        } catch (AnalysisException $exception) {
            return redirect()->to(route('questions.show', ['number' => $number, 'analyse' => $request->input('klaerung_analyse')]).'#nachfrage')->withInput()
                ->with('question_error', $exception->problem->message())
                ->with('question_retry', $exception->problem !== AnalysisProblem::Misconfigured);
        }

        return redirect()->to(route('questions.show', ['number' => $number, 'analyse' => $id]).'#ergebnis');
    }
}
