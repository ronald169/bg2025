<?php

namespace App\Livewire;

use Livewire\Volt\Component;

new class extends Component {

    public $currentYear;
    public $quickLinks = [];
    public $resources = [];
    public $legal = [];
    public $socialLinks = [];

    public function mount(): void
    {
        $this->currentYear = date('Y');
        $this->loadQuickLinks();
        $this->loadResources();
        $this->loadLegal();
        $this->loadSocialLinks();
    }

    public function loadQuickLinks(): void
    {
        $this->quickLinks = [
            ['name' => __('About Us'), 'route' => '/about'],
            ['name' => __('Features'), 'route' => '#features'],
            ['name' => __('Courses'), 'route' => '#courses'],
            ['name' => __('Pricing'), 'route' => '#pricing'],
            ['name' => __('FAQ'), 'route' => '#faq'],
            ['name' => __('Contact'), 'route' => '/contact'],
        ];
    }

    public function loadResources(): void
    {
        $this->resources = [
            ['name' => __('Blog'), 'route' => '/blog'],
            ['name' => __('Help Center'), 'route' => '/help'],
            ['name' => __('Community'), 'route' => '/community'],
            ['name' => __('Success Stories'), 'route' => '/success-stories'],
            ['name' => __('Teacher Resources'), 'route' => '/teachers'],
            ['name' => __('Parent Guide'), 'route' => '/parents'],
        ];
    }

    public function loadLegal(): void
    {
        $this->legal = [
            ['name' => __('Privacy Policy'), 'route' => '/privacy'],
            ['name' => __('Terms of Service'), 'route' => '/terms'],
            ['name' => __('Cookie Policy'), 'route' => '/cookies'],
            ['name' => __('GDPR'), 'route' => '/gdpr'],
            ['name' => __('Legal Notice'), 'route' => '/legal'],
        ];
    }

    public function loadSocialLinks(): void
    {
        $this->socialLinks = [
            ['name' => 'Facebook', 'url' => 'https://facebook.com/braingenius', 'icon' => 'o-user', 'color' => 'hover:bg-blue-600'],
            ['name' => 'Twitter', 'url' => 'https://twitter.com/braingenius', 'icon' => 'o-user', 'color' => 'hover:bg-black'],
            ['name' => 'Instagram', 'url' => 'https://instagram.com/braingenius', 'icon' => 'o-user', 'color' => 'hover:bg-pink-600'],
            ['name' => 'LinkedIn', 'url' => 'https://linkedin.com/company/braingenius', 'icon' => 'o-user', 'color' => 'hover:bg-blue-700'],
            ['name' => 'YouTube', 'url' => 'https://youtube.com/@braingenius', 'icon' => 'o-user', 'color' => 'hover:bg-red-600'],
        ];
    }

    public function subscribeToNewsletter($email): void
    {
        // Logique d'inscription à la newsletter
        // À implémenter selon ton système
        $this->dispatch('notify', message: __('Thank you for subscribing!'), type: 'success');
    }

    public function getContactInfoProperty()
    {
        return [
            'email' => 'support@braingenius.com',
            'phone' => '+33 1 23 45 67 89',
            'address' => __('123 Learning Street, Paris, France'),
            'hours' => __('Monday - Friday, 9am - 6pm CET'),
        ];
    }

    public function getAppStoreLinksProperty()
    {
        return [
            ['name' => 'App Store', 'url' => '#', 'icon' => 'o-apple'],
            ['name' => 'Google Play', 'url' => '#', 'icon' => 'o-google-play'],
        ];
    }
}; ?>

<footer class="text-white bg-gray-900 border-t border-gray-800">
    <div class="container px-4 py-12 mx-auto">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-5">
            <!-- Brand Column -->
            <div class="lg:col-span-1">
                <a href="/" wire:navigate class="flex items-center mb-4 space-x-3 group">
                    <div class="flex items-center justify-center w-12 h-12 transition shadow-lg bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl group-hover:shadow-xl">
                        <x-icon name="o-academic-cap" class="text-white w-7 h-7" />
                    </div>
                    <span class="text-2xl font-bold text-white">BrainGenius</span>
                </a>
                <p class="mb-6 text-sm leading-relaxed text-gray-400">
                    {{ __('Your personal learning companion for middle and high school.') }}
                </p>

                <div class="space-y-3 text-sm text-gray-400">
                    <div class="flex items-start space-x-3">
                        <x-icon name="o-envelope" class="w-5 h-5 text-gray-500 mt-0.5" />
                        <a href="mailto:{{ $this->contactInfo['email'] }}" class="transition-colors hover:text-primary-400">
                            {{ $this->contactInfo['email'] }}
                        </a>
                    </div>
                    <div class="flex items-start space-x-3">
                        <x-icon name="o-phone" class="w-5 h-5 text-gray-500 mt-0.5" />
                        <a href="tel:{{ $this->contactInfo['phone'] }}" class="transition-colors hover:text-primary-400">
                            {{ $this->contactInfo['phone'] }}
                        </a>
                    </div>
                    <div class="flex items-start space-x-3">
                        <x-icon name="o-map-pin" class="w-5 h-5 text-gray-500 mt-0.5" />
                        <span>{{ $this->contactInfo['address'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="mb-4 text-lg font-semibold text-white">{{ __('Quick Links') }}</h3>
                <ul class="space-y-2">
                    @foreach($quickLinks as $link)
                    <li>
                        <a href="{{ $link['route'] }}" wire:navigate class="inline-flex items-center text-gray-400 transition-colors hover:text-white group">
                            <x-icon name="o-chevron-right" class="w-4 h-4 mr-2 transition-all -translate-x-2 opacity-0 group-hover:opacity-100 group-hover:translate-x-0" />
                            {{ $link['name'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <!-- Resources -->
            <div>
                <h3 class="mb-4 text-lg font-semibold text-white">{{ __('Resources') }}</h3>
                <ul class="space-y-2">
                    @foreach($resources as $resource)
                    <li>
                        <a href="{{ $resource['route'] }}" wire:navigate class="inline-flex items-center text-gray-400 transition-colors hover:text-white group">
                            <x-icon name="o-chevron-right" class="w-4 h-4 mr-2 transition-all -translate-x-2 opacity-0 group-hover:opacity-100 group-hover:translate-x-0" />
                            {{ $resource['name'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <!-- Legal -->
            <div>
                <h3 class="mb-4 text-lg font-semibold text-white">{{ __('Legal') }}</h3>
                <ul class="space-y-2">
                    @foreach($legal as $item)
                    <li>
                        <a href="{{ $item['route'] }}" wire:navigate class="inline-flex items-center text-gray-400 transition-colors hover:text-white group">
                            <x-icon name="o-chevron-right" class="w-4 h-4 mr-2 transition-all -translate-x-2 opacity-0 group-hover:opacity-100 group-hover:translate-x-0" />
                            {{ $item['name'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <!-- Newsletter & Social -->
            <div>
                <h3 class="mb-4 text-lg font-semibold text-white">{{ __('Stay Updated') }}</h3>
                <p class="mb-4 text-sm text-gray-400">
                    {{ __('Subscribe to our newsletter for learning tips and exclusive offers.') }}
                </p>

                <form wire:submit.prevent="subscribeToNewsletter($event.target.email.value)" class="mb-6">
                    <div class="flex flex-col space-y-2">
                        <x-input type="email" name="email" placeholder="{{ __('Your email address') }}"
                                 class="w-full text-white bg-gray-800 border-gray-700 rounded-lg focus:border-primary-500 focus:ring-primary-500" />
                        <x-button type="submit" class="justify-center w-full btn-primary">
                            {{ __('Subscribe') }}
                        </x-button>
                    </div>
                </form>

                <div class="flex mb-6 space-x-2">
                    @foreach($this->appStoreLinks as $store)
                    <a href="{{ $store['url'] }}"
                       class="flex items-center justify-center flex-1 px-3 py-2 text-xs font-semibold transition-colors bg-gray-800 border border-gray-700 rounded-lg hover:bg-gray-700">
                        <x-icon name="o-user" class="w-5 h-5 mr-2 text-gray-300" />
                        <span class="text-white">{{ $store['name'] }}</span>
                    </a>
                    @endforeach
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach($socialLinks as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                       class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center text-gray-400 hover:text-white {{ $social['color'] }} hover:bg-opacity-100 transition-all duration-300 transform hover:scale-110 border border-gray-700">
                        <x-icon :name="$social['icon']" class="w-5 h-5" />
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-gray-800">
        <div class="container px-4 py-6 mx-auto">
            <div class="flex flex-col text-sm text-gray-400 md:flex-row md:items-center md:justify-between">
                <div>
                    &copy; {{ $currentYear }} BrainGenius. {{ __('All rights reserved.') }}
                </div>
                <div class="flex flex-wrap gap-4 mt-4 md:mt-0">
                    <a href="/privacy" wire:navigate class="transition-colors hover:text-white">{{ __('Privacy Policy') }}</a>
                    <a href="/terms" wire:navigate class="transition-colors hover:text-white">{{ __('Terms of Service') }}</a>
                    <a href="/cookies" wire:navigate class="transition-colors hover:text-white">{{ __('Cookie Policy') }}</a>
                </div>
            </div>
        </div>
    </div>
</footer>
