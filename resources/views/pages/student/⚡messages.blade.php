<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Mary\Traits\Toast;

new
#[Title('Messages - German Learning')]
#[Layout('layouts.app')]
class extends Component {
    use Toast;

    public $selectedConversationId = null;
    public $newMessage = '';
    public $searchUser = '';
    public $searchResults = [];

    protected function rules()
    {
        return [
            'newMessage' => ['required', 'string', 'max:1000'],
        ];
    }

    // Getters
    public function getConversations()
    {
        return Conversation::where('student_id', auth()->id())
            ->orWhere('teacher_id', auth()->id())
            ->with(['teacher', 'student', 'lastMessage'])
            ->get()
            ->map(function ($conv) {
                $otherUser = auth()->id() === ($conv->student_id ?? null) ? $conv->teacher : $conv->student;
                $conv->other_user_name = $otherUser?->name ?? 'User';
                $conv->unread_count = Message::where('conversation_id', $conv->id)
                    ->where('receiver_id', auth()->id())
                    ->where('is_read', false)
                    ->count();
                return $conv;
            });
    }

    public function getSelectedConversation()
    {
        if (!$this->selectedConversationId) return null;

        $conversation = Conversation::with(['messages.user', 'teacher', 'student'])
            ->find($this->selectedConversationId);

        if ($conversation) {
            Message::where('conversation_id', $conversation->id)
                ->where('receiver_id', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return $conversation;
    }

    public function getMessages()
    {
        if (!$this->selectedConversationId) return collect();

        $conversation = Conversation::with(['messages.user'])
            ->find($this->selectedConversationId);

        return $conversation?->messages ?? collect();
    }

    // Search for users to start a new conversation
    public function updatedSearchUser()
    {
        if (strlen($this->searchUser) < 2) {
            $this->searchResults = [];
            return;
        }

        $this->searchResults = User::where('id', '!=', auth()->id())
            ->where('status', 'active')
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchUser . '%')
                  ->orWhere('email', 'like', '%' . $this->searchUser . '%');
            })
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function startConversation($userId)
    {
        $otherUser = User::find($userId);
        if (!$otherUser) {
            $this->error('User not found.');
            return;
        }

        // Check if conversation already exists
        $existingConversation = Conversation::where(function ($q) use ($userId) {
            $q->where('student_id', auth()->id())->where('teacher_id', $userId);
        })->orWhere(function ($q) use ($userId) {
            $q->where('student_id', $userId)->where('teacher_id', auth()->id());
        })->first();

        if ($existingConversation) {
            $this->selectedConversationId = $existingConversation->id;
            $this->searchUser = '';
            $this->searchResults = [];
            $this->dispatch('conversation-selected');
            $this->success('Conversation loaded.');
        } else {
            // Create a new conversation
            $isTeacher = $otherUser->role === 'teacher';
            $conversation = Conversation::create([
                'student_id' => $isTeacher ? auth()->id() : $userId,
                'teacher_id' => $isTeacher ? $userId : auth()->id(),
            ]);
            $this->selectedConversationId = $conversation->id;
            $this->searchUser = '';
            $this->searchResults = [];
            $this->dispatch('conversation-selected');
            $this->success('New conversation started!');
        }
    }

    public function selectConversation($id): void
    {
        $this->selectedConversationId = $id;
        $this->searchUser = '';
        $this->searchResults = [];
        $this->dispatch('conversation-selected');
    }

    public function sendMessage(): void
    {
        // $this->validate();

        if (!$this->selectedConversationId) {
            $this->error('No conversation selected.');
            return;
        }

        $conversation = Conversation::find($this->selectedConversationId);

        if (!$conversation) {
            $this->error('Conversation not found.');
            return;
        }

        $receiverId = auth()->id() === $conversation->student_id
            ? $conversation->teacher_id
            : $conversation->student_id;

        if (!$receiverId) {
            $this->error('Receiver not found.');
            return;
        }

        Message::create([
            'conversation_id' => $this->selectedConversationId,
            'user_id' => auth()->id(),
            'receiver_id' => $receiverId,
            'content' => clean_text($this->newMessage, 500),
            'is_read' => false,
        ]);

        $this->newMessage = '';
        $this->dispatch('message-sent');
        $this->success('Message sent!');
    }

    public function getOtherUserName($conversation): string
    {
        if (!$conversation) return 'User';

        if (auth()->id() === ($conversation->student_id ?? null)) {
            return $conversation->teacher?->name ?? 'Teacher';
        }
        return $conversation->student?->name ?? 'Student';
    }

    public function getOtherUserInitial($conversation): string
    {
        $name = $this->getOtherUserName($conversation);
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
            return 'Yesterday ' . $createdAt->format('H:i');
        }

        if ($createdAt->diffInDays($now) <= 7) {
            return $createdAt->format('l H:i');
        }

        return $createdAt->format('d.m.Y H:i');
    }

    public function render()
    {
        return $this->view([
            'conversations' => $this->getConversations(),
            'selectedConversation' => $this->getSelectedConversation(),
            'messages' => $this->getMessages(),
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="mb-4">
            <h1 class="text-2xl font-bold md:text-3xl">💬 {{ __('Messages') }}</h1>
            <p class="text-sm text-base-content/70">{{ __('Chat with your teachers and fellow students') }}</p>
        </div>

        <div class="flex flex-col lg:flex-row h-[calc(100vh-12rem)] bg-base-100 rounded-xl shadow-sm overflow-hidden">
            <!-- Sidebar: Conversations & Search -->
            <div class="flex flex-col w-full border-r lg:w-80 border-base-200">
                <!-- Search user -->
                <div class="p-4 border-b">
                    <x-input
                        wire:model.live.debounce.300ms="searchUser"
                        icon="o-magnifying-glass"
                        placeholder="{{ __('Search for a user...') }}"
                        class="w-full"
                        clearable />
                </div>

                <!-- Search results -->
                @if($searchUser && count($searchResults) > 0)
                    <div class="p-2 border-b">
                        <p class="px-2 mb-2 text-xs font-semibold tracking-wider uppercase text-base-content/60">{{ __('Start new conversation') }}</p>
                        @foreach($searchResults as $user)
                            <div wire:click="startConversation({{ $user['id'] }})"
                                 class="flex items-center gap-3 p-2 transition rounded-lg cursor-pointer hover:bg-base-200">
                                <div class="flex items-center justify-center w-10 h-10 font-bold rounded-full bg-primary/20 text-primary">
                                    {{ strtoupper(substr($user['name'], 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-medium text-base-content">{{ $user['name'] }}</div>
                                    <div class="text-xs text-base-content/50">{{ $user['email'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Conversations list -->
                <div class="flex-1 overflow-y-auto">
                    <div class="p-3">
                        <p class="px-2 mb-2 text-xs font-semibold tracking-wider uppercase text-base-content/60">{{ __('Conversations') }}</p>
                        @if($conversations->count() > 0)
                            @foreach($conversations as $conv)
                                <div wire:click="selectConversation({{ $conv->id }})"
                                     class="p-3 rounded-lg cursor-pointer transition hover:bg-base-200
                                            {{ $selectedConversationId === $conv->id ? 'bg-primary/10 border-l-4 border-primary' : '' }}">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-10 h-10 font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                            {{ $this->getOtherUserInitial($conv) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <p class="font-medium truncate text-base-content">{{ $this->getOtherUserName($conv) }}</p>
                                                @if($conv->unread_count > 0)
                                                    <span class="px-2 py-0.5 text-xs bg-primary text-white rounded-full">{{ $conv->unread_count }}</span>
                                                @endif
                                            </div>
                                            <p class="text-xs truncate text-base-content/50">
                                                {{ $conv->lastMessage?->content ?? __('No messages yet') }}
                                            </p>
                                            <p class="mt-1 text-xs text-base-content/40">
                                                {{ $conv->lastMessage?->created_at?->diffForHumans() ?? '' }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="py-8 text-center">
                                <x-icon name="o-chat-bubble-left-right" class="w-12 h-12 mx-auto mb-3 text-base-content/30" />
                                <p class="text-sm text-base-content/60">{{ __('No conversations yet') }}</p>
                                <p class="text-xs text-base-content/50">{{ __('Search for a user above to start chatting') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Main chat area -->
            <div class="flex flex-col flex-1 bg-base-100">
                @if($selectedConversationId && $selectedConversation)
                    <!-- Chat header -->
                    <div class="flex items-center gap-3 p-4 border-b">
                        <div class="flex items-center justify-center w-10 h-10 font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                            {{ $this->getOtherUserInitial($selectedConversation) }}
                        </div>
                        <div>
                            <h3 class="font-semibold text-base-content">{{ $this->getOtherUserName($selectedConversation) }}</h3>
                            <p class="text-xs text-base-content/60">
                                @php
                                    $isTeacher = ($selectedConversation->teacher_id ?? null) === auth()->id()
                                        || ($selectedConversation->teacher?->id ?? null) === auth()->id();
                                @endphp
                                {{ $isTeacher ? __('Teacher') : __('Student') }}
                            </p>
                        </div>
                    </div>

                    <!-- Messages list -->
                    <div class="flex-1 p-4 space-y-3 overflow-y-auto bg-base-200"
                         x-data="{ scrollToBottom() { setTimeout(() => { this.$el.scrollTop = this.$el.scrollHeight }, 100) } }"
                         x-init="scrollToBottom()"
                         x-on:message-sent.window="scrollToBottom()"
                         x-on:conversation-selected.window="scrollToBottom()">
                        @forelse($messages as $message)
                            <div class="flex {{ $message->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] {{ $message->user_id === auth()->id()
                                    ? 'bg-primary text-white'
                                    : 'bg-base-100 border border-base-200' }} rounded-2xl px-4 py-2 shadow-sm">
                                    <p class="text-sm">{{ $message->content }}</p>
                                    <p class="text-xs mt-1 {{ $message->user_id === auth()->id() ? 'text-primary-content/70' : 'text-base-content/50' }}">
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
                                <x-icon name="o-chat-bubble-left-right" class="w-16 h-16 mx-auto mb-3 text-base-content/30" />
                                <p class="text-base-content/60">{{ __('No messages yet') }}</p>
                                <p class="text-sm text-base-content/50">{{ __('Send the first message!') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Message input -->
                    <div class="p-4 border-t bg-base-100">
                        <div class="flex gap-2">
                            <x-input wire:model="newMessage" placeholder="{{ __('Write a message...') }}" class="flex-1" wire:keydown.enter="sendMessage" />
                            <x-button wire:click="sendMessage" icon="o-paper-airplane" class="btn-primary" spinner="sendMessage">{{ __('Send') }}</x-button>
                        </div>
                        <p class="mt-2 text-xs text-base-content/50">
                            <x-icon name="o-lock-closed" class="inline w-3 h-3 mr-1" />
                            {{ __('End-to-end encrypted') }}
                        </p>
                    </div>
                @else
                    <div class="flex items-center justify-center flex-1">
                        <div class="text-center">
                            <x-icon name="o-chat-bubble-left-right" class="w-20 h-20 mx-auto mb-4 text-base-content/30" />
                            <h3 class="mb-2 text-lg font-semibold text-base-content">{{ __('No conversation selected') }}</h3>
                            <p class="text-base-content/60">{{ __('Select a conversation from the sidebar or search for a user to start chatting') }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
