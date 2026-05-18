<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\NotificationTemplate;
use Mary\Traits\Toast;

new
#[Title('Edit Template - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public ?NotificationTemplate $template = null;
    public string $title = '';
    public string $subject = '';
    public string $content = '';
    public string $type = 'info';
    public string $action_url = '';
    public string $action_text = '';
    public bool $is_active = true;

    public function mount($template = null)
    {
        if ($template) {
            $this->template = $template;
            $this->title = $template->title;
            $this->subject = $template->subject;
            $this->content = $template->content;
            $this->type = $template->type;
            $this->action_url = $template->action_url ?? '';
            $this->action_text = $template->action_text ?? '';
            $this->is_active = $template->is_active;
        }
    }

    public function save(): void
    {
        $this->validate([
            'title'   => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'type'    => 'required|in:info,warning,success,error',
            'action_url'  => 'nullable|url|max:500',
            'action_text' => 'nullable|string|max:100',
            'is_active'   => 'boolean',
        ]);

        $data = [
            'title'       => $this->title,
            'subject'     => $this->subject,
            'content'     => $this->content,
            'type'        => $this->type,
            'action_url'  => $this->action_url,
            'action_text' => $this->action_text,
            'is_active'   => $this->is_active,
        ];

        if ($this->template) {
            $this->template->update($data);
            $this->success(__('Template updated successfully.'));
        } else {
            NotificationTemplate::create($data);
            $this->success(__('Template created successfully.'));
        }

        $this->redirectRoute('admin.notification-templates');
    }

    public function render()
    {
        return $this->view();
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-4xl px-3 mx-auto md:px-4">
        <h1 class="mb-6 text-2xl font-bold">{{ $template ? __('Edit Template') : __('Create Template') }}</h1>

        <x-card class="shadow-sm">
            <x-form wire:submit="save" no-separator>
                <x-input wire:model="title" label="{{ __('Title') }}" placeholder="{{ __('Welcome new user') }}" required />
                <x-input wire:model="subject" label="{{ __('Email subject') }}" placeholder="{{ __('Welcome to our platform!') }}" required />
                <x-select wire:model="type" label="{{ __('Notification type') }}" :options="[
                    ['id' => 'info', 'name' => 'Info'],
                    ['id' => 'warning', 'name' => 'Warning'],
                    ['id' => 'success', 'name' => 'Success'],
                    ['id' => 'error', 'name' => 'Error'],
                ]" option-value="id" option-label="name" required />
                <x-textarea wire:model="content" label="{{ __('Content') }}" rows="8" placeholder="{{ __('Full message content...') }}" required />
                <div class="grid gap-4 md:grid-cols-2">
                    <x-input wire:model="action_url" label="{{ __('Action URL (optional)') }}" placeholder="https://..." />
                    <x-input wire:model="action_text" label="{{ __('Button text') }}" placeholder="{{ __('Learn more') }}" />
                </div>
                <x-toggle wire:model="is_active" label="{{ __('Active') }}" hint="{{ __('Inactive templates are not shown in selection') }}" />

                <x-slot:actions>
                    <x-button label="{{ __('Cancel') }}" link="{{ route('admin.notification-templates') }}" class="btn-ghost" />
                    <x-button label="{{ __('Save') }}" class="btn-primary" type="submit" spinner="save" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
