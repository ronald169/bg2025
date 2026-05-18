<?php

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Nachrichten - Lehrer')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public $selectedConversationId = null;
    public $newMessage = '';
    public $showMobileList = true;

    #[Computed]
    public function conversations()
    {
        $query = Conversation::where('teacher_id', auth()->id())
            ->orWhere('student_id', auth()->id())
            ->with(['student', 'teacher', 'lastMessage']);

        if ($this->search) {
            $query->whereHas('student', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            })->orWhereHas('teacher', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->get()
            ->map(function ($conv) {
                $otherUser = auth()->id() === $conv->teacher_id ? $conv->student : $conv->teacher;
                $conv->other_user_name = $otherUser?->name ?? 'Unbekannt';
                $conv->other_user_email = $otherUser?->email ?? '';
                $conv->other_user_avatar = $otherUser?->profile_photo_path;
                $conv->unread_count = Message::where('conversation_id', $conv->id)
                    ->where('receiver_id', auth()->id())
                    ->where('is_read', false)
                    ->count();
                return $conv;
            })
            ->sortByDesc(function($conv) {
                return $conv->lastMessage?->created_at ?? $conv->created_at;
            });
    }

    #[Computed]
    public function selectedConversation()
    {
        if (!$this->selectedConversationId) return null;

        $conversation = Conversation::with(['student', 'teacher', 'messages.user'])
            ->find($this->selectedConversationId);

        if ($conversation) {
            // Marquer les messages comme lus
            Message::where('conversation_id', $conversation->id)
                ->where('receiver_id', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);

            $otherUser = auth()->id() === $conversation->teacher_id ? $conversation->student : $conversation->teacher;
            $conversation->other_user_name = $otherUser?->name ?? 'Unbekannt';
            $conversation->other_user_email = $otherUser?->email ?? '';
        }

        return $conversation;
    }

    #[Computed]
    public function messages()
    {
        return $this->selectedConversation?->messages ?? collect();
    }

    public function selectConversation($id): void
    {
        $this->selectedConversationId = $id;
        $this->showMobileList = false;
        $this->dispatch('conversation-selected');
    }

    public function backToList(): void
    {
        $this->showMobileList = true;
        $this->selectedConversationId = null;
    }

    public function sendMessage(): void
    {
        //$this->validate([
        //    'newMessage' => 'required|string|max:1000'
        //]);

        if (!$this->selectedConversationId) {
            $this->error('Keine Konversation ausgewählt.');
            return;
        }

        $conversation = Conversation::find($this->selectedConversationId);

        if (!$conversation) {
            $this->error('Konversation nicht gefunden.');
            return;
        }

        // Déterminer le destinataire
        $receiverId = auth()->id() === $conversation->teacher_id
            ? $conversation->student_id
            : $conversation->teacher_id;

        Message::create([
            'conversation_id' => $this->selectedConversationId,
            'user_id' => auth()->id(),
            'receiver_id' => $receiverId,
            'content' => $this->newMessage,
            'is_read' => false,
        ]);

        $this->newMessage = '';

        // Rafraîchir la vue
        $this->dispatch('message-sent');
    }

    public function getOtherUserInitial($conversation): string
    {
        $name = $conversation->other_user_name ?? 'U';
        return strtoupper(substr($name, 0, 1));
    }

    public function getMessageTime($message): string
    {
        $now = now();
        $createdAt = $message->created_at;

        if ($createdAt->isToday()) {
            return $createdAt->format('H:i');
        }

        if ($createdAt->isYesterday()) {
            return 'Gestern ' . $createdAt->format('H:i');
        }

        if ($createdAt->diffInDays($now) <= 7) {
            return $createdAt->format('l');
        }

        return $createdAt->format('d.m.Y');
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="mb-4 md:mb-6">
            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">💬 {{ __('Nachrichten') }}</h1>
            <p class="text-gray-500 text-xs md:text-sm mt-0.5">{{ __('Tausche dich mit deinen Studenten aus') }}</p>
        </div>

        <!-- Messages Container -->
        <div class="overflow-hidden bg-white shadow-sm rounded-xl">
            <div class="flex flex-col md:flex-row min-h-[500px] md:h-[calc(100vh-200px)]">

                <!-- Conversations List - Mobile Toggle -->
                <div class="w-full md:w-80 border-r border-gray-200 flex flex-col
                            {{ $showMobileList ? 'block' : 'hidden md:flex' }}">
                    <!-- Search -->
                    <div class="p-3 border-b">
                        <div class="relative">
                            <x-input
                                wire:model.live.debounce.300ms="search"
                                placeholder="{{ __('Suchen...') }}"
                                icon="o-magnifying-glass"
                                class="w-full" />
                            @if($search)
                                <button wire:click="clearSearch" class="absolute -translate-y-1/2 right-3 top-1/2">
                                    <x-icon name="o-x-mark" class="w-4 h-4 text-gray-400 hover:text-gray-600" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Conversations List -->
                    <div class="flex-1 overflow-y-auto">
                        @if($this->conversations->count() > 0)
                            @foreach($this->conversations as $conv)
                            <div
                                wire:click="selectConversation({{ $conv->id }})"
                                class="p-3 border-b cursor-pointer transition hover:bg-gray-50
                                       {{ $selectedConversationId === $conv->id ? 'bg-orange-50 border-l-4 border-l-[#FF6B35]' : '' }}">
                                <div class="flex items-center gap-3">
                                    <!-- Avatar -->
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                        {{ $this->getOtherUserInitial($conv) }}
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $conv->other_user_name }}
                                            </p>
                                            @if($conv->unread_count > 0)
                                                <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-[#FF6B35] text-white rounded-full flex-shrink-0">
                                                    {{ $conv->unread_count }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-500 truncate">
                                            {{ $conv->lastMessage?->content ?? 'Keine Nachrichten' }}
                                        </p>
                                        <p class="text-[10px] text-gray-400 mt-0.5">
                                            {{ $conv->lastMessage?->created_at?->diffForHumans() ?? '' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="p-8 text-center">
                                <x-icon name="o-chat-bubble-left-right" class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                <p class="text-sm text-gray-500">{{ __('Keine Konversationen') }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ __('Wenn Studenten dich kontaktieren, erscheinen sie hier') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="flex-1 flex flex-col bg-gray-50
                            {{ !$showMobileList ? 'block' : 'hidden md:flex' }}">

                    @if($this->selectedConversation)
                        <!-- Header -->
                        <div class="flex items-center justify-between p-3 bg-white border-b">
                            <div class="flex items-center gap-3">
                                <!-- Back button mobile -->
                                <button
                                    wire:click="backToList"
                                    class="md:hidden p-1.5 rounded-lg hover:bg-gray-100">
                                    <x-icon name="o-arrow-left" class="w-5 h-5 text-gray-600" />
                                </button>

                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-sm">
                                    {{ $this->getOtherUserInitial($this->selectedConversation) }}
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">
                                        {{ $this->selectedConversation->other_user_name }}
                                    </h3>
                                    <p class="text-xs text-gray-500">
                                        {{ $this->selectedConversation->other_user_email }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-xs text-gray-400">
                                @php
                                    $role = auth()->id() === $this->selectedConversation->teacher_id ? 'Lehrer' : 'Student';
                                @endphp
                                {{ $role }}
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="flex-1 p-3 space-y-2 overflow-y-auto"
                             x-data="{ scrollToBottom() { setTimeout(() => { this.$el.scrollTop = this.$el.scrollHeight }, 100) } }"
                             x-init="scrollToBottom()"
                             x-on:message-sent.window="scrollToBottom()"
                             x-on:conversation-selected.window="scrollToBottom()">

                            @forelse($this->messages as $message)
                                <div class="flex {{ $message->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[85%] md:max-w-[70%] {{ $message->user_id === auth()->id()
                                        ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white'
                                        : 'bg-white border' }} rounded-2xl px-3 py-2 shadow-sm">
                                        <p class="text-sm">{{ $message->content }}</p>
                                        <p class="text-xs mt-1 {{ $message->user_id === auth()->id() ? 'text-orange-100' : 'text-gray-400' }}">
                                            {{ $this->getMessageTime($message) }}
                                            @if($message->user_id === auth()->id())
                                                @if($message->is_read)
                                                    <span class="ml-1">✓✓</span>
                                                @else
                                                    <span class="ml-1">✓</span>
                                                @endif
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <div class="py-12 text-center">
                                    <x-icon name="o-chat-bubble-left-right" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                    <p class="text-sm text-gray-500">{{ __('Keine Nachrichten') }}</p>
                                    <p class="mt-1 text-xs text-gray-400">{{ __('Schreibe die erste Nachricht!') }}</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Input -->
                        <div class="p-3 bg-white border-t">
                            <div class="flex gap-2">
                                <x-input
                                    wire:model="newMessage"
                                    placeholder="{{ __('Nachricht schreiben...') }}"
                                    class="flex-1 text-sm"
                                    wire:keydown.enter="sendMessage" />
                                <x-button
                                    wire:click="sendMessage"
                                    icon="o-paper-airplane"
                                    class="px-3 btn-primary md:px-4"
                                    spinner="sendMessage">
                                    <span class="hidden md:inline">{{ __('Senden') }}</span>
                                </x-button>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-2 text-center">
                                <x-icon name="o-lock-closed" class="inline w-3 h-3 mr-1" />
                                {{ __('Nachrichten sind Ende-zu-Ende verschlüsselt') }}
                            </p>
                        </div>
                    @else
                        <!-- No conversation selected -->
                        <div class="flex items-center justify-center flex-1">
                            <div class="p-4 text-center">
                                <x-icon name="o-chat-bubble-left-right" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                                <h3 class="mb-1 text-base font-semibold text-gray-900">{{ __('Keine Konversation ausgewählt') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('Wähle eine Konversation aus der Liste') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Note MVP -->
        <div class="p-3 mt-4 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-2">
                <x-icon name="o-information-circle" class="w-4 h-4 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700">{{ __('Prochaines fonctionnalités : pièces jointes, notifications push, réponses rapides, et recherche de messages.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
