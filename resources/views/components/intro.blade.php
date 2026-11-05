{{-- Explanatory text that only shows in the introduction mode (PROJ-74). Warnings that need action do not belong in here. --}}
@if (app(\App\Staff\IntroMode::class)->isActive(request()))
    {{ $slot }}
@endif
