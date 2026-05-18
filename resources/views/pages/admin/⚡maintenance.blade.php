<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Mary\Traits\Toast;

new
#[Title('Maintenance - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public bool $maintenanceMode = false;
    public ?string $maintenanceMessage = null;
    public ?string $maintenanceSecret = null;
    public bool $allowSecret = true;
    public array $maintenanceIps = [];

    public string $newIp = '';

    public function mount(): void
    {
        $this->checkMaintenanceStatus();
        $this->loadMaintenanceConfig();
    }

    public function checkMaintenanceStatus(): void
    {
        $this->maintenanceMode = app()->isDownForMaintenance();
    }

    public function loadMaintenanceConfig(): void
    {
        $maintenanceFile = storage_path('framework/down');
        if (File::exists($maintenanceFile)) {
            $data = json_decode(File::get($maintenanceFile), true);
            $this->maintenanceMessage = $data['message'] ?? null;
            $this->maintenanceSecret = $data['secret'] ?? null;
            $this->allowSecret = !empty($this->maintenanceSecret);
            $this->maintenanceIps = $data['allowed_ips'] ?? [];
        }
    }

    public function enableMaintenance(): void
    {
        $params = [];

        if ($this->maintenanceMessage) {
            $params['--message'] = $this->maintenanceMessage;
        }

        if ($this->maintenanceSecret) {
            $params['--secret'] = $this->maintenanceSecret;
        }

        if (!empty($this->maintenanceIps)) {
            $params['--allow'] = implode(',', $this->maintenanceIps);
        }

        try {
            Artisan::call('down', $params);
            $this->maintenanceMode = true;
            $this->success(__('Maintenance mode enabled.'));
        } catch (\Exception $e) {
            $this->error(__('Error: ') . $e->getMessage());
        }
    }

    public function disableMaintenance(): void
    {
        try {
            Artisan::call('up');
            $this->maintenanceMode = false;
            $this->success(__('Maintenance mode disabled.'));
        } catch (\Exception $e) {
            $this->error(__('Error: ') . $e->getMessage());
        }
    }

    public function addIp(): void
    {
        if (filter_var($this->newIp, FILTER_VALIDATE_IP)) {
            if (!in_array($this->newIp, $this->maintenanceIps)) {
                $this->maintenanceIps[] = $this->newIp;
                $this->newIp = '';
                $this->success(__('IP address added.'));
                $this->saveMaintenanceConfig();
            } else {
                $this->warning(__('IP already in list.'));
            }
        } else {
            $this->error(__('Invalid IP address.'));
        }
    }

    public function removeIp($index): void
    {
        unset($this->maintenanceIps[$index]);
        $this->maintenanceIps = array_values($this->maintenanceIps);
        $this->success(__('IP address removed.'));
        $this->saveMaintenanceConfig();
    }

    private function saveMaintenanceConfig(): void
    {
        $maintenanceFile = storage_path('framework/down');
        if (File::exists($maintenanceFile)) {
            $data = json_decode(File::get($maintenanceFile), true);
            $data['allowed_ips'] = $this->maintenanceIps;
            if ($this->maintenanceSecret) {
                $data['secret'] = $this->maintenanceSecret;
            }
            if ($this->maintenanceMessage) {
                $data['message'] = $this->maintenanceMessage;
            }
            File::put($maintenanceFile, json_encode($data, JSON_PRETTY_PRINT));
        }
    }

    public function clearOptimize(): void
    {
        Artisan::call('optimize:clear');
        $this->success(__('Optimize cache cleared.'));
    }

    public function render()
    {
        return $this->view();
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-4xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold md:text-3xl">🔧 {{ __('Maintenance') }}</h1>
            <p class="mt-1 text-sm text-base-content/70">{{ __('Control application maintenance mode') }}</p>
        </div>

        {{-- Maintenance toggle card --}}
        <x-card class="mb-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('Maintenance mode') }}</h2>
                    <p class="text-sm text-base-content/70">{{ __('When enabled, only administrators and allowed IPs can access the site.') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm {{ $maintenanceMode ? 'text-error' : 'text-success' }}">
                        {{ $maintenanceMode ? __('Active') : __('Inactive') }}
                    </span>
                    @if($maintenanceMode)
                        <x-button label="{{ __('Disable') }}" wire:click="disableMaintenance" class="btn-error" spinner />
                    @else
                        <x-button label="{{ __('Enable') }}" wire:click="enableMaintenance" class="btn-primary" spinner />
                    @endif
                </div>
            </div>
        </x-card>

        {{-- Maintenance settings (visible only when mode is enabled or we prepare settings) --}}
        <x-card class="mb-6 shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-cog-6-tooth" class="w-5 h-5 text-primary" />
                <h2 class="font-semibold">{{ __('Maintenance settings') }}</h2>
            </div>
            <div class="space-y-4">
                <x-textarea wire:model="maintenanceMessage" label="{{ __('Custom message') }}" rows="2" placeholder="{{ __('The site is under maintenance. Please come back later.') }}" />
                <x-input wire:model="maintenanceSecret" label="{{ __('Secret token') }}" placeholder="{{ __('Access with /{secret}') }}" hint="{{ __('Add ?secret=token to bypass maintenance') }}" />
            </div>
        </x-card>

        {{-- IP whitelist --}}
        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-shield-check" class="w-5 h-5 text-primary" />
                <h2 class="font-semibold">{{ __('Allowed IP addresses') }}</h2>
            </div>
            <div class="space-y-4">
                <div class="flex gap-2">
                    <x-input wire:model="newIp" placeholder="{{ __('Enter IP address') }}" class="flex-1" />
                    <x-button wire:click="addIp" label="{{ __('Add') }}" class="btn-primary" />
                </div>
                @if(count($maintenanceIps) > 0)
                    <div class="space-y-2">
                        @foreach($maintenanceIps as $index => $ip)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-base-200">
                                <span>{{ $ip }}</span>
                                <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="removeIp({{ $index }})" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm italic text-base-content/60">{{ __('No IP addresses whitelisted.') }}</p>
                @endif
            </div>
        </x-card>

        {{-- Utilities --}}
        <div class="mt-6">
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-rocket-launch" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('Utilities') }}</h2>
                </div>
                <x-button wire:click="clearOptimize" label="{{ __('Clear optimization cache') }}" icon="o-arrow-path" class="btn-outline" />
            </x-card>
        </div>

        {{-- Info note --}}
        <div class="p-4 mt-6 border rounded-lg bg-info/10 border-info/20">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-info mt-0.5" />
                <div>
                    <p class="font-medium text-info">{{ __('Maintenance mode information') }}</p>
                    <p class="text-sm text-info/80">{{ __('When maintenance is enabled, non-admin users will see the maintenance page. Use the secret token to bypass or whitelist specific IPs.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
