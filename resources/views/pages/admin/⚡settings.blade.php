<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\Url;
use Mary\Traits\Toast;

new
#[Title('System Settings - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    #[Url(as: 'tab', history: true)]
    public string $activeTab = 'general';

    // General Settings
    public string $site_name = '';
    public string $site_description = '';
    public string $contact_email = '';
    public string $contact_phone = '';
    public string $contact_address = '';
    public string $timezone = 'UTC';
    public string $default_language = 'de';
    public bool $maintenance_mode = false;

    // Payment Settings
    public string $currency = 'EUR';
    public float $tax_rate = 0;
    public string $stripe_key = '';
    public string $stripe_secret = '';
    public string $stripe_webhook_secret = '';
    public bool $enable_free_trial = true;
    public int $free_trial_days = 7;

    // Email Settings
    public string $mail_driver = 'smtp';
    public string $mail_host = '';
    public int $mail_port = 587;
    public string $mail_username = '';
    public string $mail_password = '';
    public string $mail_encryption = 'tls';
    public string $mail_from_address = '';
    public string $mail_from_name = '';

    // Security Settings
    public int $session_lifetime = 120;
    public int $login_attempts = 5;
    public int $lockout_minutes = 15;
    public bool $two_factor_auth = false;
    public int $password_expiry_days = 90;

    public array $timezones = [
        ['id' => 'UTC', 'name' => 'UTC'],
        ['id' => 'Europe/Paris', 'name' => 'Paris (GMT+1)'],
        ['id' => 'Europe/Berlin', 'name' => 'Berlin (GMT+1)'],
        ['id' => 'America/New_York', 'name' => 'New York (GMT-5)'],
        ['id' => 'Asia/Tokyo', 'name' => 'Tokyo (GMT+9)'],
    ];

    public array $languages = [
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
        // General
        $this->site_name = config('app.name', 'AllemandExpress');
        $this->site_description = config('app.description', '');
        $this->contact_email = config('mail.from.address', '');
        $this->contact_phone = config('app.contact_phone', '');
        $this->contact_address = config('app.contact_address', '');
        $this->timezone = config('app.timezone', 'UTC');
        $this->default_language = config('app.locale', 'de');
        $this->maintenance_mode = app()->isDownForMaintenance();

        // Payment
        $this->currency = config('app.currency', 'EUR');
        $this->tax_rate = config('app.tax_rate', 0);
        $this->stripe_key = config('services.stripe.key', '');
        $this->stripe_secret = config('services.stripe.secret', '');
        $this->stripe_webhook_secret = config('services.stripe.webhook_secret', '');
        $this->enable_free_trial = config('app.enable_free_trial', true);
        $this->free_trial_days = config('app.free_trial_days', 7);

        // Email
        $this->mail_driver = config('mail.default', 'smtp');
        $this->mail_host = config('mail.mailers.smtp.host', '');
        $this->mail_port = config('mail.mailers.smtp.port', 587);
        $this->mail_username = config('mail.mailers.smtp.username', '');
        $this->mail_password = config('mail.mailers.smtp.password', '');
        $this->mail_encryption = config('mail.mailers.smtp.encryption', 'tls');
        $this->mail_from_address = config('mail.from.address', '');
        $this->mail_from_name = config('mail.from.name', '');

        // Security
        $this->session_lifetime = config('session.lifetime', 120);
        $this->login_attempts = config('auth.throttle.attempts', 5);
        $this->lockout_minutes = config('auth.throttle.lockout_minutes', 15);
        $this->two_factor_auth = config('auth.two_factor', false);
        $this->password_expiry_days = config('auth.password_expiry_days', 90);
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'site_name'         => 'required|string|max:255',
            'contact_email'     => 'required|email',
            'contact_phone'     => 'nullable|string|max:20',
            'timezone'          => 'required|string',
            'default_language'  => 'required|in:de,fr,en',
        ]);

        $this->updateEnvFile('APP_NAME', $this->site_name);
        $this->updateEnvFile('APP_TIMEZONE', $this->timezone);
        $this->updateEnvFile('APP_LOCALE', $this->default_language);

        $this->success(__('General settings saved! 🎉'));
    }

    public function savePayment(): void
    {
        $this->validate([
            'currency'      => 'required|string|size:3',
            'tax_rate'      => 'numeric|min:0|max:100',
            'free_trial_days' => 'integer|min:0|max:30',
        ]);

        $this->updateEnvFile('APP_CURRENCY', $this->currency);
        $this->updateEnvFile('APP_TAX_RATE', $this->tax_rate);
        $this->updateEnvFile('APP_ENABLE_FREE_TRIAL', $this->enable_free_trial ? 'true' : 'false');
        $this->updateEnvFile('APP_FREE_TRIAL_DAYS', $this->free_trial_days);
        $this->updateEnvFile('STRIPE_KEY', $this->stripe_key);
        $this->updateEnvFile('STRIPE_SECRET', $this->stripe_secret);
        $this->updateEnvFile('STRIPE_WEBHOOK_SECRET', $this->stripe_webhook_secret);

        $this->success(__('Payment settings saved! 💰'));
    }

    public function saveEmail(): void
    {
        $this->validate([
            'mail_host'         => 'required_if:mail_driver,smtp|nullable|string',
            'mail_port'         => 'required_if:mail_driver,smtp|nullable|integer',
            'mail_from_address' => 'required|email',
            'mail_from_name'    => 'required|string',
        ]);

        $this->updateEnvFile('MAIL_MAILER', $this->mail_driver);
        $this->updateEnvFile('MAIL_HOST', $this->mail_host);
        $this->updateEnvFile('MAIL_PORT', $this->mail_port);
        $this->updateEnvFile('MAIL_USERNAME', $this->mail_username);
        $this->updateEnvFile('MAIL_PASSWORD', $this->mail_password);
        $this->updateEnvFile('MAIL_ENCRYPTION', $this->mail_encryption);
        $this->updateEnvFile('MAIL_FROM_ADDRESS', $this->mail_from_address);
        $this->updateEnvFile('MAIL_FROM_NAME', $this->mail_from_name);

        $this->success(__('Email settings saved! 📧'));
    }

    public function saveSecurity(): void
    {
        $this->validate([
            'session_lifetime'   => 'integer|min:15|max:1440',
            'login_attempts'     => 'integer|min:1|max:20',
            'lockout_minutes'    => 'integer|min:1|max:60',
            'password_expiry_days' => 'integer|min:0|max:365',
        ]);

        $this->updateEnvFile('SESSION_LIFETIME', $this->session_lifetime);
        $this->updateEnvFile('AUTH_THROTTLE_ATTEMPTS', $this->login_attempts);
        $this->updateEnvFile('AUTH_THROTTLE_LOCKOUT_MINUTES', $this->lockout_minutes);
        $this->updateEnvFile('AUTH_TWO_FACTOR', $this->two_factor_auth ? 'true' : 'false');
        $this->updateEnvFile('AUTH_PASSWORD_EXPIRY_DAYS', $this->password_expiry_days);

        $this->success(__('Security settings saved! 🔒'));
    }

    public function sendTestEmail(): void
    {
        try {
            \Illuminate\Support\Facades\Mail::raw('Test email from AllemandExpress', function ($message) {
                $message->to($this->mail_from_address)
                        ->subject('Test Email');
            });
            $this->success(__('Test email sent! 📨'));
        } catch (\Exception $e) {
            $this->error(__('Error sending test email: ') . $e->getMessage());
        }
    }

    public function clearCache(): void
    {
        \Artisan::call('cache:clear');
        \Artisan::call('config:clear');
        \Artisan::call('view:clear');
        \Artisan::call('route:clear');
        \Artisan::call('optimize:clear');
        $this->success(__('All caches cleared! 🧹'));
    }

    public function toggleMaintenance(): void
    {
        if (!$this->maintenance_mode) {
            \Artisan::call('down');
            $this->maintenance_mode = true;
            $this->success(__('Maintenance mode activated! ⚠️'));
        } else {
            \Artisan::call('up');
            $this->maintenance_mode = false;
            $this->success(__('Maintenance mode deactivated! ✅'));
        }
    }

    private function updateEnvFile($key, $value): void
    {
        $path = base_path('.env');
        if (!file_exists($path)) return;

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

    public function render()
    {
        return $this->view();
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">⚙️ {{ __('System Settings') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Configure your platform settings') }}</p>
            </div>
            <x-button wire:click="clearCache" label="{{ __('Clear cache') }}" icon="o-arrow-path" class="btn-outline" />
        </div>

        {{-- Tabs --}}
        <div class="mb-6 tabs tabs-boxed">
            <a class="tab {{ $activeTab === 'general' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'general')">⚙️ {{ __('General') }}</a>
            <a class="tab {{ $activeTab === 'payment' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'payment')">💰 {{ __('Payments') }}</a>
            <a class="tab {{ $activeTab === 'email' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'email')">📧 {{ __('Email') }}</a>
            <a class="tab {{ $activeTab === 'security' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'security')">🔒 {{ __('Security') }}</a>
        </div>

        {{-- General Tab --}}
        @if($activeTab === 'general')
            <x-form wire:submit="saveGeneral">
                <x-card class="shadow-sm">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-globe-alt" class="w-5 h-5 text-primary" />
                        <h2 class="font-semibold">{{ __('General Settings') }}</h2>
                    </div>
                    <div class="space-y-4">
                        <x-input wire:model="site_name" label="{{ __('Site name') }}" placeholder="{{ config('app.name') }}" icon="o-building-storefront" required />
                        <x-textarea wire:model="site_description" label="{{ __('Site description') }}" rows="2" icon="o-document-text" />
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input wire:model="contact_email" type="email" label="{{ __('Contact email') }}" placeholder="contact@example.com" icon="o-envelope" required />
                            <x-input wire:model="contact_phone" label="{{ __('Contact phone') }}" placeholder="+49 123 456789" icon="o-phone" />
                        </div>
                        <x-input wire:model="contact_address" label="{{ __('Address') }}" placeholder="{{ __('Your address') }}" icon="o-map-pin" />
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-select wire:model="timezone" label="{{ __('Timezone') }}" :options="$timezones" icon="o-clock" />
                            <x-select wire:model="default_language" label="{{ __('Default language') }}" :options="$languages" option-value="id" option-label="name" icon="o-language" />
                        </div>
                        <label class="flex items-center justify-between w-full p-3 rounded-lg bg-base-200">
                            <div><span class="font-medium">{{ __('Maintenance mode') }}</span><p class="text-xs text-base-content/60">{{ __('Only administrators can access the site') }}</p></div>
                            <input type="checkbox" wire:click="toggleMaintenance" class="toggle toggle-primary" {{ $maintenance_mode ? 'checked' : '' }} />
                        </label>
                    </div>
                </x-card>
                <x-slot:actions>
                    <div class="flex justify-end mt-4">
                        <x-button label="{{ __('Save settings') }}" class="btn-primary" type="submit" spinner="saveGeneral" />
                    </div>
                </x-slot:actions>
            </x-form>

        {{-- Payment Tab --}}
        @elseif($activeTab === 'payment')
            <x-form wire:submit="savePayment">
                <x-card class="shadow-sm">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-currency-euro" class="w-5 h-5 text-primary" />
                        <h2 class="font-semibold">{{ __('Payment Settings') }}</h2>
                    </div>
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-select wire:model="currency" label="{{ __('Currency') }}" :options="[
                                ['id' => 'EUR', 'name' => 'EUR - Euro (€)'],
                                ['id' => 'USD', 'name' => 'USD - US Dollar ($)'],
                                ['id' => 'GBP', 'name' => 'GBP - British Pound (£)']
                            ]" option-value="id" option-label="name" icon="o-currency-euro" />
                            <x-input wire:model="tax_rate" type="number" step="0.01" label="{{ __('Tax rate (%)') }}" placeholder="19" icon="o-chart-bar" />
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="flex items-center justify-between w-full p-3 rounded-lg bg-base-200">
                                <div><span class="font-medium">{{ __('Free trial') }}</span><p class="text-xs text-base-content/60">{{ __('New users get trial days') }}</p></div>
                                <input type="checkbox" wire:model="enable_free_trial" class="toggle toggle-primary" />
                            </label>
                            <x-input wire:model="free_trial_days" type="number" min="0" max="30" label="{!! __('Trial days') !!}" placeholder="7" icon="o-calendar" />
                        </div>
                        <div class="pt-4">
                            <h3 class="mb-3 font-medium">{{ __('Stripe Integration') }}</h3>
                            <div class="space-y-3">
                                <x-input wire:model="stripe_key" label="{{ __('Stripe Publishable Key') }}" placeholder="pk_live_..." icon="o-credit-card" />
                                <x-input wire:model="stripe_secret" type="password" label="{{ __('Stripe Secret Key') }}" placeholder="sk_live_..." icon="o-key" />
                                <x-input wire:model="stripe_webhook_secret" type="password" label="{{ __('Stripe Webhook Secret') }}" placeholder="whsec_..." icon="o-link" />
                            </div>
                        </div>
                    </div>
                </x-card>
                <x-slot:actions>
                    <div class="flex justify-end mt-4">
                        <x-button label="{{ __('Save settings') }}" class="btn-primary" type="submit" spinner="savePayment" />
                    </div>
                </x-slot:actions>
            </x-form>

        {{-- Email Tab --}}
        @elseif($activeTab === 'email')
            <x-form wire:submit="saveEmail">
                <x-card class="shadow-sm">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-envelope" class="w-5 h-5 text-primary" />
                        <h2 class="font-semibold">{{ __('Email Settings') }}</h2>
                    </div>
                    <div class="space-y-4">
                        <x-select wire:model="mail_driver" label="{{ __('Mail driver') }}" :options="[
                            ['id' => 'smtp', 'name' => 'SMTP'],
                            ['id' => 'sendmail', 'name' => 'Sendmail'],
                            ['id' => 'log', 'name' => 'Log (testing)']
                        ]" option-value="id" option-label="name" icon="o-cog" />
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input wire:model="mail_host" label="{{ __('SMTP Host') }}" placeholder="smtp.gmail.com" icon="o-server" />
                            <x-input wire:model="mail_port" type="number" label="{{ __('SMTP Port') }}" placeholder="587" icon="o-numbered-list" />
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-input wire:model="mail_username" label="{!! __('SMTP Username') !!}" placeholder="user@example.com" icon="o-user" />
                            <x-input wire:model="mail_password" type="password" label="{{ __('SMTP Password') }}" placeholder="••••••••" icon="o-key" />
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-select wire:model="mail_encryption" label="{{ __('Encryption') }}" :options="[['id' => 'tls', 'name' => 'TLS'], ['id' => 'ssl', 'name' => 'SSL']]" option-value="id" option-label="name" icon="o-lock-closed" />
                            <x-input wire:model="mail_from_address" type="email" label="{!! __('From email') !!}" placeholder="noreply@example.com" icon="o-envelope" required />
                            <x-input wire:model="mail_from_name" label="{!! __('From name') !!}" placeholder="AllemandExpress" icon="o-user" required />
                        </div>
                    </div>
                </x-card>
                <x-slot:actions>
                    <div class="flex items-center justify-between mt-4">
                        <x-button type="button" wire:click="sendTestEmail" label="{{ __('Send test email') }}" icon="o-paper-airplane" class="btn-outline" />
                        <x-button label="{{ __('Save settings') }}" class="btn-primary" type="submit" spinner="saveEmail" />
                    </div>
                </x-slot:actions>
            </x-form>

        {{-- Security Tab --}}
        @elseif($activeTab === 'security')
            <x-form wire:submit="saveSecurity">
                <x-card class="shadow-sm">
                    <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                        <x-icon name="o-shield-check" class="w-5 h-5 text-primary" />
                        <h2 class="font-semibold">{{ __('Security Settings') }}</h2>
                    </div>
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-3">
                            <x-input wire:model="session_lifetime" type="number" min="15" max="1440" label="{{ __('Session lifetime (minutes)') }}" placeholder="120" icon="o-clock" />
                            <x-input wire:model="login_attempts" type="number" min="1" max="20" label="{{ __('Max login attempts') }}" placeholder="5" icon="o-arrow-path" />
                            <x-input wire:model="lockout_minutes" type="number" min="1" max="60" label="{{ __('Lockout time (minutes)') }}" placeholder="15" icon="o-lock-closed" />
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="flex items-center justify-between w-full p-3 rounded-lg bg-base-200">
                                <div><span class="font-medium">{{ __('Two-factor authentication') }}</span><p class="text-xs text-base-content/60">{{ __('Enhanced security for administrators') }}</p></div>
                                <input type="checkbox" wire:model="two_factor_auth" class="toggle toggle-primary" />
                            </label>
                            <x-input wire:model="password_expiry_days" type="number" min="0" max="365" label="{{ __('Password expiry (days)') }}" placeholder="90" icon="o-calendar" />
                        </div>
                    </div>
                </x-card>
                <x-slot:actions>
                    <div class="flex justify-end mt-4">
                        <x-button label="{{ __('Save settings') }}" class="btn-primary" type="submit" spinner="saveSecurity" />
                    </div>
                </x-slot:actions>
            </x-form>
        @endif
    </div>
</div>
