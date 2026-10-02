@props(['html'])

{{-- $html comes from App\Knowledge\KnowledgeMarkdown, which escapes raw HTML. --}}
<div {{ $attributes->merge(['class' => 'knowledge-text']) }}>{{ $html }}</div>
