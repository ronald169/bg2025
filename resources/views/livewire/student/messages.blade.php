<?php

use App\Models\Conversation;
use App\Models\Message;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\{Computed, Validate};
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new
#[Title('Nachrichten - Deutsch lernen')]
#[Layout('components.layouts.dashboard-student')]
class extends Component {
    use Toast;

    public $selectedConversationId = null;

    public $newMessage = '';

    protected function rules()
    {
        return [
            'newMessage' => ['required', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function conversations()
    {
        return Conversation::where('student_id', auth()->id())
            ->orWhere('teacher_id', auth()->id())
            ->with(['teacher', 'student', 'lastMessage'])
            ->get()
            ->map(function ($conv) {
                // Déterminer l'autre participant
                $otherUser = auth()->id() === $conv->student_id ? $conv->teacher : $conv->student;
                $conv->other_user_name = $otherUser?->name ?? 'Benutzer';
                $conv->other_user_avatar = $otherUser?->profile_photo_path;
                $conv->unread_count = Message::where('conversation_id', $conv->id)
                    ->where('receiver_id', auth()->id())
                    ->where('is_read', false)
                    ->count();
                return $conv;
            });
    }

    #[Computed]
    public function selectedConversation()
    {
        if (!$this->selectedConversationId) return null;

        $conversation = Conversation::with(['messages.user', 'teacher', 'student'])
            ->find($this->selectedConversationId);

        if ($conversation) {
            // Marquer les messages comme lus
            Message::where('conversation_id', $conversation->id)
                ->where('receiver_id', auth()->id())
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return $conversation;
    }

    #[Computed]
    public function messages()
    {
        if (!$this->selectedConversationId) return collect();

        $conversation = Conversation::with(['messages.user'])
            ->find($this->selectedConversationId);

        return $conversation?->messages ?? collect();
    }

    public function selectConversation($id): void
    {
        $this->selectedConversationId = $id;
        $this->dispatch('conversation-selected');
    }

    public function sendMessage(): void
    {
        //$this->validate([
        //    'newMessage' => ['required', 'string', 'max:1000'],
        //]);

        if (!$this->selectedConversationId) {
            $this->error('Keine Konversation ausgewählt.');
            return;
        }

        // Récupérer la conversation directement depuis la base de données
        $conversation = Conversation::find($this->selectedConversationId);

        if (!$conversation) {
            $this->error('Konversation nicht gefunden.');
            return;
        }

        // Déterminer le destinataire
        $receiverId = auth()->id() === $conversation->student_id
            ? $conversation->teacher_id
            : $conversation->student_id;

        if (!$receiverId) {
            $this->error('Empfänger nicht gefunden.');
            return;
        }

        // Créer le message
        $message = Message::create([
            'conversation_id' => $this->selectedConversationId,
            'user_id' => auth()->id(),
            'receiver_id' => $receiverId,
            'content' => $this->newMessage,
            'is_read' => false,
        ]);

        $this->newMessage = '';

        // Rafraîchir la vue
        $this->dispatch('message-sent');

        $this->success('Nachricht gesendet!');
    }

    #[On('message-sent')]
    #[On('conversation-selected')]
    public function refresh()
    {
        // Ceci force le rechargement des propriétés computed
        unset($this->selectedConversation);
        unset($this->messages);
        unset($this->conversations);
    }

    public function getOtherUserName($conversation): string
    {
        if (!$conversation) return 'Benutzer';

        if (auth()->id() === ($conversation->student_id ?? null)) {
            return $conversation->teacher?->name ?? 'Lehrer';
        }
        return $conversation->student?->name ?? 'Schüler';
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
            return 'Gestern ' . $createdAt->format('H:i');
        }

        if ($createdAt->diffInDays($now) <= 7) {
            return $createdAt->format('l H:i');
        }

        return $createdAt->format('d.m.Y H:i');
    }
}
?>

<div class="py-4">
    <div class="px-4 mx-auto max-w-7xl">

        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900">💬 {{ __('Nachrichten') }}</h1>
            <p class="text-sm text-gray-600">{{ __('Tausche dich mit deinen Lehrern aus') }}</p>
        </div>

        <div class="flex h-[calc(100vh-16rem)] bg-white rounded-xl shadow-sm overflow-hidden">
            <!-- Conversations List -->
            <div class="flex flex-col border-r w-80 bg-gray-50">
                <div class="p-4 bg-white border-b">
                    <h2 class="font-semibold text-gray-900">Konversationen</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ $this->conversations->count() }} Unterhaltungen</p>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @if($this->conversations->count() > 0)
                        @foreach($this->conversations as $conv)
                        <div
                            wire:click="selectConversation({{ $conv->id }})"
                            class="p-4 border-b cursor-pointer transition hover:bg-gray-100
                                   {{ $selectedConversationId === $conv->id ? 'bg-orange-50 border-l-4 border-l-[#FF6B35]' : 'bg-white' }}">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold">
                                    {{ $this->getOtherUserInitial($conv) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="font-medium text-gray-900 truncate">{{ $this->getOtherUserName($conv) }}</p>
                                        @if($conv->unread_count > 0)
                                            <span class="px-2 py-0.5 text-xs bg-[#FF6B35] text-white rounded-full">
                                                {{ $conv->unread_count }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 truncate mt-0.5">
                                        {{ $conv->lastMessage?->content ?? 'Keine Nachrichten' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-400">
                                        {{ $conv->lastMessage?->created_at?->diffForHumans() ?? '' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="p-8 text-center">
                            <x-icon name="o-chat-bubble-left-right" class="w-12 h-12 mx-auto mb-3 text-gray-300" />
                            <p class="text-sm text-gray-500">Keine Konversationen</p>
                            <p class="mt-1 text-xs text-gray-400">Starte eine neue Unterhaltung</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex flex-col flex-1 bg-white">
                @if($this->selectedConversationId && $this->selectedConversation)
                    <!-- Header -->
                    <div class="p-4 bg-white border-b">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold">
                                {{ $this->getOtherUserInitial($this->selectedConversation) }}
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $this->getOtherUserName($this->selectedConversation) }}</h3>
                                <p class="text-xs text-gray-500">
                                    @php
                                        $isTeacher = ($this->selectedConversation->teacher_id ?? null) === auth()->id()
                                            || ($this->selectedConversation->teacher?->id ?? null) === auth()->id();
                                    @endphp
                                    {{ $isTeacher ? 'Lehrer' : 'Schüler' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="flex-1 p-4 space-y-3 overflow-y-auto bg-gray-50"
                         x-data="{ scrollToBottom() { setTimeout(() => { this.$el.scrollTop = this.$el.scrollHeight }, 100) } }"
                         x-init="scrollToBottom()"
                         x-on:message-sent.window="scrollToBottom()"
                         x-on:conversation-selected.window="scrollToBottom()">
                        @forelse($this->messages as $message)
                            <div class="flex {{ $message->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[70%] {{ $message->user_id === auth()->id()
                                    ? 'bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] text-white'
                                    : 'bg-white border' }} rounded-2xl px-4 py-2 shadow-sm">
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
                                <x-icon name="o-chat-bubble-left-right" class="w-16 h-16 mx-auto mb-3 text-gray-300" />
                                <p class="text-gray-500">Keine Nachrichten</p>
                                <p class="mt-1 text-sm text-gray-400">Schreibe die erste Nachricht!</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Input -->
                    <div class="p-4 bg-white border-t">
                        <div class="flex gap-2">
                            <x-input
                                wire:model="newMessage"
                                placeholder="{{ __('Nachricht schreiben...') }}"
                                class="flex-1"
                                wire:keydown.enter="sendMessage" />
                            <x-button
                                wire:click="sendMessage"
                                icon="o-paper-airplane"
                                class="btn-primary"
                                spinner="sendMessage">
                                Senden
                            </x-button>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">
                            <x-icon name="o-lock-closed" class="inline w-3 h-3 mr-1" />
                            Nachrichten sind Ende-zu-Ende verschlüsselt
                        </p>
                    </div>
                @else
                    <div class="flex items-center justify-center flex-1">
                        <div class="text-center">
                            <x-icon name="o-chat-bubble-left-right" class="w-20 h-20 mx-auto mb-4 text-gray-300" />
                            <h3 class="mb-2 text-lg font-semibold text-gray-900">Keine Konversation ausgewählt</h3>
                            <p class="text-gray-500">Wähle eine Konversation aus der Liste</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Note MVP -->
        <div class="p-3 mt-4 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-2">
                <x-icon name="o-information-circle" class="w-4 h-4 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700">Prochaines fonctionnalités : pièces jointes, notifications push, réponses rapides, et recherche de messages.</p>
                </div>
            </div>
        </div>
    </div>
</div>
