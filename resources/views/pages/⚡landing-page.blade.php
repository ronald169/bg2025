<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Master German fast with AllemandExpress')]
#[Layout('layouts.guest')]
class extends Component {
    use Toast;

    // Formulaire de contact
    public $contact_name = '';
    public $contact_email = '';
    public $contact_message = '';

    // Newsletter
    public $email = '';

    protected $rules = [
        'contact_name' => 'required|min:2',
        'contact_email' => 'required|email',
        'contact_message' => 'required|min:10',
        'email' => 'nullable|email',
    ];

    public function submitContact()
    {
        $this->validate([
            'contact_name' => 'required|min:2',
            'contact_email' => 'required|email',
            'contact_message' => 'required|min:10',
        ]);

        // Ici envoyer email ou sauvegarder en base
        // Mail::to('hello@allemandexpress.fr')->send(...)

        $this->success(
            title: __('Thank you!'),
            description: __('We will get back to you within 24 hours.'),
            icon: 'o-check-circle',
            timeout: 5000,
        );
        $this->reset(['contact_name', 'contact_email', 'contact_message']);
    }

    public function subscribe()
    {
        $this->validate([
            'email' => 'required|email',
        ]);

        // Gérer l’abonnement newsletter
        $this->success(
            title: __('Subscribed!'),
            description: __('You are now on the list.'),
            icon: 'o-check-circle',
            timeout: 5000,
        );
        $this->email = '';
    }

    // Dans un composant Livewire (par exemple app/Livewire/Home.php)
    public function getStructuredDataProperty(): string
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => url('/'),
            'description' => __('Learn German online with interactive courses for all levels'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/catalog?search={search_term_string}'),
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    public function render()
    {
        return $this->view()
            ->layoutData([
                'structuredData' => $this->structuredData,
            ]);
    }

}; ?>

{{-- SEO Meta Tags --}}
@section('meta_title', config('app.name') . ' - Learn German Online')
@section('meta_description', 'Learn German online with interactive courses for all levels. Prepare for Goethe, ÖSD, and TELC certifications. Start your German journey today!')
@section('meta_keywords', 'learn German, German course, Goethe certificate, ÖSD, TELC, German grammar, German vocabulary, German online')
@section('og_title', config('app.name') . ' - Learn German Online')
@section('og_description', 'Master the German language with our comprehensive online courses. From A1 to C2, we have the perfect course for you.')
@section('og_image', asset('/images/og-image.jpg'))
@section('canonical_url', url('/'))
@section('meta_robots', 'index,follow')


<div x-data="{ heroAnimate: false }" x-init="setTimeout(() => heroAnimate = true, 100)">

    {{-- ==================== HERO SECTION (texte animé + image à droite) ==================== --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-primary/5 via-base-100 to-secondary/5">
        <div class="container px-4 py-16 mx-auto md:py-24">
            <div class="flex flex-col items-center gap-10 lg:flex-row">
                <!-- Texte : occupe 2/5 sur desktop -->
                <div class="flex-1 text-center lg:text-left lg:w-5/12" x-show="heroAnimate" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0">
                    <h1 class="text-3xl font-bold leading-tight md:text-4xl lg:text-4xl xl:text-6xl">
                        {{ __('Master German') }} <span class="text-primary">{{ __('fast') }}</span><br>
                        {{ __('with Manu') }}
                    </h1>
                    <p class="max-w-2xl mx-auto mt-4 text-base md:text-lg text-base-content/70 lg:mx-0">
                        {{ __('Interactive courses, practical exercises & personalised coaching – from beginner (A1) to expert (C2).') }}
                    </p>
                    <div class="flex flex-wrap justify-center gap-3 mt-6 lg:justify-start">
                        <a href="{{ route('register') }}" class="text-base transition-transform duration-300 shadow-xl btn btn-primary btn-wide hover:scale-105">
                            {{ __('Start free trial') }}
                            <x-icon name="o-arrow-right" class="w-5 h-5 ml-2" />
                        </a>
                        <a href="#courses" class="btn btn-outline btn-wide">{{ __('Explore courses') }}</a>
                    </div>
                    <div class="flex justify-center gap-5 mt-6 text-sm lg:justify-start opacity-70">
                        <span><x-icon name="o-star" class="inline w-4 h-4 text-warning" /> 4.95 (1,200+ reviews)</span>
                        <span><x-icon name="o-users" class="inline w-4 h-4" /> 15k+ {{ __('active learners') }}</span>
                    </div>
                </div>

                <!-- Image : occupe 3/5 sur desktop, rectangulaire arrondie -->
                <div class="flex justify-center lg:w-7/12" x-show="heroAnimate" x-transition:enter="transition ease-out duration-700 delay-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <div class="relative w-full max-w-md mx-auto lg:max-w-none">
                        <div class="absolute inset-0 rounded-2xl bg-primary/20 blur-2xl animate-pulse"></div>
                        <img src="{{ asset('/storage/images/hero-german-learning.png') }}"
                            alt="{{ __('Student learning German online') }}"
                            class="object-cover w-full h-auto shadow-xl rounded-2xl">
                    </div>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-base-200 to-transparent"></div>
    </section>

    {{-- ==================== WHY US (4 cartes MaryUI) ==================== --}}
    <section class="py-20 bg-base-100">
        <div class="container px-4 mx-auto">
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold md:text-4xl">{{ __('Why choose AllemandExpress?') }}</h2>
                <div class="w-24 h-1 mx-auto mt-4 rounded-full bg-primary"></div>
            </div>
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                <x-card class="text-center transition-shadow duration-300 hover:shadow-xl">
                    <div class="flex justify-center mb-4">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary/10">
                            <x-icon name="o-academic-cap" class="w-8 h-8 text-primary" />
                        </div>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold">{{ __('Structured courses') }}</h3>
                    <p class="text-base-content/70">{{ __('CEFR‑aligned curriculum (AC2) with clear milestones and progress tracking.') }}</p>
                </x-card>

                <x-card class="text-center transition-shadow duration-300 hover:shadow-xl">
                    <div class="flex justify-center mb-4">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary/10">
                            <x-icon name="o-beaker" class="w-8 h-8 text-primary" />
                        </div>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold">{{ __('Interactive exercises') }}</h3>
                    <p class="text-base-content/70">{{ __('Gamified quizzes, listening drills & speaking simulations.') }}</p>
                </x-card>

                <x-card class="text-center transition-shadow duration-300 hover:shadow-xl">
                    <div class="flex justify-center mb-4">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary/10">
                            <x-icon name="o-document-check" class="w-8 h-8 text-primary" />
                        </div>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold">{{ __('Official certification') }}</h3>
                    <p class="text-base-content/70">{{ __('Goethe, TELC, TestDaF, ÖSD, ECL — exam‑ready preparation.') }}</p>
                </x-card>

                <x-card class="text-center transition-shadow duration-300 hover:shadow-xl">
                    <div class="flex justify-center mb-4">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-primary/10">
                            <x-icon name="o-user-group" class="w-8 h-8 text-primary" />
                        </div>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold">{{ __('Personal coaching') }}</h3>
                    <p class="text-base-content/70">{{ __('Dedicated teacher feedback & custom learning plans.') }}</p>
                </x-card>
            </div>
        </div>
    </section>

    {{-- ==================== POPULAR COURSES ==================== --}}
    <section id="courses" class="py-20 bg-base-200">
        <div class="container px-4 mx-auto">
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold md:text-4xl">{{ __('Our most‑loved courses') }}</h2>
                <div class="w-24 h-1 mx-auto mt-4 rounded-full bg-primary"></div>
                <p class="mt-4 text-base-content/70">{{ __('Start your German journey – first level is') }} <strong>{{ __('100% free') }}</strong>.</p>
            </div>
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $courses = [
                        (object)['title' => 'A1 – '.__('Beginner'), 'description' => __('First steps: greetings, basic grammar, essential vocabulary for daily life.'), 'duration' => '6 weeks', 'level' => 'A1', 'price' => __('Free')],
                        (object)['title' => 'A2 – '.__('Elementary'), 'description' => __('Express yourself in routine situations, past tense, more confidence speaking.'), 'duration' => '8 weeks', 'level' => 'A2', 'price' => __('Free')],
                        (object)['title' => 'B1 – '.__('Intermediate'), 'description' => __('Work and travel with ease – complex grammar, writing emails, group discussions.'), 'duration' => '12 weeks', 'level' => 'B1', 'price' => __('Free')],
                    ];
                @endphp
                @foreach($courses as $course)
                <x-card class="transition-shadow duration-300 hover:shadow-xl">
                    <div class="flex flex-col h-full">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-2xl font-bold">{{ $course->title }}</h3>
                                <div class="mt-2">
                                    <x-badge :value="$course->price" class="{{ $course->price == __('Free') ? 'badge-success' : 'badge-primary' }} badge-soft" />
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-base-content/60">{{ __('Duration') }}</div>
                                <div class="font-semibold">{{ $course->duration }}</div>
                            </div>
                        </div>
                        <p class="flex-1 mt-4 text-base-content/80">{{ $course->description }}</p>
                        <div class="mt-6">
                            <a href="{{ route('register') }}" class="w-full btn btn-primary">
                                {{ __('See course') }}
                                <x-icon name="o-arrow-right" class="w-4 h-4 ml-2" />
                            </a>
                        </div>
                    </div>
                </x-card>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==================== HOW IT WORKS ==================== --}}
    <section class="py-20 bg-base-100">
        <div class="container px-4 mx-auto">
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold md:text-4xl">{{ __('How it works — 3 steps to fluency') }}</h2>
                <div class="w-24 h-1 mx-auto mt-4 rounded-full bg-primary"></div>
            </div>
            <div class="flex flex-col justify-between gap-8 md:flex-row">
                <div class="flex-1 text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto text-2xl font-bold rounded-full bg-primary/20 text-primary">1</div>
                    <h3 class="mt-4 text-xl font-semibold">{{ __('Free sign‑up') }}</h3>
                    <p class="mt-2 text-base-content/70">{{ __('Create your account in 30 seconds — no credit card required.') }}</p>
                </div>
                <div class="flex-1 text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto text-2xl font-bold rounded-full bg-primary/20 text-primary">2</div>
                    <h3 class="mt-4 text-xl font-semibold">{{ __('Follow smart lessons') }}</h3>
                    <p class="mt-2 text-base-content/70">{{ __('Adaptive exercises, videos, and live sessions with native teachers.') }}</p>
                </div>
                <div class="flex-1 text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto text-2xl font-bold rounded-full bg-primary/20 text-primary">3</div>
                    <h3 class="mt-4 text-xl font-semibold">{{ __('Get certified') }}</h3>
                    <p class="mt-2 text-base-content/70">{{ __('Pass official Goethe/TELC exams and unlock your future.') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== TESTIMONIALS ==================== --}}
    <section class="py-20 bg-base-200">
        <div class="container px-4 mx-auto">
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold md:text-4xl">{{ __('Loved by 15,000+ learners') }}</h2>
                <div class="w-24 h-1 mx-auto mt-4 rounded-full bg-primary"></div>
            </div>
            <div class="grid gap-8 md:grid-cols-3">
                @php
                    $testimonials = [
                        ['name' => 'Anna M.', 'role' => __('Engineer'), 'rating' => 5, 'comment' => __('After 4 months I passed my B1 Goethe exam! The structured lessons made it feel achievable.'), 'avatar' => 'A'],
                        ['name' => 'James K.', 'role' => __('Student'), 'rating' => 5, 'comment' => __('Best online platform for German — interactive, affordable, and the teachers truly care.'), 'avatar' => 'J'],
                        ['name' => 'Sophia L.', 'role' => __('Expat in Berlin'), 'rating' => 5, 'comment' => __('I arrived with zero German, now I speak confidently at work. AllemandExpress changed my life.'), 'avatar' => 'S'],
                    ];
                @endphp
                @foreach($testimonials as $t)
                <x-card class="shadow-md">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="avatar placeholder">
                            <div class="w-12 rounded-full bg-primary text-primary-content">
                                <span class="text-lg">{{ $t['avatar'] }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold">{{ $t['name'] }}</div>
                            <div class="text-sm text-base-content/60">{{ $t['role'] }}</div>
                            <div class="flex text-warning">
                                @for($i=1; $i<=5; $i++)
                                    <x-icon name="o-star" class="w-4 h-4 {{ $i <= $t['rating'] ? 'text-warning' : 'text-base-content/20' }}" />
                                @endfor
                            </div>
                        </div>
                    </div>
                    <p class="italic">"{{ $t['comment'] }}"</p>
                </x-card>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ==================== CTA FINAL ==================== --}}
    <section class="py-20 bg-gradient-to-r from-primary to-secondary">
        <div class="container px-4 mx-auto text-center text-white">
            <h2 class="mb-4 text-3xl font-bold md:text-4xl">{{ __('Ready to speak German?') }}</h2>
            <p class="mb-8 text-xl md:text-2xl opacity-90">{{ __('Join thousands of learners already mastering German with confidence.') }}</p>
            <a href="{{ route('register') }}" class="transition transform bg-white border-0 shadow-lg btn btn-wide text-primary hover:bg-gray-100 hover:scale-105">
                {{ __('Start your free lesson') }}
                <x-icon name="o-arrow-right" class="w-5 h-5 ml-2" />
            </a>
        </div>
    </section>

    {{-- ==================== CONTACT US SECTION (avec image) ==================== --}}
    <section id="contact" class="py-20 bg-base-100">
        <div class="container px-4 mx-auto">
            <div class="max-w-3xl mx-auto mb-16 text-center">
                <h2 class="text-3xl font-bold md:text-4xl">{{ __('Contact us') }}</h2>
                <div class="w-24 h-1 mx-auto mt-4 rounded-full bg-primary"></div>
                <p class="mt-4 text-base-content/70">{{ __('Questions? Our team is here to help you.') }}</p>
            </div>

            <div class="grid items-start max-w-5xl gap-12 mx-auto lg:grid-cols-2">
                {{-- Formulaire de contact --}}
                <div class="p-6 shadow-md bg-base-200 rounded-box md:p-8">
                    <form wire:submit.prevent="submitContact" class="space-y-6">
                        <x-input label="{{ __('Full name *') }}" wire:model="contact_name" placeholder="{{ __('Your full name') }}" icon="o-user" />
                        @error('contact_name') <p class="text-xs text-error">{{ $message }}</p> @enderror

                        <x-input label="{{ __('Email address *') }}" wire:model="contact_email" placeholder="you@example.com" icon="o-envelope" />
                        @error('contact_email') <p class="text-xs text-error">{{ $message }}</p> @enderror

                        <x-textarea label="{{ __('Message *') }}" wire:model="contact_message" placeholder="{{ __('Your message...') }}" rows="5" />
                        @error('contact_message') <p class="text-xs text-error">{{ $message }}</p> @enderror

                        <x-button type="submit" class="w-full btn-primary" :label="__('Send message')" />
                        <p class="mt-2 text-xs text-center text-base-content/60">{{ __('We reply within 24h on business days.') }}</p>
                    </form>
                </div>

                {{-- Informations de contact + image --}}
                <div class="space-y-8">
                    <div class="flex flex-col gap-4 p-6 bg-base-200 rounded-box md:p-8">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-primary/10">
                                <x-icon name="o-envelope" class="w-6 h-6 text-primary" />
                            </div>
                            <span class="text-base-content">hello@allemandexpress.fr</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-primary/10">
                                <x-icon name="o-phone" class="w-6 h-6 text-primary" />
                            </div>
                            <span class="text-base-content">+49 30 12345678</span>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-12 h-12 rounded-full bg-primary/10">
                                <x-icon name="o-map-pin" class="w-6 h-6 text-primary" />
                            </div>
                            <span class="text-base-content">Berlin, Germany</span>
                        </div>
                    </div>
                    <div class="overflow-hidden shadow-md rounded-box">
                        <img src="{{ asset('/storage/images/contact-support-team.png') }}" alt="{{ __('Support team helping students') }}" class="object-cover w-full h-auto">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================== FOOTER (identique à l’exemple, avec clés de traduction) ==================== --}}
    <footer class="pt-16 pb-8 bg-base-200">
        <div class="container px-4 mx-auto">
            <div class="grid gap-8 md:grid-cols-4">
                <div>
                    <h3 class="text-xl font-bold text-primary">AllemandExpress</h3>
                    <p class="mt-2 text-sm text-base-content/70">{{ __('Learn German online, at your own pace.') }}</p>
                </div>
                <div>
                    <h4 class="mb-3 font-semibold">{{ __('Quick links') }}</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="transition hover:text-primary">{{ __('About') }}</a></li>
                        <li><a href="#contact" class="transition hover:text-primary">{{ __('Contact') }}</a></li>
                        <li><a href="#" class="transition hover:text-primary">{{ __('Legal notice') }}</a></li>
                        <li><a href="#" class="transition hover:text-primary">{{ __('Terms of use') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-3 font-semibold">{{ __('Follow us') }}</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="btn btn-ghost btn-circle"><x-icon name="fab.facebook" class="w-5 h-5" /></a>
                        <a href="#" class="btn btn-ghost btn-circle"><x-icon name="fab.twitter" class="w-5 h-5" /></a>
                        <a href="#" class="btn btn-ghost btn-circle"><x-icon name="fab.instagram" class="w-5 h-5" /></a>
                    </div>
                </div>
                <div>
                    <h4 class="mb-3 font-semibold">{{ __('Newsletter') }}</h4>
                    <p class="mb-3 text-sm">{{ __('Receive our tips and exclusive offers.') }}</p>
                    <form wire:submit.prevent="subscribe" class="flex flex-col gap-2 sm:flex-row">
                        <x-input wire:model="email" placeholder="{{ __('Your email') }}" type="email" required class="flex-1" />
                        <x-button type="submit" class="btn-primary">{{ __('Subscribe') }}</x-button>
                    </form>
                </div>
            </div>
            <div class="my-8 divider"></div>
            <div class="text-sm text-center text-base-content/60">
                © {{ date('Y') }} AllemandExpress. {{ __('All rights reserved.') }}
            </div>
        </div>
    </footer>
</div>
