<?php

namespace App\Knowledge\Retrieval\Router;

use App\Knowledge\Retrieval\RetrievalResult;

/**
 * The documents the router may look at: usable documents in the scope that the retrieval did not take (PROJ-37).
 * Scope, status and validation already hold, because the retrieval only looks at usable documents in scope.
 * What the retrieval left out for a reason of its own stays out:
 *  - hits cut for size (they were found, only the room was short),
 *  - binding rules tied to an action (the safeguard decides) or whose condition is known to fail,
 *  - reference cases (they have their own selection).
 * A document whose hits an exclusion suppressed stays in the pool with a note: the exclusion applies to one
 * sentence, not to the document, and another concern in the same message may still need it.
 * A `when` on a triggered document is only one more way in, never a barrier, so it does not keep a document out.
 */
class RouterPool
{
    private const array EXAMPLE_TYPES = ['example-good', 'example-bad'];

    /**
     * @return list<PoolEntry>
     */
    public function build(RetrievalResult $first): array
    {
        $suppressed = [];

        foreach ($first->suppressed as $item) {
            $suppressed[$item['id']][] = ['trigger' => $item['trigger'], 'tier' => $item['tier'], 'sentence' => $item['sentence'], 'exclusion' => $item['exclusion']];
        }

        $pool = [];

        foreach ($first->notRetrieved as $item) {
            $document = $item['document'];

            if (in_array($document->type, self::EXAMPLE_TYPES, true) || $item['code'] === RetrievalResult::OUT_OF_SPACE || $item['code'] === RetrievalResult::ACTION_BOUND) {
                continue;
            }

            if ($document->retrieval() === 'binding' && $item['code'] === RetrievalResult::CONDITION_FALSE) {
                continue;
            }

            $pool[] = new PoolEntry($document, $suppressed[(string) $document->id] ?? []);
        }

        return $pool;
    }
}
