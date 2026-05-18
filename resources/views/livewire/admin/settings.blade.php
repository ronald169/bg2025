<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Mary\Traits\Toast;

new
#[Title('Systemeinstellungen - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use Toast;

    #[Url(as: 'tab', history: true)]
    public string $activeTab = 'general';

    // General Settings
    public $site_name = '';
    public $site_description = '';
    public $contact_email = '';
    public $contact_phone = '';
    public $contact_address = '';
    public $timezone = 'UTC';
    public $default_language = 'de';
    public $maintenance_mode = false;

    // Payment Settings
    public $currency = 'EUR';
    public $tax_rate = 0;
    public $stripe_key = '';
    public $stripe_secret = '';
    public $stripe_webhook_secret = '';
    public $enable_free_trial = true;
    public $free_trial_days = 7;

    // Email Settings
    public $mail_driver = 'smtp';
    public $mail_host = '';
    public $mail_port = 587;
    public $mail_username = '';
    public $mail_password = '';
    public $mail_encryption = 'tls';
    public $mail_from_address = '';
    public $mail_from_name = '';

    // Security Settings
    public $session_lifetime = 120;
    public $login_attempts = 5;
    public $lockout_minutes = 15;
    public $two_factor_auth = false;
    public $password_expiry_days = 90;

    public $timezones = [
        'UTC' => 'UTC',
        'Europe/Paris' => 'Paris (GMT+1)',
        'Europe/Berlin' => 'Berlin (GMT+1)',
        'America/New_York' => 'New York (GMT-5)',
        'Asia/Tokyo' => 'Tokyo (GMT+9)',
    ];

    public $languages = [
        ['id' => 'de', 'name' => 'Deutsch', 'flag' => '🇩🇪'],
        ['id' => 'fr', 'name' => 'Français', 'flag' => '🇫🇷'],
        ['id' => 'en', 'name' => 'English', 'flag' => '🇬🇧'],
    ];

    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        // General settings
        $this->site_name = config('app.name', 'AllemandExpress');
        $this->site_description = config('app.description', '');
        $this->contact_email = config('mail.from.address', '');
        $this->contact_phone = config('app.contact_phone', '');
        $this->contact_address = config('app.contact_address', '');
        $this->timezone = config('app.timezone', 'UTC');
        $this->default_language = config('app.locale', 'de');
        $this->maintenance_mode = app()->isDownForMaintenance();

        // Payment settings
        $this->currency = config('app.currency', 'EUR');
        $this->tax_rate = config('app.tax_rate', 0);
        $this->stripe_key = config('services.stripe.key', '');
        $this->stripe_secret = config('services.stripe.secret', '');
        $this->stripe_webhook_secret = config('services.stripe.webhook_secret', '');
        $this->enable_free_trial = config('app.enable_free_trial', true);
        $this->free_trial_days = config('app.free_trial_days', 7);

        // Email settings
        $this->mail_driver = config('mail.default', 'smtp');
        $this->mail_host = config('mail.mailers.smtp.host', '');
        $this->mail_port = config('mail.mailers.smtp.port', 587);
        $this->mail_username = config('mail.mailers.smtp.username', '');
        $this->mail_password = config('mail.mailers.smtp.password', '');
        $this->mail_encryption = config('mail.mailers.smtp.encryption', 'tls');
        $this->mail_from_address = config('mail.from.address', '');
        $this->mail_from_name = config('mail.from.name', '');

        // Security settings
        $this->session_lifetime = config('session.lifetime', 120);
        $this->login_attempts = config('auth.throttle.attempts', 5);
        $this->lockout_minutes = config('auth.throttle.lockout_minutes', 15);
        $this->two_factor_auth = config('auth.two_factor', false);
        $this->password_expiry_days = config('auth.password_expiry_days', 90);
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'site_name' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'contact_phone' => 'nullable|string|max:20',
            'timezone' => 'required|string',
            'default_language' => 'required|in:de,fr,en',
        ]);

        // Logique de sauvegarde
        $this->updateEnvFile('APP_NAME', $this->site_name);
        $this->updateEnvFile('APP_TIMEZONE', $this->timezone);
        $this->updateEnvFile('APP_LOCALE', $this->default_language);

        $this->success('Allgemeine Einstellungen wurden gespeichert! 🎉');
    }

    public function savePayment(): void
    {
        $this->validate([
            'currency' => 'required|string|size:3',
            'tax_rate' => 'numeric|min:0|max:100',
            'free_trial_days' => 'integer|min:0|max:30',
        ]);

        $this->updateEnvFile('APP_CURRENCY', $this->currency);
        $this->updateEnvFile('APP_TAX_RATE', $this->tax_rate);
        $this->updateEnvFile('APP_ENABLE_FREE_TRIAL', $this->enable_free_trial ? 'true' : 'false');
        $this->updateEnvFile('APP_FREE_TRIAL_DAYS', $this->free_trial_days);

        $this->updateEnvFile('STRIPE_KEY', $this->stripe_key);
        $this->updateEnvFile('STRIPE_SECRET', $this->stripe_secret);
        $this->updateEnvFile('STRIPE_WEBHOOK_SECRET', $this->stripe_webhook_secret);

        $this->success('Zahlungseinstellungen wurden gespeichert! 💰');
    }

    public function saveEmail(): void
    {
        $this->validate([
            'mail_host' => 'required_if:mail_driver,smtp|nullable|string',
            'mail_port' => 'required_if:mail_driver,smtp|nullable|integer',
            'mail_from_address' => 'required|email',
            'mail_from_name' => 'required|string',
        ]);

        $this->updateEnvFile('MAIL_MAILER', $this->mail_driver);
        $this->updateEnvFile('MAIL_HOST', $this->mail_host);
        $this->updateEnvFile('MAIL_PORT', $this->mail_port);
        $this->updateEnvFile('MAIL_USERNAME', $this->mail_username);
        $this->updateEnvFile('MAIL_PASSWORD', $this->mail_password);
        $this->updateEnvFile('MAIL_ENCRYPTION', $this->mail_encryption);
        $this->updateEnvFile('MAIL_FROM_ADDRESS', $this->mail_from_address);
        $this->updateEnvFile('MAIL_FROM_NAME', $this->mail_from_name);

        $this->success('E-Mail-Einstellungen wurden gespeichert! 📧');
    }

    public function saveSecurity(): void
    {
        $this->validate([
            'session_lifetime' => 'integer|min:15|max:1440',
            'login_attempts' => 'integer|min:1|max:20',
            'lockout_minutes' => 'integer|min:1|max:60',
            'password_expiry_days' => 'integer|min:0|max:365',
        ]);

        $this->updateEnvFile('SESSION_LIFETIME', $this->session_lifetime);
        $this->updateEnvFile('AUTH_THROTTLE_ATTEMPTS', $this->login_attempts);
        $this->updateEnvFile('AUTH_THROTTLE_LOCKOUT_MINUTES', $this->lockout_minutes);
        $this->updateEnvFile('AUTH_TWO_FACTOR', $this->two_factor_auth ? 'true' : 'false');
        $this->updateEnvFile('AUTH_PASSWORD_EXPIRY_DAYS', $this->password_expiry_days);

        $this->success('Sicherheitseinstellungen wurden gespeichert! 🔒');
    }

    public function sendTestEmail(): void
    {
        try {
            \Illuminate\Support\Facades\Mail::raw('Test-E-Mail von AllemandExpress', function ($message) {
                $message->to($this->mail_from_address)
                        ->subject('Test-E-Mail');
            });
            $this->success('Test-E-Mail wurde gesendet! 📨');
        } catch (\Exception $e) {
            $this->error('Fehler beim Senden der Test-E-Mail: ' . $e->getMessage());
        }
    }

    public function clearCache(): void
    {
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
        \Artisan::call('view:clear');
        \Artisan::call('route:clear');
        \Artisan::call('optimize:clear');
        $this->success('Alle Caches wurden geleert! 🧹');
    }

    public function toggleMaintenance(): void
    {
        if ($this->maintenance_mode) {
            \Artisan::call('down');
            $this->maintenance_mode = true;
            $this->success('Wartungsmodus aktiviert! ⚠️');
        } else {
            \Artisan::call('up');
            $this->maintenance_mode = false;
            $this->success('Wartungsmodus deaktiviert! ✅');
        }
    }

    private function updateEnvFile($key, $value): void
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $content = file_get_contents($path);
            $pattern = "/^{$key}=.*/m";
            $newLine = "{$key}={$value}";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $newLine, $content);
            } else {
                $content .= "\n{$newLine}";
            }

            file_put_contents($path, $content);
        }
    }
}
?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">⚙️ {{ __('Systemeinstellungen') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Konfiguriere deine Plattform-Einstellungen') }}</p>
            </div>
            <x-button wire:click="clearCache" icon="o-arrow-path" class="btn-outline">
                {{ __('Cache leeren') }}
            </x-button>
        </div>

        <!-- Tabs - Responsive -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex flex-wrap gap-2 sm:gap-4">
                <button wire:click="$set('activeTab', 'general')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $activeTab === 'general' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    ⚙️ {{ __('Allgemein') }}
                </button>
                <button wire:click="$set('activeTab', 'payment')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $activeTab === 'payment' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    💰 {{ __('Zahlungen') }}
                </button>
                <button wire:click="$set('activeTab', 'email')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $activeTab === 'email' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    📧 {{ __('E-Mail') }}
                </button>
                <button wire:click="$set('activeTab', 'security')"
                        class="px-3 py-2 text-sm font-medium rounded-lg transition
                               {{ $activeTab === 'security' ? 'bg-[#FF6B35] text-white' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' }}">
                    🔒 {{ __('Sicherheit') }}
                </button>
            </nav>
        </div>

        <!-- General Tab -->
        @if($activeTab === 'general')
        <form wire:submit="saveGeneral" class="space-y-5">
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-globe-alt" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Allgemeine Einstellungen') }}</h2>
                </div>

                <div class="space-y-4">
                    <x-input
                        wire:model="site_name"
                        label="{{ __('Seitenname') }}"
                        placeholder="{{ config('app.name') }}"
                        icon="o-building-storefront"
                        required />

                    <x-textarea
                        wire:model="site_description"
                        label="{{ __('Seitenbeschreibung') }}"
                        placeholder="{{ __('Beschreibung für Suchmaschinen') }}"
                        rows="2"
                        icon="o-document-text" />

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            wire:model="contact_email"
                            type="email"
                            label="{{ __('Kontakt-E-Mail') }}"
                            placeholder="contact@example.com"
                            icon="o-envelope"
                            required />

                        <x-input
                            wire:model="contact_phone"
                            label="{{ __('Kontakt-Telefon') }}"
                            placeholder="+49 123 456789"
                            icon="o-phone" />
                    </div>

                    <x-input
                        wire:model="contact_address"
                        label="{{ __('Adresse') }}"
                        placeholder="{{ __('Ihre Adresse') }}"
                        icon="o-map-pin" />

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select
                            wire:model="timezone"
                            label="{{ __('Zeitzone') }}"
                            :options="$timezones"
                            icon="o-clock" />

                        <x-select
                            wire:model="default_language"
                            label="{{ __('Standardsprache') }}"
                            :options="$languages"
                            icon="o-language" />
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                        <div>
                            <label class="font-medium text-gray-900">{{ __('Wartungsmodus') }}</label>
                            <p class="text-xs text-gray-500">{{ __('Nur Administratoren können auf die Seite zugreifen') }}</p>
                        </div>
                        <button type="button"
                                wire:click="toggleMaintenance"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                                       {{ $maintenance_mode ? 'bg-[#FF6B35]' : 'bg-gray-300' }}">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                         {{ $maintenance_mode ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>
                </div>
            </x-card>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Einstellungen speichern') }}
                </button>
            </div>
        </form>

        <!-- Payment Tab -->
        @elseif($activeTab === 'payment')
        <form wire:submit="savePayment" class="space-y-5">
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-currency-euro" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Zahlungseinstellungen') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select
                            wire:model="currency"
                            label="{{ __('Währung') }}"
                            :options="[
                                ['id' => 'EUR', 'name' => 'EUR - Euro (€)'],
                                ['id' => 'USD', 'name' => 'USD - US Dollar ($)'],
                                ['id' => 'GBP', 'name' => 'GBP - British Pound (£)']
                            ]"
                            icon="o-currency-euro" />

                        <x-input
                            wire:model="tax_rate"
                            type="number"
                            step="0.01"
                            label="{{ __('Steuersatz (%)') }}"
                            placeholder="19"
                            icon="o-chart-bar" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                            <div>
                                <label class="font-medium text-gray-900">{{ __('Kostenlose Testversion') }}</label>
                                <p class="text-xs text-gray-500">{{ __('Neue Benutzer erhalten Testtage') }}</p>
                            </div>
                            <button type="button"
                                    wire:click="$set('enable_free_trial', !$enable_free_trial)"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                                           {{ $enable_free_trial ? 'bg-[#FF6B35]' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                             {{ $enable_free_trial ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        <x-input
                            wire:model="free_trial_days"
                            type="number"
                            min="0"
                            max="30"
                            label="{{ __('Testtage') }}"
                            placeholder="7"
                            icon="o-calendar" />
                    </div>

                    <div class="pt-4">
                        <h3 class="mb-3 font-medium text-gray-900">{{ __('Stripe Integration') }}</h3>
                        <div class="space-y-3">
                            <x-input
                                wire:model="stripe_key"
                                label="{{ __('Stripe Publishable Key') }}"
                                placeholder="pk_live_..."
                                icon="o-credit-card" />

                            <x-input
                                wire:model="stripe_secret"
                                type="password"
                                label="{{ __('Stripe Secret Key') }}"
                                placeholder="sk_live_..."
                                icon="o-key" />

                            <x-input
                                wire:model="stripe_webhook_secret"
                                type="password"
                                label="{{ __('Stripe Webhook Secret') }}"
                                placeholder="whsec_..."
                                icon="o-link" />
                        </div>
                    </div>
                </div>
            </x-card>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Einstellungen speichern') }}
                </button>
            </div>
        </form>

        <!-- Email Tab -->
        @elseif($activeTab === 'email')
        <form wire:submit="saveEmail" class="space-y-5">
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-envelope" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('E-Mail-Einstellungen') }}</h2>
                </div>

                <div class="space-y-4">
                    <x-select
                        wire:model="mail_driver"
                        label="{{ __('Mail-Treiber') }}"
                        :options="[
                            ['id' => 'smtp', 'name' => 'SMTP'],
                            ['id' => 'sendmail', 'name' => 'Sendmail'],
                            ['id' => 'log', 'name' => 'Log (zum Testen)']
                        ]"
                        icon="o-cog" />

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            wire:model="mail_host"
                            label="{{ __('SMTP Host') }}"
                            placeholder="smtp.gmail.com"
                            icon="o-server" />

                        <x-input
                            wire:model="mail_port"
                            type="number"
                            label="{{ __('SMTP Port') }}"
                            placeholder="587"
                            icon="o-numbered-list" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input
                            wire:model="mail_username"
                            label="{{ __('SMTP Benutzername') }}"
                            placeholder="user@example.com"
                            icon="o-user" />

                        <x-input
                            wire:model="mail_password"
                            type="password"
                            label="{{ __('SMTP Passwort') }}"
                            placeholder="••••••••"
                            icon="o-key" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select
                            wire:model="mail_encryption"
                            label="{{ __('Verschlüsselung') }}"
                            :options="[
                                ['id' => 'tls', 'name' => 'TLS'],
                                ['id' => 'ssl', 'name' => 'SSL']
                            ]"
                            icon="o-lock-closed" />

                        <x-input
                            wire:model="mail_from_address"
                            type="email"
                            label="{{ __('Absender-E-Mail') }}"
                            placeholder="noreply@example.com"
                            icon="o-envelope"
                            required />

                        <x-input
                            wire:model="mail_from_name"
                            label="{{ __('Absendername') }}"
                            placeholder="AllemandExpress"
                            icon="o-user"
                            required />
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-between">
                <button type="button" wire:click="sendTestEmail" class="px-4 py-2 text-[#FF6B35] border border-[#FF6B35] rounded-lg hover:bg-orange-50 transition">
                    <x-icon name="o-paper-airplane" class="inline w-4 h-4 mr-1" />
                    {{ __('Test-E-Mail senden') }}
                </button>
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Einstellungen speichern') }}
                </button>
            </div>
        </form>

        <!-- Security Tab -->
        @elseif($activeTab === 'security')
        <form wire:submit="saveSecurity" class="space-y-5">
            <x-card class="border-0 shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-shield-check" class="w-5 h-5 text-[#FF6B35]" />
                    <h2 class="font-semibold text-gray-900">{{ __('Sicherheitseinstellungen') }}</h2>
                </div>

                <div class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <x-input
                            wire:model="session_lifetime"
                            type="number"
                            min="15"
                            max="1440"
                            label="{{ __('Session-Lebensdauer (Minuten)') }}"
                            placeholder="120"
                            icon="o-clock" />

                        <x-input
                            wire:model="login_attempts"
                            type="number"
                            min="1"
                            max="20"
                            label="{{ __('Max. Login-Versuche') }}"
                            placeholder="5"
                            icon="o-arrow-path" />

                        <x-input
                            wire:model="lockout_minutes"
                            type="number"
                            min="1"
                            max="60"
                            label="{{ __('Sperrzeit (Minuten)') }}"
                            placeholder="15"
                            icon="o-lock-closed" />
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                            <div>
                                <label class="font-medium text-gray-900">{{ __('Zwei-Faktor-Authentifizierung') }}</label>
                                <p class="text-xs text-gray-500">{{ __('Erhöhte Sicherheit für Administratoren') }}</p>
                            </div>
                            <button type="button"
                                    wire:click="$set('two_factor_auth', !$two_factor_auth)"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none
                                           {{ $two_factor_auth ? 'bg-[#FF6B35]' : 'bg-gray-300' }}">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                             {{ $two_factor_auth ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </div>

                        <x-input
                            wire:model="password_expiry_days"
                            type="number"
                            min="0"
                            max="365"
                            label="{{ __('Passwortablauf (Tage)') }}"
                            placeholder="90"
                            icon="o-calendar" />
                    </div>
                </div>
            </x-card>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-check" class="inline w-4 h-4 mr-1" />
                    {{ __('Einstellungen speichern') }}
                </button>
            </div>
        </form>
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Les modifications des fichiers .env nécessitent un redéploiement pour être appliquées.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
