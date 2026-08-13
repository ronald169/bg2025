@props(['modules' => []])

@forelse($modules as $module)
    <div class="nav-section">
        <div class="nav-section-title" style="background-color: {{ $module['color'] ?? '#9e9e9e' }};">
            {{ $module['title'] }}
        </div>
        <div class="nav-grid">
            @foreach($module['questions'] as $question)
                <button class="nav-btn {{ $question['done'] ? 'done' : '' }}"
                        wire:click="goToQuestion({{ $question['id'] }})"
                        title="{{ $question['label'] }}">
                    {{ $question['label'] }}
                </button>
            @endforeach
        </div>
    </div>
@empty
    <p>{{ __('Aucune section disponible') }}</p>
@endforelse
