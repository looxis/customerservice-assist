@props(['rules'])

{{-- The rules of a category of the ticket classification as bullet points, for the tooltip of its button (PROJ-80). --}}
<ul class="list-disc space-y-0.5 pl-4">
    @foreach ($rules as $rule)
        <li>{{ $rule }}</li>
    @endforeach
</ul>
