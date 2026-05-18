<?php
// app/Livewire/Admin/Contacts.php

namespace App\Livewire\Admin;

use App\Models\Contact;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Contact Messages - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    #[Url(as: 'read', history: true)]
    public string $readFilter = '';

    // Modal states
    public $showViewModal = false;
    public $showReplyModal = false;
    public $selectedContact = null;
    
    // Reply form
    public $replyMessage = '';

    #[Computed]
    public function contacts()
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

    #[Computed]
    public function stats()
    {
        return [
            'total' => Contact::count(),
            'pending' => Contact::where('status', 'pending')->count(),
            'unread' => Contact::where('is_read', false)->count(),
            'replied' => Contact::where('status', 'replied')->count(),
        ];
    }

    public function viewContact($id): void
    {
        $this->selectedContact = Contact::with(['repliedBy', 'user'])->findOrFail($id);
        
        // Marquer comme lu
        if (!$this->selectedContact->is_read) {
            $this->selectedContact->update([
                'is_read' => true,
                'read_at' => now(),
                'status' => 'read',
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
            'replyMessage.required' => 'Please enter a reply message.',
            'replyMessage.min' => 'The reply must be at least 3 characters.',
        ]);

        // Mettre à jour le contact
        $this->selectedContact->update([
            'status' => 'replied',
            'replied_by' => auth()->id(),
            'reply_message' => $this->replyMessage,
            'replied_at' => now(),
        ]);

        // Envoyer l'email de réponse
        $this->sendReplyEmail();

        $this->success('Reply sent successfully!');
        $this->showReplyModal = false;
        $this->replyMessage = '';
    }

    private function sendReplyEmail(): void
    {
        // Implémentez l'envoi d'email ici
        // Mail::to($this->selectedContact->email)->send(new ContactReplyMail($this->selectedContact, $this->replyMessage));
    }

    public function archiveContact($id): void
    {
        $contact = Contact::findOrFail($id);
        $contact->update(['status' => 'archived']);
        $this->success('Contact message archived.');
    }

    public function deleteContact($id): void
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();
        $this->success('Contact message deleted permanently.');
    }

    public function markAsRead($id): void
    {
        $contact = Contact::findOrFail($id);
        $contact->update([
            'is_read' => true,
            'read_at' => now(),
            'status' => 'read',
        ]);
        $this->success('Message marked as read.');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'readFilter']);
        $this->resetPage();
        $this->success('Filters cleared.');
    }

    public function getStatusColor($status): string
    {
        return match($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'read' => 'bg-blue-100 text-blue-800',
            'replied' => 'bg-green-100 text-green-800',
            'archived' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusLabel($status): string
    {
        return match($status) {
            'pending' => 'Pending',
            'read' => 'Read',
            'replied' => 'Replied',
            'archived' => 'Archived',
            default => $status,
        };
    }
}
?>

<!-- resources/views/livewire/admin/contacts.blade.php -->

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">
        
        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📬 {{ __('Contact Messages') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Manage messages from your website contact form') }}</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <div class="bg-white rounded-lg p-3 text-center shadow-sm border-l-4 border-l-[#FF6B35]">
                <p class="text-xs text-gray-500">{{ __('Total') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->stats['total'] }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-yellow-500">
                <p class="text-xs text-gray-500">{{ __('Pending') }}</p>
                <p class="text-xl font-bold text-yellow-600">{{ $this->stats['pending'] }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-blue-500">
                <p class="text-xs text-gray-500">{{ __('Unread') }}</p>
                <p class="text-xl font-bold text-blue-600">{{ $this->stats['unread'] }}</p>
            </div>
            <div class="p-3 text-center bg-white border-l-4 rounded-lg shadow-sm border-l-green-500">
                <p class="text-xs text-gray-500">{{ __('Replied') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->stats['replied'] }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="relative">
                        <x-input
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Search by name, email, subject...') }}"
                            icon="o-magnifying-glass"
                            class="w-full" />
                    </div>
                    
                    <select wire:model.live="statusFilter" class="px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('All Status') }}</option>
                        <option value="pending">{{ __('Pending') }}</option>
                        <option value="read">{{ __('Read') }}</option>
                        <option value="replied">{{ __('Replied') }}</option>
                        <option value="archived">{{ __('Archived') }}</option>
                    </select>
                    
                    <select wire:model.live="readFilter" class="px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('All Messages') }}</option>
                        <option value="unread">{{ __('Unread') }}</option>
                        <option value="read">{{ __('Read') }}</option>
                    </select>
                </div>

                @if($search || $statusFilter || $readFilter)
                    <div class="flex justify-end">
                        <button wire:click="clearFilters" class="text-sm text-[#FF6B35] hover:underline">
                            <x-icon name="o-x-mark" class="inline w-4 h-4 mr-1" />
                            {{ __('Clear filters') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Contacts List -->
        @if($this->contacts->count() > 0)
            <!-- Version Desktop -->
            <div class="hidden overflow-hidden bg-white shadow-sm md:block rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Contact') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Subject') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Received') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->contacts as $contact)
                            <tr class="border-b hover:bg-gray-50 transition {{ !$contact->is_read ? 'bg-yellow-50' : '' }}">
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $contact->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $contact->email }}</p>
                                        @if($contact->phone)
                                            <p class="text-xs text-gray-400">{{ $contact->phone }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-800">{{ Str::limit($contact->subject, 40) }}</p>
                                    <p class="mt-1 text-xs text-gray-400">{{ Str::limit($contact->message, 50) }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full {{ $this->getStatusColor($contact->status) }}">
                                        @if($contact->status === 'pending')
                                            <x-icon name="o-clock" class="w-3 h-3" />
                                        @elseif($contact->status === 'replied')
                                            <x-icon name="o-check-circle" class="w-3 h-3" />
                                        @elseif($contact->status === 'read')
                                            <x-icon name="o-eye" class="w-3 h-3" />
                                        @else
                                            <x-icon name="o-archive-box" class="w-3 h-3" />
                                        @endif
                                        {{ $this->getStatusLabel($contact->status) }}
                                    </span>
                                    @if(!$contact->is_read)
                                        <span class="ml-1 px-1.5 py-0.5 text-[10px] rounded-full bg-red-100 text-red-600">
                                            {{ __('New') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">
                                    {{ $contact->time_ago }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="viewContact({{ $contact->id }})" 
                                                class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg transition"
                                                title="{{ __('View') }}">
                                            <x-icon name="o-eye" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="openReplyModal({{ $contact->id }})" 
                                                class="p-1.5 text-gray-400 hover:text-green-600 rounded-lg transition"
                                                title="{{ __('Reply') }}">
                                            <x-icon name="o-paper-airplane" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="archiveContact({{ $contact->id }})" 
                                                class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg transition"
                                                title="{{ __('Archive') }}">
                                            <x-icon name="o-archive-box" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="deleteContact({{ $contact->id }})" 
                                                wire:confirm="{{ __('Delete this message?') }}"
                                                class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition"
                                                title="{{ __('Delete') }}">
                                            <x-icon name="o-trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-gray-50">
                    {{ $this->contacts->links() }}
                </div>
            </div>

            <!-- Version Mobile -->
            <div class="space-y-3 md:hidden">
                @foreach($this->contacts as $contact)
                <div class="bg-white rounded-xl shadow-sm p-4 {{ !$contact->is_read ? 'border-l-4 border-l-yellow-500' : '' }}">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $contact->name }}</p>
                            <p class="text-xs text-gray-500">{{ $contact->email }}</p>
                        </div>
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $this->getStatusColor($contact->status) }}">
                            {{ $this->getStatusLabel($contact->status) }}
                        </span>
                    </div>
                    
                    <p class="mb-1 text-sm font-medium text-gray-700">{{ $contact->subject }}</p>
                    <p class="mb-2 text-xs text-gray-500">{{ Str::limit($contact->message, 80) }}</p>
                    
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                        <span class="text-xs text-gray-400">{{ $contact->time_ago }}</span>
                        <div class="flex gap-2">
                            <button wire:click="viewContact({{ $contact->id }})" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg">
                                <x-icon name="o-eye" class="w-4 h-4" />
                            </button>
                            <button wire:click="openReplyModal({{ $contact->id }})" class="p-1.5 text-gray-400 hover:text-green-600 rounded-lg">
                                <x-icon name="o-paper-airplane" class="w-4 h-4" />
                            </button>
                            <button wire:click="deleteContact({{ $contact->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg">
                                <x-icon name="o-trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
                
                <div class="mt-4">
                    {{ $this->contacts->links() }}
                </div>
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                <x-icon name="o-inbox" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('No messages found') }}</h3>
                <p class="text-gray-500">{{ __('No contact messages match your filters.') }}</p>
            </div>
        @endif

        <!-- View Modal -->
        @if($showViewModal && $selectedContact)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="showViewModal = false">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="sticky top-0 p-5 bg-white border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Message Details') }}</h3>
                        <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600">
                            <x-icon name="o-x-mark" class="w-6 h-6" />
                        </button>
                    </div>
                </div>
                
                <div class="p-5 space-y-4">
                    <!-- Sender info -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-xs text-gray-500">{{ __('Name') }}</label>
                            <p class="font-medium text-gray-900">{{ $selectedContact->name }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">{{ __('Email') }}</label>
                            <p class="font-medium text-gray-900">{{ $selectedContact->email }}</p>
                        </div>
                        @if($selectedContact->phone)
                        <div>
                            <label class="text-xs text-gray-500">{{ __('Phone') }}</label>
                            <p class="font-medium text-gray-900">{{ $selectedContact->phone }}</p>
                        </div>
                        @endif
                        <div>
                            <label class="text-xs text-gray-500">{{ __('Received') }}</label>
                            <p class="font-medium text-gray-900">{{ $selectedContact->formatted_date }}</p>
                        </div>
                    </div>
                    
                    <!-- Subject -->
                    <div>
                        <label class="text-xs text-gray-500">{{ __('Subject') }}</label>
                        <p class="font-medium text-gray-900">{{ $selectedContact->subject }}</p>
                    </div>
                    
                    <!-- Message -->
                    <div>
                        <label class="text-xs text-gray-500">{{ __('Message') }}</label>
                        <div class="p-3 mt-1 rounded-lg bg-gray-50">
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $selectedContact->message }}</p>
                        </div>
                    </div>
                    
                    <!-- Reply info -->
                    @if($selectedContact->status === 'replied')
                    <div class="p-3 border border-green-200 rounded-lg bg-green-50">
                        <div class="flex items-center gap-2 mb-2">
                            <x-icon name="o-check-circle" class="w-5 h-5 text-green-600" />
                            <span class="font-medium text-green-800">{{ __('Reply sent') }}</span>
                        </div>
                        <p class="text-sm text-green-700">{{ $selectedContact->reply_message }}</p>
                        <p class="mt-2 text-xs text-green-600">
                            {{ __('Replied by') }} {{ $selectedContact->repliedBy?->name }} • {{ $selectedContact->replied_at?->format('d.m.Y H:i') }}
                        </p>
                    </div>
                    @endif
                </div>
                
                <div class="flex justify-end gap-3 p-5 border-t bg-gray-50">
                    <button wire:click="openReplyModal({{ $selectedContact->id }})" 
                            class="px-4 py-2 text-white transition bg-green-600 rounded-lg hover:bg-green-700">
                        <x-icon name="o-paper-airplane" class="inline w-4 h-4 mr-1" />
                        {{ __('Reply') }}
                    </button>
                    <button wire:click="$set('showViewModal', false)" class="px-4 py-2 text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Reply Modal -->
        @if($showReplyModal && $selectedContact)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="showReplyModal = false">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="sticky top-0 p-5 bg-white border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">{{ __('Reply to') }} {{ $selectedContact->name }}</h3>
                        <button wire:click="$set('showReplyModal', false)" class="text-gray-400 hover:text-gray-600">
                            <x-icon name="o-x-mark" class="w-6 h-6" />
                        </button>
                    </div>
                </div>
                
                <div class="p-5 space-y-4">
                    <div class="p-3 rounded-lg bg-gray-50">
                        <p class="mb-1 text-sm text-gray-600">{{ __('Original message') }}:</p>
                        <p class="italic text-gray-700">"{{ Str::limit($selectedContact->message, 150) }}"</p>
                    </div>
                    
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Your reply') }} *</label>
                        <textarea wire:model="replyMessage" 
                                  rows="6"
                                  class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]"
                                  placeholder="{{ __('Write your reply here...') }}"></textarea>
                        @error('replyMessage')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <x-icon name="o-information-circle" class="w-4 h-4" />
                        <span>{{ __('The customer will receive your reply by email') }}</span>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 p-5 border-t bg-gray-50">
                    <button wire:click="$set('showReplyModal', false)" class="px-4 py-2 text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="sendReply" class="px-4 py-2 text-white transition bg-green-600 rounded-lg hover:bg-green-700">
                        <x-icon name="o-paper-airplane" class="inline w-4 h-4 mr-1" />
                        {{ __('Send Reply') }}
                    </button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>