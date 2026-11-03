@props(['days'])

{{-- Days of docs/CHANGELOG.md (see App\Changelog\Changelog), newest first. --}}
<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    @foreach ($days as $day)
        <div>
            <p class="text-sm font-medium text-slate-900"><time datetime="{{ $day['date']->format('Y-m-d') }}">{{ $day['date']->locale('de')->translatedFormat("j. M 'y") }}</time></p>
            <x-knowledge.text :html="$day['html']" class="mt-1 text-slate-700" />
        </div>
    @endforeach
</div>
