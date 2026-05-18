<?php

namespace App\Livewire\Navigation;

use Livewire\Volt\Component;

new class extends Component {
    public string $currentYear;

    public function mount(): void
    {
        $this->currentYear = date('Y');
    }
};
?>

<footer class="mt-auto bg-white border-t border-gray-200">
    <div class="container px-4 py-8 mx-auto">
        <div class="flex flex-col items-center justify-between gap-4 text-center md:flex-row md:text-left">
            <!-- Copyright -->
            <p class="text-sm text-gray-500">
                &copy; {{ $currentYear }} {{ config('app.name', 'AllemandExpress') }}. {{ __('All rights reserved.') }}
            </p>

            <!-- Language Switcher -->
            <div class="flex items-center gap-4">
                <div class="flex gap-2">
                    <a href="#" wire:click.prevent="switchLanguage('fr')"
                       class="text-sm {{ app()->getLocale() === 'fr' ? 'text-[#FF6B35] font-semibold' : 'text-gray-500 hover:text-[#FF6B35]' }} transition-colors">
                        🇫🇷 Français
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="#" wire:click.prevent="switchLanguage('en')"
                       class="text-sm {{ app()->getLocale() === 'en' ? 'text-[#FF6B35] font-semibold' : 'text-gray-500 hover:text-[#FF6B35]' }} transition-colors">
                        🇬🇧 English
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="#" wire:click.prevent="switchLanguage('de')"
                       class="text-sm {{ app()->getLocale() === 'de' ? 'text-[#FF6B35] font-semibold' : 'text-gray-500 hover:text-[#FF6B35]' }} transition-colors">
                        🇩🇪 Deutsch
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="flex gap-4 text-sm">
                <a href="#" class="text-gray-500 transition-colors hover:text-[#FF6B35]">{{ __('Privacy') }}</a>
                <a href="#" class="text-gray-500 transition-colors hover:text-[#FF6B35]">{{ __('Terms') }}</a>
                <a href="#" class="text-gray-500 transition-colors hover:text-[#FF6B35]">{{ __('Contact') }}</a>
            </div>
        </div>
    </div>
</footer>
