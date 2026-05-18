<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Volt\Component;

new class extends Component {

    public $currentLocale;

    public function mount()
    {
        $this->currentLocale = App::getLocale();
    }

    public function changeLanguage($locale)
    {
        if (in_array($locale, ['en', 'fr'])) {
            Session::put('locale', $locale);
            App::setLocale($locale);
            $this->currentLocale = $locale;

            // Événement pour rafraîchir les autres composants
            $this->dispatch('languageChanged', locale: $locale);

            // Optionnel : petit feedback
            $this->dispatch('notify',
                type: 'success',
                message: __('Language changed to :locale', ['locale' => $locale === 'fr' ? 'Français' : 'English'])
            );

            return redirect()->back();
        }

        return redirect()->back();
    }

}; ?>

<x-dropdown>
    <x-slot:trigger>
        <x-button :label="strtoupper(app()->getLocale())"
        icon="o-language"
        class="btn-ghost"
        responsive />
    </x-slot:trigger>

    <x-menu-item :title="__('French')" link="{{route('language.switch', 'fr')}}" />
    <x-menu-item :title="__('English')" link="{{route('language.switch', 'en')}}" />
</x-dropdown>
