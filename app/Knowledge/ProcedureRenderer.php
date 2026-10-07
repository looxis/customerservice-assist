<?php

namespace App\Knowledge;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\HtmlString;

/**
 * Shows a procedure for working through it (PROJ-30): the safe Markdown view
 * of the knowledge base, with a checkbox at every step and check item and
 * critical notes highlighted as warnings. Ticking is not saved.
 */
class ProcedureRenderer
{
    private const array CHECKLIST_SECTIONS = ['arbeitsschritte', 'abschlusskontrolle'];

    private const string CRITICAL_SECTION = 'kritische hinweise';

    public function __construct(
        private readonly KnowledgeMarkdown $markdown,
        private readonly KnowledgeLibrary $library,
    ) {}

    public function render(KnowledgeDocument $procedure): HtmlString
    {
        $html = $this->markdown->render($procedure->body, $this->links($procedure))->toHtml();
        $document = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR, 'UTF-8');
        $section = null;
        $critical = null;

        foreach (iterator_to_array($document->body->childNodes) as $node) {
            if (! $node instanceof Element) {
                continue;
            }

            if (preg_match('/^h[2-6]$/i', $node->tagName)) {
                $section = mb_strtolower(trim($node->textContent));
                $critical = null;

                if ($section === self::CRITICAL_SECTION) {
                    $critical = $document->createElement('div');
                    $critical->setAttribute('class', 'procedure-critical');
                    $node->after($critical);
                    $critical->append($node);
                }

                continue;
            }

            if ($critical !== null) {
                $critical->append($node);

                continue;
            }

            if (in_array($section, self::CHECKLIST_SECTIONS, true) && in_array(strtolower($node->tagName), ['ol', 'ul'], true)) {
                $this->checkboxes($document, $node);
            }

            if (strtolower($node->tagName) === 'p' && preg_match('/^\s*Achtung:/u', $node->textContent)) {
                $node->setAttribute('class', 'procedure-warning');
            }
        }

        return new HtmlString($document->body->innerHTML);
    }

    private function checkboxes(HTMLDocument $document, Element $list): void
    {
        foreach (iterator_to_array($list->children) as $item) {
            if (strtolower($item->tagName) !== 'li') {
                continue;
            }

            foreach (iterator_to_array($item->querySelectorAll('input[type=checkbox]')) as $taskBox) {
                $taskBox->remove();
            }

            $label = $document->createElement('label');
            $label->setAttribute('class', 'procedure-check');
            $box = $document->createElement('input');
            $box->setAttribute('type', 'checkbox');
            $text = $document->createElement('span');

            foreach (iterator_to_array($item->childNodes) as $child) {
                $text->append($child);
            }

            $label->append($box, $text);
            $item->append($label);
        }
    }

    /**
     * @return array<string, string>
     */
    private function links(KnowledgeDocument $procedure): array
    {
        return $this->library->all()
            ->filter(fn (KnowledgeDocument $candidate): bool => $candidate->id !== null && $candidate->path !== $procedure->path && ! $this->library->hasErrors($candidate))
            ->mapWithKeys(fn (KnowledgeDocument $candidate): array => [$candidate->id => route('knowledge.show', ['path' => $candidate->path])])
            ->all();
    }
}
