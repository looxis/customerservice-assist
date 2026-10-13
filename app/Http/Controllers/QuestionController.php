<?php

namespace App\Http\Controllers;

use App\Knowledge\KnowledgeSelector;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * A question to the knowledge base without a ticket (PROJ-40). Frontend stage: the page "Frage stellen"
 * works; storing, showing and continuing a question follow with the backend stage.
 */
class QuestionController extends Controller
{
    public function create(KnowledgeSelector $selector): View
    {
        return view('questions.create', [
            'form' => [
                'groups' => $selector->customerGroups(),
                'suggestedGroup' => 'unclear',
                'groupSource' => null,
                'products' => $selector->products(),
                'suggestedProducts' => [],
                'productReasons' => [],
                'mentionedProducts' => [],
            ],
            // Backend stage: the latest questions of the chosen person, see components/question/recent.
            'recent' => [],
        ]);
    }

    public function store(): never
    {
        throw new HttpException(501, 'Wird mit dem Backend von PROJ-40 umgesetzt.');
    }

    public function show(string $number): never
    {
        throw new HttpException(501, 'Wird mit dem Backend von PROJ-40 umgesetzt.');
    }

    public function followUp(string $number): never
    {
        throw new HttpException(501, 'Wird mit dem Backend von PROJ-40 umgesetzt.');
    }
}
