<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\NotificationTemplate;
use Mary\Traits\Toast;

new
#[Title('Notification Templates - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public bool $showDeleteModal = false;
    public $templateToDelete = null;

    public function getTemplatesProperty()
    {
        return NotificationTemplate::query()
            ->when($this->search, fn($q) => $q->where('title', 'like', '%' . $this->search . '%')
                ->orWhere('subject', 'like', '%' . $this->search . '%'))
            ->orderBy('title')
            ->paginate(15);
    }

    public function deleteTemplate($id): void
    {
        $this->templateToDelete = NotificationTemplate::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        if ($this->templateToDelete) {
            $this->templateToDelete->delete();
            $this->success(__('Template deleted.'));
            $this->showDeleteModal = false;
            $this->templateToDelete = null;
            $this->resetPage();
        }
    }

    public function render()
    {
        return $this->view([
            'templates' => $this->templates,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📝 {{ __('Notification Templates') }}</h1>
                <p class="text-sm text-base-content/70">{{ __('Manage reusable notification templates') }}</p>
            </div>
            <x-button label="{{ __('Create template') }}" icon="o-plus" class="btn-primary" link="{{ route('admin.notification-templates.create') }}" />
        </div>

        @if($templates->count() > 0)
            <x-card class="overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-base-200">
                            <tr>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Title') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Subject') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Active') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($templates as $template)
                                <tr class="border-b hover:bg-base-200">
                                    <td class="px-4 py-3 text-sm font-medium">{{ $template->title }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $template->subject }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge :value="ucfirst($template->type)" :class="match($template->type) {
                                            'info' => 'badge-info',
                                            'warning' => 'badge-warning',
                                            'success' => 'badge-success',
                                            'error' => 'badge-error',
                                            default => 'badge-ghost',
                                        } . ' badge-soft'" />
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($template->is_active)
                                            <x-badge value="{{ __('Active') }}" class="badge-success badge-soft" />
                                        @else
                                            <x-badge value="{{ __('Inactive') }}" class="badge-neutral badge-soft" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" link="{{ route('admin.notification-templates.edit', $template) }}" />
                                        <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" wire:click="deleteTemplate({{ $template->id }})" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t">{{ $templates->links() }}</div>
            </x-card>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-document-duplicate" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="text-lg font-semibold">{{ __('No templates yet') }}</h3>
                <p class="text-base-content/60">{{ __('Create your first notification template.') }}</p>
                <x-button label="{{ __('Create template') }}" icon="o-plus" class="mt-4 btn-primary" link="{{ route('admin.notification-templates.create') }}" />
            </x-card>
        @endif

        <x-modal wire:model="showDeleteModal" title="{{ __('Delete template') }}" separator>
            <p>{{ __('Are you sure you want to delete this template?') }}</p>
            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showDeleteModal', false)" class="btn-ghost" />
                <x-button label="{{ __('Delete') }}" class="btn-error" wire:click="confirmDelete" spinner />
            </x-slot:actions>
        </x-modal>
    </div>
</div>
