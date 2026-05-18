<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Mary\Traits\Toast;

new
#[Title('Benutzer verwalten - Admin')]
#[Layout('components.layouts.dashboard-admin')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'role', history: true)]
    public string $roleFilter = '';

    #[Url(as: 'status', history: true)]
    public string $statusFilter = '';

    public bool $showDeleteModal = false;
    public $userToDelete = null;

    #[Computed]
    public function users()
    {
        return User::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%'))
            ->when($this->roleFilter, fn($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    #[Computed]
    public function totalUsers()
    {
        return User::count();
    }

    #[Computed]
    public function studentsCount()
    {
        return User::where('role', 'student')->count();
    }

    #[Computed]
    public function teachersCount()
    {
        return User::where('role', 'teacher')->count();
    }

    #[Computed]
    public function adminsCount()
    {
        return User::where('role', 'admin')->count();
    }

    #[Computed]
    public function activeUsersCount()
    {
        return User::where('status', 'active')->count();
    }

    public function deleteUser($userId): void
    {
        $this->userToDelete = User::findOrFail($userId);
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        if ($this->userToDelete) {
            $userName = $this->userToDelete->name;
            $this->userToDelete->delete();
            $this->success("Benutzer '{$userName}' wurde gelöscht.");
            $this->showDeleteModal = false;
            $this->userToDelete = null;
            $this->resetPage();
        }
    }

    public function toggleStatus($userId): void
    {
        $user = User::findOrFail($userId);
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);
        $this->success($newStatus === 'active' ? 'Benutzer aktiviert' : 'Benutzer deaktiviert');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'statusFilter']);
        $this->resetPage();
        $this->success('Filter zurückgesetzt.');
    }

    public function getRoleBadgeClass($role): string
    {
        return match($role) {
            'admin' => 'bg-red-100 text-red-700',
            'teacher' => 'bg-blue-100 text-blue-700',
            'student' => 'bg-green-100 text-green-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function getRoleIcon($role): string
    {
        return match($role) {
            'admin' => 'o-shield-check',
            'teacher' => 'custom.chalkboard-teacher',
            'student' => 'o-academic-cap',
            default => 'o-user',
        };
    }
}
?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">👥 {{ __('Benutzer verwalten') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Verwalte alle Plattform-Benutzer') }}</p>
            </div>
            <div>
                <a href="{{ route('admin.users.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-white bg-gradient-to-r from-[#FF6B35] to-[#E55A2A] rounded-lg hover:shadow-md transition">
                    <x-icon name="o-plus" class="w-4 h-4" />
                    {{ __('Neuer Benutzer') }}
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-5">
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Gesamt') }}</p>
                <p class="text-xl font-bold text-gray-900">{{ $this->totalUsers }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Studenten') }}</p>
                <p class="text-xl font-bold text-green-600">{{ $this->studentsCount }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Lehrer') }}</p>
                <p class="text-xl font-bold text-blue-600">{{ $this->teachersCount }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Admins') }}</p>
                <p class="text-xl font-bold text-red-600">{{ $this->adminsCount }}</p>
            </div>
            <div class="p-3 text-center bg-white rounded-lg shadow-sm">
                <p class="text-xs text-gray-500">{{ __('Aktiv') }}</p>
                <p class="text-xl font-bold text-[#FF6B35]">{{ $this->activeUsersCount }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="relative">
                        <x-input
                            wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Suchen...') }}"
                            icon="o-magnifying-glass"
                            class="w-full" />
                    </div>

                    <select wire:model.live="roleFilter" class="px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Alle Rollen') }}</option>
                        <option value="student">{{ __('Student') }}</option>
                        <option value="teacher">{{ __('Lehrer') }}</option>
                        <option value="admin">{{ __('Admin') }}</option>
                    </select>

                    <select wire:model.live="statusFilter" class="px-3 py-2 text-sm border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                        <option value="">{{ __('Alle Status') }}</option>
                        <option value="active">{{ __('Aktiv') }}</option>
                        <option value="inactive">{{ __('Inaktiv') }}</option>
                    </select>
                </div>

                @if($search || $roleFilter || $statusFilter)
                    <div class="flex justify-end">
                        <button wire:click="clearFilters" class="text-sm text-[#FF6B35] hover:underline">
                            {{ __('Filter zurücksetzen') }} →
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Users List -->
        @if($this->users->count() > 0)
            <!-- Version Desktop -->
            <div class="hidden overflow-hidden bg-white shadow-sm md:block rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-gray-50">
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Benutzer') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('E-Mail') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Rolle') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left text-gray-600">{{ __('Registriert') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center text-gray-600">{{ __('Aktionen') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->users as $user)
                            <tr class="transition border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-xs">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ Str::limit($user->name, 25) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full {{ $this->getRoleBadgeClass($user->role) }}">
                                        <x-icon :name="$this->getRoleIcon($user->role)" class="w-3 h-3" />
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $user->status === 'active' ? 'Aktiv' : 'Inaktiv' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $user->created_at->format('d.m.Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('admin.users.show', $user) }}" class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg transition" title="Anzeigen">
                                            <x-icon name="o-eye" class="w-4 h-4" />
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="p-1.5 text-gray-400 hover:text-orange-600 rounded-lg transition" title="Bearbeiten">
                                            <x-icon name="o-pencil" class="w-4 h-4" />
                                        </a>
                                        <button wire:click="toggleStatus({{ $user->id }})" class="p-1.5 text-gray-400 hover:text-yellow-600 rounded-lg transition" title="{{ $user->status === 'active' ? 'Deaktivieren' : 'Aktivieren' }}">
                                            <x-icon :name="$user->status === 'active' ? 'o-eye-slash' : 'o-eye'" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="deleteUser({{ $user->id }})" class="p-1.5 text-gray-400 hover:text-red-600 rounded-lg transition" title="Löschen">
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
                    {{ $this->users->links() }}
                </div>
            </div>

            <!-- Version Mobile -->
            <div class="space-y-3 md:hidden">
                @foreach($this->users as $user)
                <div class="p-4 bg-white shadow-sm rounded-xl">
                                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#FF6B35] to-[#1E6091] flex items-center justify-center text-white font-bold text-sm">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ Str::limit($user->name, 20) }}</p>
                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $user->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $user->status === 'active' ? 'Aktiv' : 'Inaktiv' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full {{ $this->getRoleBadgeClass($user->role) }}">
                            <x-icon :name="$this->getRoleIcon($user->role)" class="w-3 h-3" />
                            {{ ucfirst($user->role) }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $user->created_at->format('d.m.Y') }}</span>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                        <a href="{{ route('admin.users.show', $user) }}" class="p-2 text-gray-400 transition rounded-lg hover:text-blue-600">
                            <x-icon name="o-eye" class="w-4 h-4" />
                        </a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="p-2 text-gray-400 transition rounded-lg hover:text-orange-600">
                            <x-icon name="o-pencil" class="w-4 h-4" />
                        </a>
                        <button wire:click="toggleStatus({{ $user->id }})" class="p-2 text-gray-400 transition rounded-lg hover:text-yellow-600">
                            <x-icon :name="$user->status === 'active' ? 'o-eye-slash' : 'o-eye'" class="w-4 h-4" />
                        </button>
                        <button wire:click="deleteUser({{ $user->id }})" class="p-2 text-gray-400 transition rounded-lg hover:text-red-600">
                            <x-icon name="o-trash" class="w-4 h-4" />
                        </button>
                    </div>
                </div>
                @endforeach

                <div class="mt-4">
                    {{ $this->users->links() }}
                </div>
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                <x-icon name="o-users" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('Keine Benutzer gefunden') }}</h3>
                <p class="mb-4 text-gray-500">{{ __('Versuche andere Suchkriterien.') }}</p>
                <button wire:click="clearFilters" class="text-[#FF6B35] hover:underline">
                    {{ __('Filter zurücksetzen') }} →
                </button>
            </div>
        @endif

        <!-- Delete Modal -->
        @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="$set('showDeleteModal', false)">
            <div class="w-full max-w-md overflow-hidden bg-white shadow-xl rounded-xl">
                <div class="p-6 text-center">
                    <div class="flex items-center justify-center w-16 h-16 mx-auto mb-4 bg-red-100 rounded-full">
                        <x-icon name="o-exclamation-triangle" class="w-8 h-8 text-red-600" />
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900">{{ __('Benutzer löschen') }}</h3>
                    <p class="mb-6 text-gray-600">
                        {{ __('Bist du sicher, dass du :name löschen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.', ['name' => $userToDelete?->name]) }}
                    </p>
                    <div class="flex justify-center gap-3">
                        <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 text-gray-600 transition border border-gray-300 rounded-lg hover:bg-gray-50">
                            {{ __('Abbrechen') }}
                        </button>
                        <button wire:click="confirmDelete" class="px-4 py-2 text-white transition bg-red-600 rounded-lg hover:bg-red-700">
                            {{ __('Löschen') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Note MVP -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">MVP Version</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Prochaines fonctionnalités : export des utilisateurs, actions groupées, filtres avancés et impersonnalisation.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
