<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Contact;
use Mary\Traits\Toast;

new
#[Title('Contact Messages - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    #[Url(as: 'read', history: true)]
    public string $readFilter = '';

    // Modal states
    public bool $showViewModal = false;
    public bool $showReplyModal = false;
    public $selectedContact = null;

    // Reply form
    public string $replyMessage = '';

    // Getters (remplacent #[Computed])
    public function getContactsProperty()
    {
        return Contact::with(['repliedBy', 'user'])
            ->when($this->search, function($q) {
                $q->where(function($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%')
                          ->orWhere('subject', 'like', '%' . $this->search . '%')
                          ->orWhere('message', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->readFilter === 'unread', fn($q) => $q->where('is_read', false))
            ->when($this->readFilter === 'read', fn($q) => $q->where('is_read', true))
            ->latest()
            ->paginate(15);
    }

    public function getStatsProperty()
    {
        return [
            'total'   => Contact::count(),
            'pending' => Contact::where('status', 'pending')->count(),
            'unread'  => Contact::where('is_read', false)->count(),
            'replied' => Contact::where('status', 'replied')->count(),
        ];
    }

    public function viewContact($id): void
    {
        $this->selectedContact = Contact::with(['repliedBy', 'user'])->findOrFail($id);

        if (!$this->selectedContact->is_read) {
            $this->selectedContact->update([
                'is_read'  => true,
                'read_at'  => now(),
                'status'   => 'read',
            ]);
        }

        $this->showViewModal = true;
    }

    public function openReplyModal($id): void
    {
        $this->selectedContact = Contact::findOrFail($id);
        $this->replyMessage = '';
        $this->showReplyModal = true;
    }

    public function sendReply(): void
    {
        $this->validate([
            'replyMessage' => 'required|string|min:3',
        ], [
            'replyMessage.required' => __('Please enter a reply message.'),
            'replyMessage.min'      => __('The reply must be at least 3 characters.'),
        ]);

        $this->selectedContact->update([
            'status'       => 'replied',
            'replied_by'   => auth()->id(),
            'reply_message' => $this->replyMessage,
            'replied_at'   => now(),
        ]);

        // Optionnel : envoyer un email
        // Mail::to($this->selectedContact->email)->send(new ContactReplyMail($this->selectedContact, $this->replyMessage));

        $this->success(__('Reply sent successfully!'));
        $this->showReplyModal = false;
        $this->replyMessage = '';
    }

    public function archiveContact($id): void
    {
        $contact = Contact::findOrFail($id);
        $contact->update(['status' => 'archived']);
        $this->success(__('Contact message archived.'));
    }

    public function deleteContact($id): void
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();
        $this->success(__('Contact message deleted permanently.'));
    }

    public function markAsRead($id): void
    {
        $contact = Contact::findOrFail($id);
        $contact->update([
            'is_read' => true,
            'read_at' => now(),
            'status'  => 'read',
        ]);
        $this->success(__('Message marked as read.'));
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'readFilter']);
        $this->resetPage();
        $this->success(__('Filters cleared.'));
    }

    public function getStatusColor($status): string
    {
        return match($status) {
            'pending' => 'badge-warning',
            'read'    => 'badge-info',
            'replied' => 'badge-success',
            'archived'=> 'badge-neutral',
            default   => 'badge-ghost',
        };
    }

    public function getStatusLabel($status): string
    {
        return match($status) {
            'pending' => __('Pending'),
            'read'    => __('Read'),
            'replied' => __('Replied'),
            'archived'=> __('Archived'),
            default   => $status,
        };
    }

    public function render()
    {
        return $this->view([
            'contacts' => $this->contacts,
            'stats'    => $this->stats,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📬 {{ __('Contact Messages') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage messages from your website contact form') }}</p>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <x-stat title="{{ __('Total') }}" :value="$stats['total']" icon="o-inbox" class="text-primary" />
            <x-stat title="{{ __('Pending') }}" :value="$stats['pending']" icon="o-clock" class="text-warning" />
            <x-stat title="{{ __('Unread') }}" :value="$stats['unread']" icon="o-envelope" class="text-info" />
            <x-stat title="{{ __('Replied') }}" :value="$stats['replied']" icon="o-check-circle" class="text-success" />
        </div>

        {{-- Filters --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name, email, subject...') }}" icon="o-magnifying-glass" class="w-full" clearable />
                    <x-select wire:model.live="statusFilter" :options="[
                        ['id' => '', 'name' => __('All Status')],
                        ['id' => 'pending', 'name' => __('Pending')],
                        ['id' => 'read', 'name' => __('Read')],
                        ['id' => 'replied', 'name' => __('Replied')],
                        ['id' => 'archived', 'name' => __('Archived')],
                    ]" option-value="id" option-label="name" id="status_filter" name="status_filter" />
                    <x-select wire:model.live="readFilter" :options="[
                        ['id' => '', 'name' => __('All Messages')],
                        ['id' => 'unread', 'name' => __('Unread')],
                        ['id' => 'read', 'name' => __('Read')],
                    ]" option-value="id" option-label="name" id="read_filter" name="read_filter" />
                </div>
                @if($search || $statusFilter || $readFilter)
                    <div class="flex justify-end">
                        <x-button wire:click="clearFilters" label="{{ __('Clear filters') }}" icon="o-x-mark" class="btn-ghost btn-sm" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Contacts List --}}
        @if($contacts->count() > 0)
            {{-- Desktop table --}}
            <div class="hidden overflow-hidden shadow-sm md:block bg-base-100 rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Contact') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Subject') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Received') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contacts as $contact)
                                <tr class="border-b hover:bg-base-200 transition {{ !$contact->is_read ? 'bg-warning/5' : '' }}">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-medium">{{ $contact->name }}</p>
                                            <p class="text-xs text-base-content/60">{{ $contact->email }}</p>
                                            @if($contact->phone)
                                                <p class="text-xs text-base-content/50">{{ $contact->phone }}</p>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm">{{ Str::limit($contact->subject, 40) }}</p>
                                        <p class="mt-1 text-xs text-base-content/60">{{ Str::limit($contact->message, 50) }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-1">
                                            <x-badge :value="$this->getStatusLabel($contact->status)" :class="$this->getStatusColor($contact->status) . ' badge-soft'" />
                                            @if(!$contact->is_read)
                                                <x-badge value="{{ __('New') }}" class="badge-error badge-soft" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-base-content/60">
                                        {{ $contact->created_at->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <x-button icon="o-eye" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('View') }}" wire:click="viewContact({{ $contact->id }})" />
                                            <x-button icon="o-paper-airplane" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Reply') }}" wire:click="openReplyModal({{ $contact->id }})" />
                                            <x-button icon="o-archive-box" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Archive') }}" wire:click="archiveContact({{ $contact->id }})" />
                                            <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteContact({{ $contact->id }})" wire:confirm="{{ __('Delete this message?') }}" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-base-200">
                    {{ $contacts->links() }}
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($contacts as $contact)
                    <x-card class="{{ !$contact->is_read ? 'border-l-4 border-l-warning' : '' }}">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <p class="font-semibold">{{ $contact->name }}</p>
                                <p class="text-xs text-base-content/60">{{ $contact->email }}</p>
                            </div>
                            <x-badge :value="$this->getStatusLabel($contact->status)" :class="$this->getStatusColor($contact->status) . ' badge-soft'" />
                        </div>
                        <p class="mb-1 text-sm font-medium">{{ $contact->subject }}</p>
                        <p class="mb-2 text-xs text-base-content/60">{{ Str::limit($contact->message, 80) }}</p>
                        <div class="flex items-center justify-between pt-2 border-t">
                            <span class="text-xs text-base-content/50">{{ $contact->created_at->diffForHumans() }}</span>
                            <div class="flex gap-2">
                                <x-button icon="o-eye" class="btn-ghost btn-sm" wire:click="viewContact({{ $contact->id }})" />
                                <x-button icon="o-paper-airplane" class="btn-ghost btn-sm" wire:click="openReplyModal({{ $contact->id }})" />
                                <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteContact({{ $contact->id }})" />
                            </div>
                        </div>
                    </x-card>
                @endforeach
                <div class="mt-4">{{ $contacts->links() }}</div>
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-inbox" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No messages found') }}</h3>
                <p class="text-base-content/60">{{ __('No contact messages match your filters.') }}</p>
            </x-card>
        @endif

        {{-- View Modal --}}
        <x-modal wire:model="showViewModal" title="{{ __('Message Details') }}" size="3xl" separator>
            @if($selectedContact)
                <div class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div><span class="font-bold">{{ __('Name') }}:</span> {{ $selectedContact->name }}</div>
                        <div><span class="font-bold">{{ __('Email') }}:</span> {{ $selectedContact->email }}</div>
                        @if($selectedContact->phone)
                            <div><span class="font-bold">{{ __('Phone') }}:</span> {{ $selectedContact->phone }}</div>
                        @endif
                        <div><span class="font-bold">{{ __('Received') }}:</span> {{ $selectedContact->created_at->format('d.m.Y H:i') }}</div>
                    </div>
                    <div><span class="font-bold">{{ __('Subject') }}:</span> {{ $selectedContact->subject }}</div>
                    <div><span class="font-bold">{{ __('Message') }}:</span><div class="p-3 mt-1 rounded-lg bg-base-200">{{ nl2br(e($selectedContact->message)) }}</div></div>
                    @if($selectedContact->status === 'replied')
                        <div class="p-3 border rounded-lg bg-success/10 border-success/20">
                            <div class="flex items-center gap-2 mb-2"><x-icon name="o-check-circle" class="w-5 h-5 text-success" /><span class="font-bold text-success">{{ __('Reply sent') }}</span></div>
                            <p>{{ $selectedContact->reply_message }}</p>
                            <p class="mt-2 text-xs text-success/80">{{ __('Replied by') }} {{ $selectedContact->repliedBy?->name }} • {{ optional($selectedContact->replied_at)->format('d.m.Y H:i') }}</p>
                        </div>
                    @endif
                </div>
            @endif
            <x-slot:actions>
                <x-button label="{{ __('Reply') }}" icon="o-paper-airplane" class="btn-primary" wire:click="openReplyModal({{ $selectedContact?->id }})" />
                <x-button label="{{ __('Close') }}" wire:click="$set('showViewModal', false)" />
            </x-slot:actions>
        </x-modal>

        {{-- Reply Modal --}}
        <x-modal wire:model="showReplyModal" title="{{ __('Reply to') }} {{ $selectedContact?->name }}" size="2xl" separator>
            <div class="space-y-4">
                <div class="p-3 rounded-lg bg-base-200">
                    <p class="mb-1 text-sm text-base-content/70">{{ __('Original message') }}:</p>
                    <p class="italic">"{{ Str::limit($selectedContact?->message, 150) }}"</p>
                </div>
                <x-textarea wire:model="replyMessage" label="{{ __('Your reply') }} *" rows="6" placeholder="{{ __('Write your reply here...') }}" required />
                <div class="flex items-center gap-2 text-sm text-base-content/60">
                    <x-icon name="o-information-circle" class="w-4 h-4" />
                    <span>{{ __('The customer will receive your reply by email') }}</span>
                </div>
            </div>
            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showReplyModal', false)" class="btn-ghost" />
                <x-button label="{{ __('Send Reply') }}" icon="o-paper-airplane" class="btn-primary" wire:click="sendReply" spinner />
            </x-slot:actions>
        </x-modal>
    </div>
</div>
