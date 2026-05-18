<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Mary\Traits\Toast;

new
#[Title('Backup - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public array $backups = [];
    public bool $creatingBackup = false;
    public string $backupDisk = 'local';

    public function mount(): void
    {
        $this->loadBackups();
    }

    public function loadBackups(): void
    {
        $backupPath = storage_path('app/backups');
        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $files = File::files($backupPath);
        $this->backups = collect($files)->map(function ($file) {
            return [
                'name' => $file->getFilename(),
                'size' => $this->formatSize($file->getSize()),
                'date' => date('Y-m-d H:i:s', $file->getMTime()),
                'path' => $file->getPathname(),
            ];
        })->sortByDesc('date')->values()->toArray();
    }

    public function createBackup(): void
    {

        if (!class_exists('ZipArchive')) {
            $this->error(__('ZipArchive extension is not installed. Please enable it in your php.ini or ask your administrator.'));
            return;
        }
        $this->creatingBackup = true;

        try {
            // Exemple de backup simple : copie des dossiers clés (database, storage, .env)
            $backupName = 'backup_' . date('Y-m-d_His') . '.zip';
            $backupPath = storage_path('app/backups/' . $backupName);

            $filesToBackup = [
                database_path(),
                storage_path('app/public'),
                base_path('.env'),
            ];

            $zip = new \ZipArchive();
            if ($zip->open($backupPath, \ZipArchive::CREATE) !== true) {
                throw new \Exception('Cannot create zip file');
            }

            foreach ($filesToBackup as $source) {
                if (is_dir($source)) {
                    $this->addFolderToZip($zip, $source, basename($source));
                } elseif (is_file($source)) {
                    $zip->addFile($source, basename($source));
                }
            }

            $zip->close();

            $this->success(__('Backup created successfully! 🎉'));
            $this->loadBackups();
        } catch (\Exception $e) {
            $this->error(__('Backup failed: ') . $e->getMessage());
        }

        $this->creatingBackup = false;
    }

    private function addFolderToZip($zip, $folder, $zipFolderName): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $zipFolderName . '/' . substr($filePath, strlen($folder) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }

    public function downloadBackup($index)
    {
        $backup = $this->backups[$index] ?? null;
        if (!$backup || !File::exists($backup['path'])) {
            $this->error(__('Backup file not found.'));
            return;
        }

        return response()->download($backup['path'], $backup['name']);
    }

    public function deleteBackup($index): void
    {
        $backup = $this->backups[$index] ?? null;
        if (!$backup || !File::exists($backup['path'])) {
            $this->error(__('Backup file not found.'));
            return;
        }

        File::delete($backup['path']);
        $this->success(__('Backup deleted successfully! 🗑️'));
        $this->loadBackups();
    }

    private function formatSize($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function render()
    {
        return $this->view([
            'backups' => $this->backups,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">💾 {{ __('Backup') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage database and file backups') }}</p>
            </div>
            <x-button wire:click="createBackup" label="{{ __('Create backup') }}" icon="o-plus" class="btn-primary" spinner="creatingBackup" />
        </div>

        {{-- Backup list --}}
        <x-card class="shadow-sm">
            <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                <x-icon name="o-archive-box" class="w-5 h-5 text-primary" />
                <h2 class="font-semibold">{{ __('Available backups') }}</h2>
            </div>

            @if(count($backups) > 0)
                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Filename') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Size') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $index => $backup)
                                <tr class="transition border-b hover:bg-base-200">
                                    <td class="px-4 py-3 text-sm">{{ $backup['name'] }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $backup['size'] }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $backup['date'] }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <x-button icon="o-arrow-down-tray" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Download') }}" wire:click="downloadBackup({{ $index }})" />
                                            <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteBackup({{ $index }})" wire:confirm="{{ __('Are you sure?') }}" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="space-y-3 md:hidden">
                    @foreach($backups as $index => $backup)
                        <div class="p-3 rounded-lg bg-base-200">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm font-medium">{{ $backup['name'] }}</p>
                                    <p class="text-xs text-base-content/60">{{ $backup['date'] }} • {{ $backup['size'] }}</p>
                                </div>
                                <div class="flex gap-1">
                                    <x-button icon="o-arrow-down-tray" class="btn-ghost btn-sm" wire:click="downloadBackup({{ $index }})" />
                                    <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteBackup({{ $index }})" />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-12 text-center">
                    <x-icon name="o-archive-box" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                    <h3 class="mb-2 text-lg font-semibold">{{ __('No backups yet') }}</h3>
                    <p class="mb-4 text-base-content/60">{{ __('Create your first backup to protect your data.') }}</p>
                    <x-button wire:click="createBackup" label="{{ __('Create backup') }}" class="btn-primary" />
                </div>
            @endif
        </x-card>

        {{-- Info note --}}
        <div class="p-4 mt-6 border rounded-lg bg-info/10 border-info/20">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-info mt-0.5" />
                <div>
                    <p class="font-medium text-info">{{ __('Backup information') }}</p>
                    <p class="text-sm text-info/80">{{ __('Backups include database, uploaded files and configuration. Store them securely.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
