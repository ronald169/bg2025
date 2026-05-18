<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Progress;
use App\Models\QuizAttempt;
use App\Models\StudySession;
use Mary\Traits\Toast;

new
#[Title('User Details - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public User $user;

    // Getters pour les données calculées
    public function getEnrolledCoursesCountProperty()
    {
        return Enrollment::where('user_id', $this->user->id)->count();
    }

    public function getCompletedLessonsCountProperty()
    {
        return Progress::where('user_id', $this->user->id)
            ->where('is_completed', true)
            ->count();
    }

    public function getTotalStudyTimeProperty()
    {
        return StudySession::where('user_id', $this->user->id)->sum('duration_minutes');
    }

    public function getQuizzesTakenCountProperty()
    {
        return QuizAttempt::where('user_id', $this->user->id)->count();
    }

    public function getAverageQuizScoreProperty()
    {
        return round(QuizAttempt::where('user_id', $this->user->id)->avg('score') ?? 0, 1);
    }

    public function formatDuration($minutes): string
    {
        if ($minutes < 60) return "{$minutes} min";
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
    }

    public function toggleStatus()
    {
        $newStatus = $this->user->status === 'active' ? 'inactive' : 'active';
        $this->user->update(['status' => $newStatus]);
        $this->success($newStatus === 'active' ? __('User activated') : __('User deactivated'));
    }

    public function render()
    {
        return $this->view([
            'enrolledCoursesCount' => $this->enrolledCoursesCount,
            'completedLessonsCount' => $this->completedLessonsCount,
            'totalStudyTime' => $this->totalStudyTime,
            'quizzesTakenCount' => $this->quizzesTakenCount,
            'averageQuizScore' => $this->averageQuizScore,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('admin.users') }}" wire:navigate class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to users') }}
            </a>
        </div>

        {{-- Header avec avatar et actions --}}
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-20 h-20 text-3xl font-bold text-white rounded-full shadow-lg bg-gradient-to-r from-primary to-secondary">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold md:text-3xl">{{ $user->name }}</h1>
                    <p class="text-base-content/70">{{ $user->email }}</p>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <x-badge :value="ucfirst($user->role)" :class="match($user->role) {
                            'admin' => 'badge-error',
                            'teacher' => 'badge-info',
                            'student' => 'badge-success',
                            default => 'badge-ghost',
                        } . ' badge-soft'" />
                        @if($user->status === 'active')
                            <x-badge value="{{ __('Active') }}" class="badge-success badge-soft" />
                        @else
                            <x-badge value="{{ __('Inactive') }}" class="badge-neutral badge-soft" />
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <x-button label="{{ __('Edit') }}" icon="o-pencil" link="{{ route('admin.users.edit', $user) }}" class="btn-outline" />
                <x-button label="{{ $user->status === 'active' ? __('Deactivate') : __('Activate') }}" icon="{{ $user->status === 'active' ? 'o-eye-slash' : 'o-eye' }}" class="btn-ghost" wire:click="toggleStatus" wire:confirm="{{ __('Are you sure?') }}" />
            </div>
        </div>

        {{-- Cartes statistiques --}}
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <x-stat title="{{ __('Courses enrolled') }}" :value="$enrolledCoursesCount" icon="o-academic-cap" class="text-primary" />
            <x-stat title="{{ __('Completed lessons') }}" :value="$completedLessonsCount" icon="o-check-circle" class="text-success" />
            <x-stat title="{{ __('Study time') }}" :value="$this->formatDuration($totalStudyTime)" icon="o-clock" class="text-warning" />
            <x-stat title="{{ __('Quiz average') }}" :value="$averageQuizScore . '%'" icon="o-document-text" class="text-info" />
        </div>

        {{-- Informations supplémentaires --}}
        <div class="grid gap-6 md:grid-cols-2">
            {{-- Informations personnelles --}}
            <x-card title="{{ __('Personal information') }}" icon="o-user" shadow separator>
                <div class="space-y-2">
                    <div class="flex justify-between"><span class="font-medium">{{ __('Phone') }}:</span><span>{{ $user->phone ?? '-' }}</span></div>
                    <div class="flex justify-between"><span class="font-medium">{{ __('Registered on') }}:</span><span>{{ $user->created_at->format('d.m.Y H:i') }}</span></div>
                    <div class="flex justify-between"><span class="font-medium">{{ __('Last updated') }}:</span><span>{{ $user->updated_at->format('d.m.Y H:i') }}</span></div>
                </div>
            </x-card>

            {{-- Bio (si existe) --}}
            @if($user->bio)
                <x-card title="{{ __('Bio') }}" icon="o-document-text" shadow separator>
                    <p>{{ $user->bio }}</p>
                </x-card>
            @endif
        </div>

        {{-- Activité récente --}}
        <div class="mt-6">
            <x-card title="{{ __('Recent activity') }}" icon="o-clock" shadow separator>
                @php
                    $recentActivity = collect()
                        ->concat(
                            Progress::where('user_id', $user->id)
                                ->with('lesson.course')
                                ->where('is_completed', true)
                                ->latest('updated_at')
                                ->take(5)
                                ->get()
                                ->map(fn($p) => [
                                    'type' => 'lesson',
                                    'title' => $p->lesson->title,
                                    'course' => $p->lesson->course->title,
                                    'date' => $p->updated_at,
                                    'icon' => 'o-check-circle',
                                ])
                        )
                        ->concat(
                            QuizAttempt::where('user_id', $user->id)
                                ->with('quiz.lesson.course')
                                ->latest('created_at')
                                ->take(5)
                                ->get()
                                ->map(fn($a) => [
                                    'type' => 'quiz',
                                    'title' => $a->quiz->title,
                                    'course' => $a->quiz->lesson->course->title,
                                    'score' => $a->score,
                                    'date' => $a->created_at,
                                    'icon' => 'o-document-text',
                                ])
                        )
                        ->sortByDesc('date')
                        ->take(10);
                @endphp
                @if($recentActivity->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentActivity as $activity)
                            <div class="flex items-center justify-between p-3 transition rounded-lg hover:bg-base-200">
                                <div class="flex items-center gap-3">
                                    <x-icon :name="$activity['icon']" class="w-5 h-5 text-primary" />
                                    <div>
                                        <p class="text-sm font-medium">{{ $activity['title'] }}</p>
                                        <p class="text-xs text-base-content/60">{{ $activity['course'] }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-base-content/50">{{ $activity['date']->diffForHumans() }}</p>
                                    @if($activity['type'] === 'quiz')
                                        <p class="text-xs text-primary">{{ $activity['score'] }} pts</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center">
                        <x-icon name="o-clock" class="w-12 h-12 mx-auto text-base-content/30" />
                        <p class="mt-2 text-base-content/60">{{ __('No activity yet') }}</p>
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</div>
