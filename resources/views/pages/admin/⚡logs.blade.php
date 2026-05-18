<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\File;
use Mary\Traits\Toast;

new
#[Title('System Logs - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public string $logFile = '';
    public string $logContent = '';
    public array $logFiles = [];
    public string $search = '';

    public function mount(): void
    {
        $this->scanLogFiles();
        if (!empty($this->logFiles)) {
            $this->logFile = $this->logFiles[0];
            $this->loadLog();
        }
    }

    public function scanLogFiles(): void
    {
        $logPath = storage_path('logs');
        $files = File::files($logPath);
        $this->logFiles = collect($files)
            ->map(fn($file) => $file->getFilename())
            ->filter(fn($name) => preg_match('/\.log$/', $name))
            ->values()
            ->toArray();
    }

    public function loadLog(): void
    {
        if (!$this->logFile) return;
        $path = storage_path('logs/' . $this->logFile);
        if (File::exists($path)) {
            $content = File::get($path);
            if ($this->search) {
                $lines = explode("\n", $content);
                $lines = array_filter($lines, fn($line) => stripos($line, $this->search) !== false);
                $this->logContent = implode("\n", $lines);
            } else {
                $this->logContent = $content;
            }
        } else {
            $this->logContent = '';
            $this->error(__('Log file not found.'));
        }
    }

    public function downloadLog()
    {
        if (!$this->logFile) return;
        $path = storage_path('logs/' . $this->logFile);
        if (File::exists($path)) {
            return response()->download($path, $this->logFile);
        }
        $this->error(__('Log file not found.'));
    }

    public function clearLog(): void
    {
        if (!$this->logFile) return;
        $path = storage_path('logs/' . $this->logFile);
        if (File::exists($path)) {
            File::put($path, '');
            $this->loadLog();
            $this->success(__('Log file cleared successfully! 🗑️'));
        } else {
            $this->error(__('Log file not found.'));
        }
    }

    public function updatedSearch(): void
    {
        $this->loadLog();
    }

    public function updatedLogFile(): void
    {
        $this->search = '';
        $this->loadLog();
    }


};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📜 {{ __('System Logs') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('View and manage application logs') }}</p>
            </div>
            <div class="flex gap-2">
                <x-button wire:click="downloadLog" label="{{ __('Download') }}" icon="o-arrow-down-tray" class="btn-outline" />
                <x-button wire:click="clearLog" label="{{ __('Clear') }}" icon="o-trash" class="btn-error" wire:confirm="{{ __('Are you sure you want to clear this log file?') }}" />
            </div>
        </div>

        {{-- Log file selector --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-select
                    wire:model.live="logFile"
                    label="{{ __('Select log file') }}"
                    :options="collect($logFiles)->map(fn($file) => ['id' => $file, 'name' => $file])->toArray()"
                    option-value="id"
                    option-label="name"
                    icon="o-document"
                />
                <x-input
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Search in log...') }}"
                    icon="o-magnifying-glass"
                    label="{{ __('Filter') }}"
                    clearable
                />
            </div>
        </div>

        {{-- Log content display --}}
        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-document-text" class="w-5 h-5 text-primary" />
                <h2 class="font-semibold">{{ __('Log content') }}</h2>
                <span class="ml-auto text-xs text-base-content/50">{{ $logFile }}</span>
            </div>
            <pre class="p-4 overflow-x-auto text-sm rounded-lg bg-base-200 text-base-content max-h-[600px] font-mono">{{ $logContent ?: __('No log content available.') }}</pre>
        </x-card>
    </div>
</div>
