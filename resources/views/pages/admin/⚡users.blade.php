<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\User;
use Mary\Traits\Toast;

new
#[Title('Manage Users - Admin')]
#[Layout('layouts.app')]
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

    // Getters (remplacent #[Computed])
    public function getUsersProperty()
    {
        return User::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%'))
            ->when($this->roleFilter, fn($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }

    public function getTotalUsersProperty()
    {
        return User::count();
    }

    public function getStudentsCountProperty()
    {
        return User::where('role', 'student')->count();
    }

    public function getTeachersCountProperty()
    {
        return User::where('role', 'teacher')->count();
    }

    public function getAdminsCountProperty()
    {
        return User::where('role', 'admin')->count();
    }

    public function getActiveUsersCountProperty()
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
            $this->success("User '{$userName}' has been deleted.");
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
        $this->success($newStatus === 'active' ? 'User activated' : 'User deactivated');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'statusFilter']);
        $this->resetPage();
        $this->success('Filters reset.');
    }

    public function getRoleBadgeClass($role): string
    {
        return match($role) {
            'admin' => 'badge-error',
            'teacher' => 'badge-info',
            'student' => 'badge-success',
            default => 'badge-ghost',
        };
    }

    public function getRoleIcon($role): string
    {
        return match($role) {
            'admin' => 'o-shield-check',
            'teacher' => 'o-user-group',
            'student' => 'o-academic-cap',
            default => 'o-user',
        };
    }

    public function render()
    {
        return $this->view([
            'users' => $this->users,
            'totalUsers' => $this->totalUsers,
            'studentsCount' => $this->studentsCount,
            'teachersCount' => $this->teachersCount,
            'adminsCount' => $this->adminsCount,
            'activeUsersCount' => $this->activeUsersCount,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">👥 {{ __('Manage Users') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage all platform users') }}</p>
            </div>
            <div>
                <x-button label="{{ __('New User') }}" icon="o-plus" link="{{ route('admin.users.create') }}" class="btn-primary" />
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-5">
            <x-stat title="{{ __('Total') }}" :value="$totalUsers" icon="o-users" class="text-primary" />
            <x-stat title="{{ __('Students') }}" :value="$studentsCount" icon="o-academic-cap" class="text-success" />
            <x-stat title="{{ __('Teachers') }}" :value="$teachersCount" icon="o-user-group" class="text-info" />
            <x-stat title="{{ __('Admins') }}" :value="$adminsCount" icon="o-shield-check" class="text-error" />
            <x-stat title="{{ __('Active') }}" :value="$activeUsersCount" icon="o-check-circle" class="text-warning" />
        </div>

        {{-- Filters --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="flex flex-col gap-3">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search...') }}" icon="o-magnifying-glass" class="w-full" clearable />
                    <x-select wire:model.live="roleFilter" :options="[
                        ['id' => '', 'name' => __('All roles')],
                        ['id' => 'student', 'name' => __('Student')],
                        ['id' => 'teacher', 'name' => __('Teacher')],
                        ['id' => 'admin', 'name' => __('Admin')],
                    ]" placeholder="{{ __('All roles') }}" id="role_filter" name="role_filter" />
                    <x-select wire:model.live="statusFilter" :options="[
                        ['id' => '', 'name' => __('All statuses')],
                        ['id' => 'active', 'name' => __('Active')],
                        ['id' => 'inactive', 'name' => __('Inactive')],
                    ]" placeholder="{{ __('All statuses') }}" id="status_filter" name="status_filter" />
                </div>
                @if($search || $roleFilter || $statusFilter)
                    <div class="flex justify-end">
                        <x-button wire:click="clearFilters" label="{{ __('Reset filters') }} →" icon="o-x-mark" class="btn-ghost btn-sm" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Users List --}}
        @if($users->count() > 0)
            {{-- Desktop table --}}
            <div class="hidden overflow-hidden shadow-sm md:block bg-base-100 rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('User') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Role') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Registered') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr class="transition border-b hover:bg-base-200">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <span class="text-sm font-medium">{{ Str::limit($user->name, 25) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        <x-badge :value="__(ucfirst($user->role))" :class="$this->getRoleBadgeClass($user->role) . ' badge-soft'" icon="{{ $this->getRoleIcon($user->role) }}" />
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($user->status === 'active')
                                            <x-badge value="{{ __('Active') }}" class="badge-success badge-soft" />
                                        @else
                                            <x-badge value="{{ __('Inactive') }}" class="badge-neutral badge-soft" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-base-content/60">{{ $user->created_at->format('d.m.Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <x-button icon="o-eye" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('View') }}" link="{{ route('admin.users.show', $user) }}" />
                                            <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Edit') }}" link="{{ route('admin.users.edit', $user) }}" />
                                            <x-button icon="{{ $user->status === 'active' ? 'o-eye-slash' : 'o-eye' }}" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ $user->status === 'active' ? __('Deactivate') : __('Activate') }}" wire:click="toggleStatus({{ $user->id }})" />
                                            <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteUser({{ $user->id }})" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-base-200">
                    {{ $users->links() }}
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($users as $user)
                    <x-card class="shadow-sm">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-10 text-sm font-bold text-white rounded-full bg-gradient-to-r from-primary to-secondary">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold">{{ Str::limit($user->name, 20) }}</p>
                                    <p class="text-xs text-base-content/60">{{ $user->email }}</p>
                                </div>
                            </div>
                            @if($user->status === 'active')
                                <x-badge value="{{ __('Active') }}" class="badge-success badge-soft" />
                            @else
                                <x-badge value="{{ __('Inactive') }}" class="badge-neutral badge-soft" />
                            @endif
                        </div>
                        <div class="flex items-center gap-2 mb-3">
                            <x-badge :value="ucfirst($user->role)" :class="$this->getRoleBadgeClass($user->role) . ' badge-soft'" />
                            <span class="text-xs text-base-content/50">{{ $user->created_at->format('d.m.Y') }}</span>
                        </div>
                        <div class="flex justify-end gap-2 pt-2 border-t">
                            <x-button icon="o-eye" class="btn-ghost btn-sm" link="{{ route('admin.users.show', $user) }}" />
                            <x-button icon="o-pencil" class="btn-ghost btn-sm" link="{{ route('admin.users.edit', $user) }}" />
                            <x-button icon="{{ $user->status === 'active' ? 'o-eye-slash' : 'o-eye' }}" class="btn-ghost btn-sm" wire:click="toggleStatus({{ $user->id }})" />
                            <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteUser({{ $user->id }})" />
                        </div>
                    </x-card>
                @endforeach
                <div class="mt-4">{{ $users->links() }}</div>
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-users" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No users found') }}</h3>
                <p class="mb-4 text-base-content/60">{{ __('Try different search criteria.') }}</p>
                <x-button wire:click="clearFilters" label="{{ __('Reset filters') }} →" class="btn-outline" />
            </x-card>
        @endif

        {{-- Delete Modal --}}
        <x-modal wire:model="showDeleteModal" title="{{ __('Delete user') }}" separator>
            <p>{{ __('Are you sure you want to delete :name? This action cannot be undone.', ['name' => $userToDelete?->name]) }}</p>
            <x-slot:actions>
                <x-button label="{{ __('Cancel') }}" wire:click="$set('showDeleteModal', false)" />
                <x-button label="{{ __('Delete') }}" class="btn-error" wire:click="confirmDelete" spinner />
            </x-slot:actions>
        </x-modal>
    </div>
</div>
