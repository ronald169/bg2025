<?php

namespace App\Livewire;

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new
#[Title('Learn German Online - AllemandExpress')]
#[Layout('components.layouts.guest')]
class extends Component {

    public string $contactName = '';
    public string $contactEmail = '';
    public string $contactMessage = '';
    public string $newsletterEmail = '';

    public array $testimonials = [];
    public array $courses = [];
    public array $pricingPlans = [];

    public function mount(): void
    {
        $this->loadTestimonials();
        $this->loadCourses();
        $this->loadPricingPlans();
    }

    private function loadPricingPlans(): void
    {
        $this->pricingPlans = [
            'course' => [
                'name' => 'Single Course Access',
                'description' => 'Full access to one course of your choice',
                'price' => 'Pay course price',
                'period' => '1 year access',
                'features' => [
                    'Complete course content',
                    'All lessons and exercises',
                    'Course quizzes',
                    'Downloadable resources',
                    'Course certificate upon completion',
                    '30-day money-back guarantee',
                ],
                'button_text' => 'Choose Course',
                'button_link' => route('student.catalog'),
                'highlighted' => false,
                'icon' => 'o-book-open',
            ],
            'monthly' => [
                'name' => 'Monthly Premium',
                'description' => 'Full platform access, month by month',
                'price' => '2,000',
                'original_price' => '4,000',
                'saving' => 'Save 50%',
                'period' => 'per month',
                'features' => [
                    'Access to ALL courses (A1-C2)',
                    'All quizzes and exercises',
                    'Downloadable materials',
                    'Progress tracking',
                    'Personalized learning path',
                    'Priority email support',
                    'Cancel anytime',
                ],
                'button_text' => 'Start Free Trial',
                'button_link' => route('register'),
                'highlighted' => false,
                'icon' => 'o-credit-card',
            ],
            'semester' => [
                'name' => '6 Months Premium',
                'description' => 'Best value for serious learners',
                'price' => '10,000',
                'original_price' => '40,000',
                'saving' => 'Save 75%',
                'period' => 'for 6 months',
                'features' => [
                    'Access to ALL courses (A1-C2)',
                    'All quizzes and exercises',
                    'Downloadable materials',
                    'Progress tracking',
                    'Personalized learning path',
                    'Priority email support',
                    '2 Live coaching sessions',
                    'Private study group access',
                    'Certificate of completion',
                ],
                'button_text' => 'Get 6 Months Access',
                'button_link' => route('register'),
                'highlighted' => true,
                'icon' => 'o-star',
                'badge' => 'Best Value',
            ],
            'annual' => [
                'name' => 'Annual Premium',
                'description' => 'Ultimate learning experience',
                'price' => '20,000',
                'original_price' => '48,000',
                'saving' => 'Save 58%',
                'period' => 'per year',
                'features' => [
                    'Access to ALL courses (A1-C2)',
                    'All quizzes and exercises',
                    'Downloadable materials',
                    'Progress tracking',
                    'Personalized learning path',
                    'Priority email & chat support',
                    '4 Live coaching sessions',
                    'Private study group access',
                    'Certificate of completion',
                    'Offline course access',
                    'Monthly group conversation classes',
                    'Exam preparation workshops',
                ],
                'button_text' => 'Get Annual Access',
                'button_link' => route('register'),
                'highlighted' => false,
                'icon' => 'o-trophy',
            ],
            'premium_plus' => [
                'name' => 'Premium+',
                'description' => 'For dedicated learners',
                'price' => '35,000',
                'original_price' => '60,000',
                'saving' => 'Save 42%',
                'period' => 'per year',
                'features' => [
                    'Everything in Annual Premium',
                    '12 Private 1-on-1 coaching sessions',
                    'Personalized study plan',
                    'Weekly progress reports',
                    'Direct chat with instructors',
                    'Priority assignment grading',
                    'Exam registration assistance',
                    'Goethe exam preparation',
                    'Interview preparation',
                    'CV/resume review in German',
                    'Certificate of excellence',
                ],
                'button_text' => 'Get Premium+',
                'button_link' => route('register'),
                'highlighted' => false,
                'icon' => 'o-sparkles',
            ],
        ];
    }

    public function selectPlan($plan): void
    {
        session(['selected_plan' => $plan]);
        $this->redirectRoute('register', navigate: true);
    }

    private function loadTestimonials(): void
    {
        $this->testimonials = [
            [
                'name' => 'Marie Schmidt',
                'role' => 'Goethe B2 Certified',
                'content' => 'Grâce à AllemandExpress, j\'ai réussi mon Goethe B2 en seulement 4 mois. Les cours sont excellents !',
                'rating' => 5,
                'image' => '/images/testimonials/student1.jpg',
                'country' => '🇫🇷',
                'achievement' => 'B2 Goethe-Zertifikat'
            ],
            [
                'name' => 'Thomas Weber',
                'role' => 'University Student',
                'content' => 'La préparation pour le TELC B1 était parfaite. Je recommande vivement à tous ceux qui veulent apprendre l\'allemand.',
                'rating' => 5,
                'image' => '/images/testimonials/student2.jpg',
                'country' => '🇩🇪',
                'achievement' => 'TELC B1'
            ],
            [
                'name' => 'Sophie Laurent',
                'role' => 'High School Student',
                'content' => 'Les leçons sont interactives et faciles à comprendre. J\'ai amélioré ma note de 12 à 17 en allemand !',
                'rating' => 5,
                'image' => '/images/testimonials/student3.jpg',
                'country' => '🇧🇪',
                'achievement' => '+5 points'
            ],
        ];
    }

    private function loadCourses(): void
    {
        $this->courses = [
            ['level' => 'A1', 'title' => 'German for Beginners', 'lessons' => 40, 'students' => 12340, 'icon' => '🌱', 'color' => 'green'],
            ['level' => 'A2', 'title' => 'Elementary German', 'lessons' => 45, 'students' => 9870, 'icon' => '📖', 'color' => 'green'],
            ['level' => 'B1', 'title' => 'Intermediate German', 'lessons' => 50, 'students' => 15230, 'icon' => '🎯', 'color' => 'orange'],
            ['level' => 'B2', 'title' => 'Upper Intermediate', 'lessons' => 55, 'students' => 11250, 'icon' => '⭐', 'color' => 'orange'],
            ['level' => 'C1', 'title' => 'Advanced German', 'lessons' => 60, 'students' => 6780, 'icon' => '🏆', 'color' => 'red'],
            ['level' => 'C2', 'title' => 'Mastery German', 'lessons' => 65, 'students' => 3420, 'icon' => '👑', 'color' => 'red'],
        ];
    }

    public function submitContact(): void
    {
        $this->validate([
            'contactName' => 'required|min:2',
            'contactEmail' => 'required|email',
            'contactMessage' => 'required|min:10',
        ]);

        // Logique d'envoi de contact
        session()->flash('success', 'Votre message a été envoyé ! Nous vous répondrons rapidement. 🇩🇪');
        $this->reset(['contactName', 'contactEmail', 'contactMessage']);
    }

    public function subscribeNewsletter(): void
    {
        $this->validate([
            'newsletterEmail' => 'required|email',
        ]);

        // Logique d'inscription newsletter
        session()->flash('newsletter_success', 'Merci pour votre inscription ! 📧');
        $this->reset('newsletterEmail');
    }

    public function switchLanguage($locale): void
    {
        if (in_array($locale, ['en', 'fr', 'de'])) {
            session(['locale' => $locale]);
            app()->setLocale($locale);
            $this->redirect(route('home'), navigate: true);
        }
    }
};
?>

{{-- SEO Meta Tags --}}
@section('meta_title', config('app.name') . ' - Learn German Online')
@section('meta_description', 'Learn German online with interactive courses for all levels. Prepare for Goethe, ÖSD, and TELC certifications. Start your German journey today!')
@section('meta_keywords', 'learn German, German course, Goethe certificate, ÖSD, TELC, German grammar, German vocabulary, German online')
@section('og_title', config('app.name') . ' - Learn German Online')
@section('og_description', 'Master the German language with our comprehensive online courses. From A1 to C2, we have the perfect course for you.')
@section('og_image', asset('images/og-image.jpg'))
@section('canonical_url', url('/'))
@section('meta_robots', 'index,follow')

@push('structured_data')
@php
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => config('app.name'),
        'url' => url('/'),
        'description' => 'Learn German online with interactive courses for all levels',
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => url('/catalog?search={search_term_string}'),
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
@endphp
<script type="application/ld+json">
    {!! json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush


<div>
    <!-- Hero Section avec Image -->
    <section class="relative overflow-hidden bg-gradient-to-br from-orange-50 via-white to-blue-50">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-10 w-72 h-72 bg-[#FF6B35] rounded-full mix-blend-multiply filter blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-72 h-72 bg-[#1E6091] rounded-full mix-blend-multiply filter blur-3xl"></div>
        </div>

        <div class="container px-4 py-16 mx-auto lg:py-24">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <!-- Left Column - Text -->
                <div class="text-center lg:text-left">
                    <div class="inline-flex items-center gap-2 px-4 py-2 mb-6 rounded-full shadow-sm bg-white/80 backdrop-blur-sm">
                        <span class="text-2xl">🇩🇪</span>
                        <span class="text-sm font-semibold text-[#FF6B35]">{{ __('Learn German with confidence') }}</span>
                    </div>

                    <h1 class="mb-6 text-4xl font-bold leading-tight text-gray-900 md:text-5xl lg:text-6xl">
                        {{ __('Master') }}
                        <span class="relative inline-block">
                            <span class="relative z-10 text-transparent bg-clip-text bg-gradient-to-r from-[#FF6B35] to-[#1E6091]">{{ __('German') }}</span>
                            <svg class="absolute bottom-0 left-0 w-full h-3 -z-0" viewBox="0 0 100 10" preserveAspectRatio="none">
                                <path d="M0 5 L100 5" stroke="#FF6B35" stroke-width="2" stroke-dasharray="5,5" fill="none"/>
                            </svg>
                        </span>
                        <br>{{ __('for Exams & Life') }}
                    </h1>

                    <p class="max-w-2xl mx-auto mb-8 text-lg text-gray-600 lg:mx-0">
                        {{ __('Prepare for Goethe, ÖSD, and TELC certifications. From A1 to C2, learn at your own pace with interactive lessons and personalized support.') }}
                    </p>

                    <div class="flex flex-col justify-center gap-4 sm:flex-row lg:justify-start">
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center gap-2 px-8 py-3 text-white transition-all transform bg-gradient-to-r from-[#FF6B35] to-[#1E6091] rounded-lg shadow-lg hover:shadow-xl hover:-translate-y-0.5 group">
                            <span>{{ __('Start Free Trial') }}</span>
                            <x-icon name="o-arrow-right" class="w-5 h-5 transition-transform group-hover:translate-x-1" />
                        </a>
                        <a href="#courses"
                           class="inline-flex items-center justify-center gap-2 px-8 py-3 text-gray-700 transition-all border border-gray-300 rounded-lg hover:border-[#FF6B35] hover:text-[#FF6B35]">
                            <x-icon name="o-play-circle" class="w-5 h-5" />
                            <span>{{ __('Watch Demo') }}</span>
                        </a>
                    </div>

                    <div class="flex flex-wrap justify-center gap-4 mt-8 lg:justify-start">
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <x-icon name="o-check-circle" class="w-5 h-5 text-green-500" />
                            <span>{{ __('7-day free trial') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <x-icon name="o-check-circle" class="w-5 h-5 text-green-500" />
                            <span>{{ __('No credit card required') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                            <x-icon name="o-check-circle" class="w-5 h-5 text-green-500" />
                            <span>{{ __('Cancel anytime') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Hero Image -->
                <div class="relative">
                    <div class="relative">
                        <div class="overflow-hidden shadow-2xl rounded-2xl">
                            <img src="{{ asset('/storage/images/hero-german-learning.jpeg') }}"
                                 alt="German Learning Platform"
                                 class="object-cover w-full h-auto transition-transform duration-500 hover:scale-105"
                                 onerror="this.src='https://placehold.co/600x500/FF6B35/white?text=Learn+German'">
                        </div>
                    </div>

                    <!-- Floating Badges -->
                    <div class="absolute p-3 bg-white shadow-lg -top-4 -right-4 rounded-xl animate-float">
                        <div class="flex items-center gap-2">
                            <div class="flex text-yellow-400">
                                ★★★★★
                            </div>
                            <span class="font-bold text-gray-900">4.9</span>
                            <span class="text-sm text-gray-500">(2.5k+ reviews)</span>
                        </div>
                    </div>

                    <div class="absolute p-3 bg-white shadow-lg -bottom-4 -left-4 rounded-xl animate-float animation-delay-2000">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">🎓</span>
                            <div>
                                <div class="font-bold text-gray-900">50,000+</div>
                                <div class="text-xs text-gray-500">Active Students</div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute p-3 bg-white shadow-lg top-1/2 -right-2 rounded-xl animate-float animation-delay-4000">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">⭐</span>
                            <div>
                                <div class="font-bold text-gray-900">94%</div>
                                <div class="text-xs text-gray-500">Success Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 bg-white border-gray-200 border-y">
        <div class="container px-4 mx-auto">
            <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
                <div class="text-center">
                    <div class="text-3xl font-bold text-[#FF6B35]">50,000+</div>
                    <div class="text-sm text-gray-600">{{ __('Active Students') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-[#FF6B35]">94%</div>
                    <div class="text-sm text-gray-600">{{ __('Exam Success Rate') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-[#FF6B35]">200+</div>
                    <div class="text-sm text-gray-600">{{ __('Interactive Courses') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-[#FF6B35]">24/7</div>
                    <div class="text-sm text-gray-600">{{ __('Teacher Support') }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Courses by Level Section -->
    <section class="py-20 bg-gray-50" id="courses">
        <div class="container px-4 mx-auto">
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                    {{ __('Courses by') }}
                    <span class="text-[#FF6B35]">{{ __('German Level') }}</span>
                </h2>
                <p class="text-xl text-gray-600">
                    {{ __('From absolute beginner to near-native fluency, find your perfect course') }}
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($courses as $course)
                <div class="p-6 transition-all bg-white shadow-sm rounded-xl hover:shadow-lg hover:-translate-y-1 group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-3xl bg-{{ $course['color'] }}-100">
                            {{ $course['icon'] }}
                        </div>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-{{ $course['color'] }}-100 text-{{ $course['color'] }}-700">
                            {{ $course['level'] }}
                        </span>
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900">{{ __($course['title']) }}</h3>
                    <div class="flex items-center gap-4 mb-4 text-sm text-gray-500">
                        <span class="flex items-center gap-1">
                            <x-icon name="o-book-open" class="w-4 h-4" />
                            {{ $course['lessons'] }} lessons
                        </span>
                        <span class="flex items-center gap-1">
                            <x-icon name="o-user-group" class="w-4 h-4" />
                            {{ number_format($course['students']) }} students
                        </span>
                    </div>
                    <a href="{{ route('student.catalog') }}"
                       class="inline-flex items-center gap-2 text-[#FF6B35] font-semibold group-hover:gap-3 transition-all">
                        {{ __('Start Learning') }}
                        <x-icon name="o-arrow-right" class="w-4 h-4" />
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="py-20 bg-white">
        <div class="container px-4 mx-auto">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="relative order-2 lg:order-1">
                    <div class="relative overflow-hidden shadow-xl rounded-2xl">
                        <img src="{{ asset('/storage/images/why-choose-us.png') }}"
                             alt="Why Choose Us"
                             class="object-cover w-full h-auto"
                             onerror="this.src='https://placehold.co/500x400/1E6091/white?text=Interactive+Learning'">
                    </div>
                    <div class="absolute p-4 bg-white shadow-lg -bottom-6 -right-6 rounded-xl">
                        <div class="flex items-center gap-3">
                            <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full">
                                <x-icon name="o-check" class="w-6 h-6 text-green-600" />
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">{{ __('Certified Teachers') }}</div>
                                <div class="text-sm text-gray-500">+50 expert instructors</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="order-1 lg:order-2">
                    <div class="inline-flex items-center gap-2 px-4 py-2 mb-4 bg-orange-100 rounded-full">
                        <span class="text-2xl">✨</span>
                        <span class="text-sm font-semibold text-[#FF6B35]">{{ __('Why Choose Us') }}</span>
                    </div>
                    <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                        {{ __('The Most Effective') }}
                        <span class="text-[#FF6B35]">{{ __('Way to Learn German') }}</span>
                    </h2>
                    <p class="mb-6 text-lg text-gray-600">
                        {{ __('Our proven methodology combines interactive lessons, real-life conversations, and exam preparation strategies.') }}
                    </p>

                    <div class="space-y-4">
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-6 h-6 mt-1 text-[#FF6B35]">✓</div>
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ __('Interactive Video Lessons') }}</h4>
                                <p class="text-sm text-gray-500">{{ __('Engaging content with quizzes and exercises') }}</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-6 h-6 mt-1 text-[#FF6B35]">✓</div>
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ __('Personalized Learning Path') }}</h4>
                                <p class="text-sm text-gray-500">{{ __('Tailored to your level and goals') }}</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-6 h-6 mt-1 text-[#FF6B35]">✓</div>
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ __('Exam Preparation Focus') }}</h4>
                                <p class="text-sm text-gray-500">{{ __('Goethe, ÖSD, TELC certified preparation') }}</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 w-6 h-6 mt-1 text-[#FF6B35]">✓</div>
                            <div>
                                <h4 class="font-semibold text-gray-900">{{ __('Live Conversation Practice') }}</h4>
                                <p class="text-sm text-gray-500">{{ __('Speak with native speakers and teachers') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-20 bg-gray-50" id="testimonials">
        <div class="container px-4 mx-auto">
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                    {{ __('What Our') }}
                    <span class="text-[#FF6B35]">{{ __('Students Say') }}</span>
                </h2>
                <p class="text-xl text-gray-600">
                    {{ __('Join thousands of successful students who transformed their German skills') }}
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @foreach($testimonials as $testimonial)
                <div class="p-6 transition-all bg-white shadow-sm rounded-xl hover:shadow-lg">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-xl">
                            {{ substr($testimonial['name'], 0, 1) }}
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $testimonial['name'] }}</h4>
                            <div class="flex items-center gap-1 text-sm text-gray-500">
                                <span>{{ $testimonial['country'] }}</span>
                                <span>•</span>
                                <span class="text-[#FF6B35]">{{ $testimonial['role'] }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex mb-3 text-yellow-400">
                        @for($i = 1; $i <= 5; $i++)
                            <x-icon name="o-star" class="w-4 h-4" />
                        @endfor
                    </div>

                    <p class="mb-4 italic text-gray-600">"{{ $testimonial['content'] }}"</p>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        <div class="flex items-center gap-1 text-sm">
                            <x-icon name="o-trophy" class="w-4 h-4 text-yellow-500" />
                            <span class="text-gray-600">{{ $testimonial['achievement'] }}</span>
                        </div>
                        <div class="flex -space-x-1">
                            <div class="flex items-center justify-center w-6 h-6 text-xs bg-green-100 rounded-full">✓</div>
                            <div class="flex items-center justify-center w-6 h-6 text-xs bg-green-100 rounded-full">✓</div>
                            <div class="flex items-center justify-center w-6 h-6 text-xs bg-green-100 rounded-full">✓</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Certifications Section -->
    <section class="py-16 bg-white">
        <div class="container px-4 mx-auto text-center">
            <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                {{ __('Official') }}
                <span class="text-[#FF6B35]">{{ __('Certifications') }}</span>
            </h2>
            <p class="max-w-2xl mx-auto mb-12 text-lg text-gray-600">
                {{ __('Prepare for internationally recognized German certificates') }}
            </p>

            <div class="flex flex-wrap items-center justify-center gap-8">
                <div class="p-6 bg-gray-50 rounded-xl">
                    <div class="mb-2 text-4xl">🎓</div>
                    <div class="font-bold text-gray-900">Goethe-Zertifikat</div>
                    <div class="text-sm text-gray-500">A1 - C2</div>
                </div>
                <div class="p-6 bg-gray-50 rounded-xl">
                    <div class="mb-2 text-4xl">📜</div>
                    <div class="font-bold text-gray-900">ÖSD</div>
                    <div class="text-sm text-gray-500">A1 - C2</div>
                </div>
                <div class="p-6 bg-gray-50 rounded-xl">
                    <div class="mb-2 text-4xl">🏅</div>
                    <div class="font-bold text-gray-900">TELC</div>
                    <div class="text-sm text-gray-500">A1 - C1</div>
                </div>
                <div class="p-6 bg-gray-50 rounded-xl">
                    <div class="mb-2 text-4xl">🇪🇺</div>
                    <div class="font-bold text-gray-900">TestDaF</div>
                    <div class="text-sm text-gray-500">University Entry</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="py-20 bg-gray-50" id="contact">
        <div class="container px-4 mx-auto">
            <div class="max-w-3xl mx-auto mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                    {{ __('Get in') }}
                    <span class="text-[#FF6B35]">{{ __('Touch') }}</span>
                </h2>
                <p class="text-xl text-gray-600">
                    {{ __('Have questions? We are here to help you on your German learning journey') }}
                </p>
            </div>

            <div class="grid gap-12 lg:grid-cols-2">
                <div class="order-2 lg:order-1">
                    @if(session('contact_success'))
                        <div class="p-4 mb-4 text-green-700 bg-green-100 border border-green-400 rounded-lg">
                            {{ session('contact_success') }}
                        </div>
                    @endif

                    <form wire:submit="submitContact" class="space-y-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input
                                    wire:model="contactName"
                                    label="{{ __('Your Name') }}"
                                    placeholder="{{ __('John Doe') }}"
                                    icon="o-user"
                                    required />
                                @error('contactName')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <x-input
                                    wire:model="contactEmail"
                                    label="{{ __('Email Address') }}"
                                    type="email"
                                    placeholder="john@example.com"
                                    icon="o-envelope"
                                    required />
                                @error('contactEmail')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div>
                            <x-textarea
                                wire:model="contactMessage"
                                label="{{ __('Your Message') }}"
                                placeholder="{{ __('How can we help you?') }}"
                                rows="5"
                                required />
                            @error('contactMessage')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-button
                            type="submit"
                            label="{{ __('Send Message') }}"
                            icon="o-paper-airplane"
                            class="w-full btn-primary md:w-auto"
                            spinner="submitContact" />
                    </form>
                </div>

                <div class="order-1 lg:order-2">
                    <div class="relative overflow-hidden shadow-xl rounded-2xl bg-gradient-to-r from-[#FF6B35]/10 to-[#1E6091]/10">
                        <img src="{{ asset('storage/images/contact-illustration.png') }}"
                            alt="Contact Us"
                            class="object-cover w-full h-auto"
                            onerror="this.onerror=null; this.src='https://placehold.co/500x400/FF6B35/white?text=Contact+Us'">
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-6">
                        <div class="p-4 text-center bg-white rounded-lg shadow-sm">
                            <div class="mb-1 text-2xl">📧</div>
                            <div class="text-sm font-medium">support@allemandexpress.com</div>
                        </div>
                        <div class="p-4 text-center bg-white rounded-lg shadow-sm">
                            <div class="mb-1 text-2xl">💬</div>
                            <div class="text-sm font-medium">{{ __('24/7 Live Chat') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="py-16 text-white bg-gradient-to-r from-[#FF6B35] to-[#1E6091]">
        <div class="container px-4 mx-auto text-center">
            <div class="max-w-2xl mx-auto">
                <div class="mb-4 text-4xl">📧</div>
                <h2 class="mb-3 text-3xl font-bold">{{ __('Stay Updated') }}</h2>
                <p class="mb-6 text-white/90">
                    {{ __('Get weekly German learning tips, exam strategies, and exclusive offers') }}
                </p>
                <form wire:submit="subscribeNewsletter" class="flex flex-col gap-3 sm:flex-row">
                    <x-input
                        wire:model="newsletterEmail"
                        type="email"
                        placeholder="{{ __('Your email address') }}"
                        class="flex-1 text-gray-900 bg-white"
                        required />
                    <x-button
                        type="submit"
                        label="{{ __('Subscribe') }} →"
                        class="bg-white text-[#FF6B35] hover:bg-gray-100"
                        spinner="subscribeNewsletter" />
                </form>
                <p class="mt-3 text-sm text-white/70">
                    {{ __('No spam, unsubscribe anytime.') }}
                </p>
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="py-20 bg-white">
        <div class="container px-4 mx-auto text-center">
            <div class="max-w-3xl mx-auto">
                <div class="mb-4 text-5xl">🇩🇪</div>
                <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                    {{ __('Ready to Start Your') }}
                    <span class="text-[#FF6B35]">{{ __('German Journey?') }}</span>
                </h2>
                <p class="mb-8 text-lg text-gray-600">
                    {{ __('Join thousands of successful students who have mastered German with us. Start your free trial today!') }}
                </p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 px-8 py-3 text-white transition-all transform bg-gradient-to-r from-[#FF6B35] to-[#1E6091] rounded-lg shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                        {{ __('Start Free Trial') }}
                        <x-icon name="o-arrow-right" class="w-5 h-5" />
                    </a>
                    <a href="{{ route('student.catalog') }}"
                       class="inline-flex items-center justify-center gap-2 px-8 py-3 text-gray-700 transition-all border border-gray-300 rounded-lg hover:border-[#FF6B35] hover:text-[#FF6B35]">
                        <x-icon name="o-academic-cap" class="w-5 h-5" />
                        {{ __('Browse Courses') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-16 md:py-24 bg-gradient-to-b from-gray-50 to-white" id="pricing">
        <div class="container px-4 mx-auto">
            <div class="max-w-3xl mx-auto mb-12 text-center">
                <h2 class="mb-4 text-3xl font-bold text-gray-900 md:text-4xl">
                    {{ __('Simple,') }}
                    <span class="text-[#FF6B35]">{{ __('Transparent') }}</span>
                    {{ __('Pricing') }}
                </h2>
                <p class="text-lg text-gray-600">
                    {{ __('Choose the plan that fits your learning journey') }}
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    {{ __('All prices in CFA Francs (XAF) • 7-day free trial available') }}
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach($pricingPlans as $key => $plan)
                <div class="relative bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 {{ $plan['highlighted'] ? 'ring-2 ring-[#FF6B35] scale-105' : '' }}">
                    @if(isset($plan['badge']))
                    <div class="absolute transform -translate-x-1/2 -top-3 left-1/2">
                        <span class="px-4 py-1 text-xs font-bold text-white rounded-full bg-[#FF6B35] shadow-md">
                            ⭐ {{ $plan['badge'] }} ⭐
                        </span>
                    </div>
                    @endif

                    <div class="p-6">
                        <!-- Icon -->
                        <div class="flex items-center justify-center w-12 h-12 mb-4 rounded-xl bg-gradient-to-r from-[#FF6B35]/10 to-[#1E6091]/10">
                            <x-icon :name="$plan['icon']" class="w-6 h-6 text-[#FF6B35]" />
                        </div>

                        <!-- Title -->
                        <h3 class="mb-2 text-xl font-bold text-gray-900">{{ __($plan['name']) }}</h3>
                        <p class="mb-4 text-sm text-gray-500">{{ __($plan['description']) }}</p>

                        <!-- Price -->
                        <div class="mb-4">
                            @if($plan['price'] === 'Pay course price')
                                <div class="text-2xl font-bold text-gray-900">{{ __($plan['price']) }}</div>
                            @else
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl font-bold text-[#FF6B35]">{{ $plan['price'] }} <span class="text-sm font-normal text-gray-500">XAF</span></span>
                                    @if(isset($plan['original_price']))
                                        <span class="text-lg text-gray-400 line-through">{{ $plan['original_price'] }} XAF</span>
                                    @endif
                                </div>
                                @if(isset($plan['saving']))
                                    <span class="inline-block px-2 py-0.5 mt-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
                                        🔥 {{ __($plan['saving']) }}
                                    </span>
                                @endif
                            @endif
                            <p class="mt-1 text-sm text-gray-500">{{ __($plan['period']) }}</p>
                        </div>

                        <!-- Features -->
                        <ul class="mb-6 space-y-2">
                            @foreach($plan['features'] as $feature)
                            <li class="flex items-start gap-2 text-sm">
                                <x-icon name="o-check-circle" class="w-4 h-4 mt-0.5 text-green-500 flex-shrink-0" />
                                <span class="text-gray-600">{{ __($feature) }}</span>
                            </li>
                            @endforeach
                        </ul>

                        <!-- Button -->
                        <a href="{{ $plan['button_link'] }}" 
                        wire:navigate
                        class="block w-full py-2.5 text-center font-semibold rounded-lg transition-all duration-200
                                {{ $plan['highlighted'] 
                                    ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white shadow-md hover:shadow-lg' 
                                    : 'border-2 border-[#FF6B35] text-[#FF6B35] hover:bg-[#FF6B35] hover:text-white' }}">
                            {{ __($plan['button_text']) }}
                        </a>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- FAQ Note -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    {{ __('All plans include a 7-day free trial. No credit card required for trial.') }}
                </p>
                <p class="mt-2 text-sm text-gray-500">
                    {{ __('Questions? Contact us at support@allemandexpress.com') }}
                </p>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

/* Pricing section animations */
.pricing-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.pricing-card:hover {
    transform: translateY(-8px);
}

/* Comparison table responsive */
@media (max-width: 768px) {
    .comparison-table {
        font-size: 12px;
    }
    .comparison-table th,
    .comparison-table td {
        padding: 8px 4px;
    }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}
</style>
@endpush
