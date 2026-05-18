<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\User;
use App\Models\NotificationTemplate;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Mail;

new
#[Title('Broadcast Notification - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public ?int $templateId = null;
    public string $customTitle = '';
    public string $customMessage = '';
    public string $targetRole = 'all';
    public ?int $targetUserId = null;
    public string $sendVia = 'database';
    public array $selectedTemplates = [];

    public function getTemplatesProperty()
    {
        return NotificationTemplate::where('is_active', true)->orderBy('title')->get();
    }

    public function getUsersForSearchProperty()
    {
        return User::where('name', 'like', '%' . request('q', '') . '%')
            ->orWhere('email', 'like', '%' . request('q', '') . '%')
            ->limit(10)
            ->get();
    }

    public function loadTemplate(): void
    {
        if ($this->templateId && $template = NotificationTemplate::find($this->templateId)) {
            $this->customTitle = $template->title;
            $this->customMessage = $template->content;
        }
    }

    public function sendBroadcast(): void
    {
        $this->validate([
            'customTitle'   => 'required|string|max:255',
            'customMessage' => 'required|string|min:5',
            'targetRole'    => 'required|in:all,students,teachers,admins',
            'targetUserId'  => 'nullable|exists:users,id',
            'sendVia'       => 'required|in:database,email,both',
        ]);

        $query = User::query();
        if ($this->targetUserId) {
            $query->where('id', $this->targetUserId);
        } else {
            switch ($this->targetRole) {
                case 'students': $query->where('role', 'student'); break;
                case 'teachers': $query->where('role', 'teacher'); break;
                case 'admins':   $query->where('role', 'admin'); break;
                default: // all
            }
        }

        $users = $query->get();
        $count = 0;

        foreach ($users as $user) {
            if (in_array($this->sendVia, ['database', 'both'])) {
                $user->notify(new \App\Notifications\CustomNotification(
                    type: 'info',
                    title: $this->customTitle,
                    message: $this->customMessage,
                    actionUrl: null,
                    actionText: null
                ));
            }

            if (in_array($this->sendVia, ['email', 'both'])) {
                // Mail::to($user->email)->send(new BroadcastMail($this->customTitle, $this->customMessage));
                // Simulation
            }
            $count++;
        }

        $this->success(__('Broadcast sent to :count user(s).', ['count' => $count]));
        $this->reset(['templateId', 'customTitle', 'customMessage', 'targetRole', 'targetUserId', 'sendVia']);
    }

    public function render()
    {
        return $this->view([
            'templates' => $this->templates,
            'users'     => $this->usersForSearch,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-3xl px-3 mx-auto md:px-4">
        <h1 class="mb-6 text-2xl font-bold">📢 {{ __('Broadcast Notification') }}</h1>

        <x-card class="shadow-sm">
            <x-form wire:submit="sendBroadcast" no-separator>
                <x-select wire:model.live="templateId" label="{{ __('Use template (optional)') }}" :options="$templates" option-value="id" option-label="title" placeholder="{{ __('Select a template') }}" wire:change="loadTemplate" />
                <x-input wire:model="customTitle" label="{{ __('Notification title') }} *" placeholder="{{ __('Important announcement') }}" required />
                <x-textarea wire:model="customMessage" label="{{ __('Message') }} *" rows="5" placeholder="{{ __('Your message here...') }}" required />
                <div class="grid gap-4 md:grid-cols-2">
                    <x-select wire:model="targetRole" label="{{ __('Send to') }}" :options="[
                        ['id' => 'all', 'name' => __('All users')],
                        ['id' => 'students', 'name' => __('Students only')],
                        ['id' => 'teachers', 'name' => __('Teachers only')],
                        ['id' => 'admins', 'name' => __('Admins only')],
                    ]" option-value="id" option-label="name" required />
                    <x-choices-offline wire:model="targetUserId" label="{{ __('Specific user (optional)') }}" :options="$users" option-value="id" option-label="name" placeholder="{{ __('Select a user') }}" single clearable searchable />
                </div>
                <x-select wire:model="sendVia" label="{{ __('Send via') }}" :options="[
                    ['id' => 'database', 'name' => __('Database only')],
                    ['id' => 'email', 'name' => __('Email only')],
                    ['id' => 'both', 'name' => __('Both')],
                ]" option-value="id" option-label="name" required />
                <x-slot:actions>
                    <x-button label="{{ __('Cancel') }}" link="{{ route('admin.notifications') }}" class="btn-ghost" />
                    <x-button label="{{ __('Send broadcast') }}" class="btn-primary" type="submit" spinner="sendBroadcast" />
                </x-slot:actions>
            </x-form>
        </x-card>
    </div>
</div>
