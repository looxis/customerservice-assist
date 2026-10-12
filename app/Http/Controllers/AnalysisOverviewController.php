<?php

namespace App\Http\Controllers;

use App\Analysis\AnalysisOverview;
use App\Http\Requests\AnalysisOverviewRequest;
use App\Knowledge\CustomerGroup;
use App\Knowledge\KnowledgeSelector;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class AnalysisOverviewController extends Controller
{
    /**
     * The analyses with filters and figures, for admins (PROJ-14).
     */
    public function index(AnalysisOverviewRequest $request, AnalysisOverview $overview, KnowledgeSelector $selector): View
    {
        $options = $overview->options();
        $filter = $overview->filter($request->validated(), $options);

        return view('analysis-overview.index', [
            'filter' => $filter,
            'rows' => $overview->page($filter, (int) $request->validated('page', 1))->appends(Arr::except($request->validated(), 'page')),
            'figures' => $overview->figures($filter),
            'options' => $options,
            'overview' => $overview,
            'productTitles' => collect($selector->products())->pluck('title', 'slug')->all(),
            'groups' => collect($selector->customerGroups())->mapWithKeys(fn (CustomerGroup $group): array => [$group->key => $group->label])->all(),
        ]);
    }
}
