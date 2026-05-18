<?php

use App\Models\Course;
use App\Models\Enrollment;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Http;

new
#[Title('Payment Successful')]
#[Layout('components.layouts.app')]
class extends Component {
    use Toast;

    public Course $course;
    public $sessionId;
    public $paymentStatus = 'processing';
    public $enrollmentId = null;

    public function mount(Course $course): void
    {
        $this->course = $course;
        $this->sessionId = request()->get('session_id');

        if ($this->sessionId) {
            $this->verifyPayment();
        }
    }

    public function verifyPayment(): void
    {
        try {
            // Utiliser Laravel HTTP Client au lieu du SDK Stripe
            $response = Http::withBasicAuth(config('services.stripe.secret'), '')
                ->get('https://api.stripe.com/v1/checkout/sessions/' . $this->sessionId);

            if (!$response->successful()) {
                $error = $response->json('error.message') ?? 'Failed to retrieve session';
                throw new \Exception($error);
            }

            $session = $response->json();

            if ($session['payment_status'] === 'paid') {
                // Vérifier si déjà inscrit
                $existing = auth()->user()->coursesEnrolled()
                    ->where('course_id', $this->course->id)
                    ->first();

                if (!$existing) {
                    // Créer l'inscription
                    auth()->user()->coursesEnrolled()->attach($this->course->id, [
                        'enrolled_at' => now(),
                        'status' => 'active',
                        'paid_amount' => $session['amount_total'] / 100,
                        'payment_intent_id' => $session['payment_intent'],
                        'paid_at' => now(),
                    ]);

                    // Récupérer l'inscription créée
                    $enrollment = auth()->user()->coursesEnrolled()
                        ->where('course_id', $this->course->id)
                        ->first();

                    $this->paymentStatus = 'success';
                    $this->enrollmentId = $enrollment->id ?? null;

                    // Enregistrer une session d'étude
                    \App\Models\StudySession::create([
                        'user_id' => auth()->id(),
                        'course_id' => $this->course->id,
                        'duration_minutes' => 0,
                        'date' => today(),
                    ]);

                    // Mettre à jour le streak de l'utilisateur
                    $this->updateLearningStreak();

                } else {
                    $this->paymentStatus = 'already_enrolled';
                }
            } else {
                $this->paymentStatus = 'failed';
            }
        } catch (\Exception $e) {
            $this->paymentStatus = 'error';
            \Log::error('Payment verification error: ' . $e->getMessage());
            $this->error(__('Payment verification failed: ') . $e->getMessage());
        }
    }

    protected function updateLearningStreak(): void
    {
        $streak = auth()->user()->learningStreak;

        if ($streak) {
            $today = now()->toDateString();

            if ($streak->last_study_date != $today) {
                $streak->update([
                    'current_streak' => $streak->current_streak + 1,
                    'last_study_date' => $today,
                    'total_study_days' => $streak->total_study_days + 1,
                ]);
            }
        }
    }

    public function getMessage()
    {
        return match($this->paymentStatus) {
            'success' => __('Thank you for your purchase! You are now enrolled in this course.'),
            'already_enrolled' => __('You were already enrolled in this course.'),
            'failed' => __('Payment verification failed. Please contact support.'),
            'error' => __('An error occurred while verifying your payment.'),
            default => __('Processing your payment...'),
        };
    }

    public function getIcon()
    {
        return match($this->paymentStatus) {
            'success', 'already_enrolled' => 'o-check-circle',
            'failed', 'error' => 'o-exclamation-circle',
            default => 'o-clock',
        };
    }

    public function getIconColor()
    {
        return match($this->paymentStatus) {
            'success' => 'text-green-500',
            'already_enrolled' => 'text-yellow-500',
            'failed', 'error' => 'text-red-500',
            default => 'text-blue-500',
        };
    }
}; ?>

<div class="max-w-2xl mx-auto text-center">
    @if($paymentStatus === 'success')
        <div class="p-12 bg-white shadow-sm rounded-xl">
            <div class="flex items-center justify-center w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full">
                <x-icon name="o-check" class="w-10 h-10 text-green-600" />
            </div>
            <h1 class="mb-4 text-2xl font-bold text-gray-900">{{ __('Payment Successful!') }}</h1>
            <p class="mb-6 text-gray-600">
                {{ __('Thank you for your purchase! You are now enrolled in') }}
                <strong>{{ $course->title }}</strong>
            </p>

            <div class="p-6 mb-8 rounded-lg bg-gray-50">
                <h3 class="mb-2 font-semibold text-gray-900">{{ __('What\'s next?') }}</h3>
                <p class="text-gray-600">{{ __('Start learning right away! Your first lesson is waiting for you.') }}</p>
            </div>

            <div class="flex justify-center space-x-4">
                <x-button link="{{ route('student.course.show', $course) }}" class="btn-primary">
                    {{ __('Start Learning') }}
                </x-button>
                <x-button link="{{ route('student.dashboard') }}" class="btn-ghost">
                    {{ __('Go to Dashboard') }}
                </x-button>
            </div>
        </div>

    @elseif($paymentStatus === 'already_enrolled')
        <div class="p-12 bg-white shadow-sm rounded-xl">
            <div class="flex items-center justify-center w-20 h-20 mx-auto mb-6 bg-yellow-100 rounded-full">
                <x-icon name="o-exclamation-triangle" class="w-10 h-10 text-yellow-600" />
            </div>
            <h1 class="mb-4 text-2xl font-bold text-gray-900">{{ __('Already Enrolled') }}</h1>
            <p class="mb-6 text-gray-600">
                {{ __('You are already enrolled in this course.') }}
            </p>
            <x-button link="{{ route('student.course.show', $course) }}" class="btn-primary">
                {{ __('Continue Learning') }}
            </x-button>
        </div>

    @elseif($paymentStatus === 'failed')
        <div class="p-12 bg-white shadow-sm rounded-xl">
            <div class="flex items-center justify-center w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full">
                <x-icon name="o-x-mark" class="w-10 h-10 text-red-600" />
            </div>
            <h1 class="mb-4 text-2xl font-bold text-gray-900">{{ __('Payment Failed') }}</h1>
            <p class="mb-6 text-gray-600">
                {{ __('There was an issue processing your payment. Please try again.') }}
            </p>
            <div class="flex justify-center space-x-4">
                <x-button link="{{ route('payment.checkout', $course) }}" class="btn-primary">
                    {{ __('Try Again') }}
                </x-button>
                <x-button link="{{ route('student.catalog') }}" class="btn-ghost">
                    {{ __('Browse Courses') }}
                </x-button>
            </div>
        </div>

    @else
        <div class="p-12 bg-white shadow-sm rounded-xl">
            <div class="w-12 h-12 mx-auto mb-6 border-b-2 rounded-full animate-spin border-primary-600"></div>
            <h1 class="mb-4 text-2xl font-bold text-gray-900">{{ __('Processing Payment...') }}</h1>
            <p class="text-gray-600">{{ __('Please wait while we confirm your payment.') }}</p>
        </div>
    @endif
</div>
