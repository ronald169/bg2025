<?php

namespace App\Livewire\Sidebar;

use Livewire\Volt\Component;

new class extends Component {

    public array $navigation = [];
    public array $socialLinks = [];
    public array $quickLinks = [];

    public function mount(): void
    {
        $this->loadNavigation();
        $this->loadSocialLinks();
        $this->loadQuickLinks();
    }

    private function loadNavigation(): void
    {
        $this->navigation = [
            [
                'name' => __('Features'),
                'href' => '#features',
                'icon' => 'o-sparkles',
                'description' => __('Discover what makes us different')
            ],
            [
                'name' => __('Courses'),
                'href' => '#courses',
                'icon' => 'o-academic-cap',
                'description' => __('Browse our course catalog')
            ],
            [
                'name' => __('How It Works'),
                'href' => '#how-it-works',
                'icon' => 'o-cog-6-tooth',
                'description' => __('Learn about our learning method')
            ],
            [
                'name' => __('Testimonials'),
                'href' => '#testimonials',
                'icon' => 'o-chat-bubble-left-right',
                'description' => __('See what students say about us')
            ],
            [
                'name' => __('Pricing'),
                'href' => '#pricing',
                'icon' => 'o-currency-dollar',
                'description' => __('Choose the plan that fits you')
            ],
            [
                'name' => __('FAQ'),
                'href' => '#faq',
                'icon' => 'o-question-mark-circle',
                'description' => __('Find answers to common questions')
            ],
        ];
    }

    private function loadSocialLinks(): void
    {
        $this->socialLinks = [
            [
                'name' => 'Facebook',
                'url' => 'https://facebook.com/braingenius',
                'icon' => 'o-user',
                'color' => 'hover:text-blue-600'
            ],
            [
                'name' => 'Twitter',
                'url' => 'https://twitter.com/braingenius',
                'icon' => 'o-user',
                'color' => 'hover:text-black'
            ],
            [
                'name' => 'Instagram',
                'url' => 'https://instagram.com/braingenius',
                'icon' => 'o-user',
                'color' => 'hover:text-pink-600'
            ],
            [
                'name' => 'LinkedIn',
                'url' => 'https://linkedin.com/company/braingenius',
                'icon' => 'o-user',
                'color' => 'hover:text-blue-700'
            ],
            [
                'name' => 'YouTube',
                'url' => 'https://youtube.com/@braingenius',
                'icon' => 'o-user',
                'color' => 'hover:text-red-600'
            ],
        ];
    }

    private function loadQuickLinks(): void
    {
        $this->quickLinks = [
            ['name' => __('About Us'), 'href' => '/about'],
            ['name' => __('Blog'), 'href' => '/blog'],
            ['name' => __('Careers'), 'href' => '/careers'],
            ['name' => __('Contact'), 'href' => '/contact'],
            ['name' => __('Privacy Policy'), 'href' => '/privacy'],
            ['name' => __('Terms of Service'), 'href' => '/terms'],
        ];
    }

    public function getContactInfoProperty(): array
    {
        return [
            'email' => 'support@braingenius.com',
            'phone' => '+33 1 23 45 67 89',
            'address' => __('123 Learning Street, Paris, France'),
        ];
    }

    public function getAppStoreLinksProperty(): array
    {
        return [
            [
                'name' => 'App Store',
                'url' => '#',
                'icon' => 'o-user',
                'image' => '/images/app-store-badge.svg'
            ],
            [
                'name' => 'Google Play',
                'url' => '#',
                'icon' => 'o-user',
                'image' => '/images/google-play-badge.svg'
            ],
        ];
    }
}; ?>

<div class="flex flex-col h-full overflow-y-auto bg-white border-r border-gray-200">
    <!-- Logo Section -->
    <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-primary-50 to-white">
        <a href="/" class="flex items-center space-x-3 group">
            <div class="flex items-center justify-center w-12 h-12 transition-all duration-300 transform shadow-lg bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl group-hover:shadow-xl group-hover:scale-110">
                <x-icon name="o-academic-cap" class="text-white w-7 h-7" />
            </div>
            <div>
                <span class="block text-xl font-bold text-gray-900">BrainGenius</span>
                <span class="text-xs text-gray-500">{{ __('Your Learning Companion') }}</span>
            </div>
        </a>

        <!-- Trust Badge -->
        <div class="p-3 mt-4 rounded-lg bg-primary-50">
            <div class="flex items-center space-x-2">
                <x-icon name="o-shield-check" class="w-5 h-5 text-primary-600" />
                <span class="text-sm font-medium text-gray-700">{{ __('Trusted by 50,000+ students') }}</span>
            </div>
            <div class="flex items-center mt-2 space-x-1">
                @for($i = 1; $i <= 5; $i++)
                    <x-icon name="o-star" class="w-4 h-4 text-yellow-400" />
                @endfor
                <span class="ml-1 text-xs text-gray-500">(4.9)</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="flex-1 p-4 space-y-1">
        <p class="px-4 mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">
            {{ __('Menu') }}
        </p>

        @foreach($navigation as $item)
        <a href="{{ $item['href'] }}"
           wire:navigate
           class="relative flex items-center px-4 py-3 text-gray-700 transition-all duration-200 rounded-xl hover:bg-primary-50 hover:text-primary-600 group">
            <x-icon :name="$item['icon']"
                    class="w-5 h-5 mr-3 text-gray-400 transition-colors group-hover:text-primary-500" />
            <div class="flex-1">
                <span class="font-medium">{{ $item['name'] }}</span>
                <p class="text-xs text-gray-500 group-hover:text-primary-400">{{ $item['description'] }}</p>
            </div>
            <x-icon name="o-chevron-right" class="w-4 h-4 text-gray-400 transition-all duration-200 opacity-0 group-hover:text-primary-500 group-hover:opacity-100" />
        </a>
        @endforeach

        <!-- Divider -->
        <div class="my-4 border-t border-gray-200"></div>

        <!-- Quick Links -->
        <p class="px-4 mb-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">
            {{ __('Quick Links') }}
        </p>

        <div class="grid grid-cols-2 gap-2 px-4">
            @foreach($quickLinks as $link)
            <a href="{{ $link['href'] }}"
               wire:navigate
               class="text-sm text-gray-600 transition-colors hover:text-primary-600">
                {{ $link['name'] }}
            </a>
            @endforeach
        </div>

        <!-- Divider -->
        <div class="my-4 border-t border-gray-200"></div>

        <!-- Contact Info -->
        <div class="px-4 space-y-3">
            <p class="text-xs font-semibold tracking-wider text-gray-500 uppercase">
                {{ __('Contact') }}
            </p>

            <div class="flex items-center space-x-3 text-sm text-gray-600">
                <x-icon name="o-envelope" class="w-4 h-4 text-gray-400" />
                <a href="mailto:{{ $this->contactInfo['email'] }}"
                   class="transition-colors hover:text-primary-600">
                    {{ $this->contactInfo['email'] }}
                </a>
            </div>

            <div class="flex items-center space-x-3 text-sm text-gray-600">
                <x-icon name="o-phone" class="w-4 h-4 text-gray-400" />
                <a href="tel:{{ $this->contactInfo['phone'] }}"
                   class="transition-colors hover:text-primary-600">
                    {{ $this->contactInfo['phone'] }}
                </a>
            </div>

            <div class="flex items-start space-x-3 text-sm text-gray-600">
                <x-icon name="o-map-pin" class="w-4 h-4 text-gray-400 mt-0.5" />
                <span>{{ $this->contactInfo['address'] }}</span>
            </div>
        </div>
    </nav>

    <!-- Footer Section -->
    <div class="p-4 border-t border-gray-200 bg-gray-50">
        <!-- App Store Badges -->
        <div class="flex mb-4 space-x-2">
            @foreach($this->appStoreLinks as $store)
            <a href="{{ $store['url'] }}"
               class="flex items-center justify-center flex-1 px-3 py-2 text-xs font-semibold text-white transition-colors bg-gray-900 rounded-lg hover:bg-gray-800">
                <x-icon :name="$store['icon']" class="w-4 h-4 mr-2" />
                {{ $store['name'] }}
            </a>
            @endforeach
        </div>

        <!-- Social Links -->
        <div class="flex justify-center mb-4 space-x-4">
            @foreach($socialLinks as $social)
            <a href="{{ $social['url'] }}"
               target="_blank"
               rel="noopener noreferrer"
               class="text-gray-400 {{ $social['color'] }} transition-colors">
                <x-icon :name="$social['icon']" class="w-5 h-5" />
            </a>
            @endforeach
        </div>

        <!-- Copyright -->
        <p class="text-xs text-center text-gray-500">
            &copy; {{ date('Y') }} BrainGenius. <br>
            {{ __('All rights reserved.') }}
        </p>

        <!-- Language Switcher -->
        <div class="flex justify-center mt-3 space-x-2">
            <a href="{{ route('language.switch', 'fr') }}"
               class="text-xs px-2 py-1 rounded {{ app()->getLocale() === 'fr' ? 'bg-primary-100 text-primary-600 font-semibold' : 'text-gray-500 hover:text-gray-700' }}">
                🇫🇷 FR
            </a>
            <a href="{{ route('language.switch', 'en') }}"
               class="text-xs px-2 py-1 rounded {{ app()->getLocale() === 'en' ? 'bg-primary-100 text-primary-600 font-semibold' : 'text-gray-500 hover:text-gray-700' }}">
                🇬🇧 EN
            </a>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Smooth hover effects */
.nav-link {
    transition: all 0.2s ease-in-out;
}

.nav-link:hover {
    transform: translateX(4px);
}

/* Active state */
.nav-link.active {
    @apply bg-primary-50 text-primary-600;
}

.nav-link.active .nav-icon {
    @apply text-primary-500;
}

/* Custom scrollbar for sidebar */
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>
@endpush
