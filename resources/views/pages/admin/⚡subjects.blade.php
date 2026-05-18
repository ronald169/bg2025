<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Subject;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
#[Title('Manage Subjects - Admin')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public bool $showModal = false;
    public $editingId = null;
    public string $name = '';
    public string $slug = '';
    public string $icon = '';
    public string $color = 'orange';
    public string $description = '';
    public bool $is_active = true;

    public array $colors = [
        ['id' => 'orange', 'name' => 'Orange'],
        ['id' => 'blue', 'name' => 'Blue'],
        ['id' => 'green', 'name' => 'Green'],
        ['id' => 'red', 'name' => 'Red'],
        ['id' => 'yellow', 'name' => 'Yellow'],
        ['id' => 'purple', 'name' => 'Purple'],
        ['id' => 'pink', 'name' => 'Pink'],
        ['id' => 'indigo', 'name' => 'Indigo'],
        ['id' => 'teal', 'name' => 'Teal'],
    ];

    public array $icons = [
        ['id' => 'o-academic-cap', 'name' => '🎓 Academic'],
        ['id' => 'o-book-open', 'name' => '📖 Book'],
        ['id' => 'o-calculator', 'name' => '🧮 Calculator'],
        ['id' => 'o-beaker', 'name' => '🧪 Science'],
        ['id' => 'o-globe-alt', 'name' => '🌍 Geography'],
        ['id' => 'o-language', 'name' => '🗣️ Languages'],
        ['id' => 'o-musical-note', 'name' => '🎵 Music'],
        ['id' => 'o-chart-bar', 'name' => '📊 Statistics'],
        ['id' => 'o-user-group', 'name' => '👥 Society'],
        ['id' => 'o-heart', 'name' => '❤️ Health'],
        ['id' => 'o-computer-desktop', 'name' => '💻 Computer'],
        ['id' => 'o-camera', 'name' => '📸 Art'],
    ];

    // Getters
    public function getSubjectsProperty()
    {
        return Subject::withCount('courses')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->paginate(15);
    }

    public function getTotalSubjectsProperty()
    {
        return Subject::count();
    }

    public function getActiveCountProperty()
    {
        return Subject::where('is_active', true)->count();
    }

    public function getInactiveCountProperty()
    {
        return Subject::where('is_active', false)->count();
    }

    public function getTotalCoursesProperty()
    {
        return Subject::withCount('courses')->get()->sum('courses_count');
    }

    public function openModal($id = null): void
    {
        if ($id) {
            $subject = Subject::findOrFail($id);
            $this->editingId = $subject->id;
            $this->name = $subject->name;
            $this->slug = $subject->slug;
            $this->icon = $subject->icon ?? '';
            $this->color = $subject->color ?? 'orange';
            $this->description = $subject->description ?? '';
            $this->is_active = $subject->is_active;
        } else {
            $this->resetForm();
        }
        $this->showModal = true;
    }

    public function updatedName($value): void
    {
        $this->slug = Str::slug($value);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'string|unique:subjects,slug,' . $this->editingId,
            'icon' => 'nullable|string',
            'color' => 'required|string',
            'description' => 'nullable|string|max:500',
        ], [
            'name.required' => __('Please enter a name.'),
            'slug.required' => __('URL slug is required.'),
            'slug.unique' => __('This URL slug is already in use.'),
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'color' => $this->color,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Subject::find($this->editingId)->update($data);
            $this->success(__('Subject updated successfully! 🎉'));
        } else {
            Subject::create($data);
            $this->success(__('Subject created successfully! 🎉'));
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function deleteSubject($id): void
    {
        $subject = Subject::findOrFail($id);

        if ($subject->courses()->count() > 0) {
            $this->error(__('This subject cannot be deleted because it is linked to :count courses.', ['count' => $subject->courses()->count()]));
            return;
        }

        $subject->delete();
        $this->success(__('Subject deleted! 🗑️'));
    }

    public function toggleActive($id): void
    {
        $subject = Subject::findOrFail($id);
        $subject->update(['is_active' => !$subject->is_active]);
        $this->success($subject->is_active ? __('Subject activated') : __('Subject deactivated'));
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->slug = '';
        $this->icon = '';
        $this->color = 'orange';
        $this->description = '';
        $this->is_active = true;
    }

    public function getColorClass($color): string
    {
        return match($color) {
            'orange' => 'bg-orange-100 text-orange-700',
            'blue' => 'bg-blue-100 text-blue-700',
            'green' => 'bg-green-100 text-green-700',
            'red' => 'bg-red-100 text-red-700',
            'yellow' => 'bg-yellow-100 text-yellow-700',
            'purple' => 'bg-purple-100 text-purple-700',
            'pink' => 'bg-pink-100 text-pink-700',
            'indigo' => 'bg-indigo-100 text-indigo-700',
            'teal' => 'bg-teal-100 text-teal-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function getIconClass($color): string
    {
        return match($color) {
            'orange' => 'text-orange-600',
            'blue' => 'text-blue-600',
            'green' => 'text-green-600',
            'red' => 'text-red-600',
            'yellow' => 'text-yellow-600',
            'purple' => 'text-purple-600',
            'pink' => 'text-pink-600',
            'indigo' => 'text-indigo-600',
            'teal' => 'text-teal-600',
            default => 'text-gray-600',
        };
    }

    public function render()
    {
        return $this->view([
            'subjects' => $this->subjects,
            'totalSubjects' => $this->totalSubjects,
            'activeCount' => $this->activeCount,
            'inactiveCount' => $this->inactiveCount,
            'totalCourses' => $this->totalCourses,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="px-3 mx-auto max-w-7xl md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">🏷️ {{ __('Manage Subjects') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage course subjects and categories') }}</p>
            </div>
            <div>
                <x-button wire:click="openModal" label="{{ __('New Subject') }}" icon="o-plus" class="btn-primary" />
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 gap-3 mb-5 sm:grid-cols-4">
            <x-stat title="{{ __('Total') }}" :value="$totalSubjects" icon="o-tag" class="text-primary" />
            <x-stat title="{{ __('Active') }}" :value="$activeCount" icon="o-check-circle" class="text-success" />
            <x-stat title="{{ __('Inactive') }}" :value="$inactiveCount" icon="o-eye-slash" class="text-warning" />
            <x-stat title="{{ __('Courses') }}" :value="$totalCourses" icon="o-academic-cap" class="text-secondary" />
        </div>

        {{-- Search --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="relative">
                <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search subjects...') }}" icon="o-magnifying-glass" class="w-full" clearable />
            </div>
        </div>

        {{-- Subjects List --}}
        @if($subjects->count() > 0)
            {{-- Desktop table --}}
            <div class="hidden overflow-hidden shadow-sm md:block bg-base-100 rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Subject') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Slug') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Courses') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subjects as $subject)
                                <tr class="transition border-b hover:bg-base-200">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $this->getColorClass($subject->color) }}">
                                                @if($subject->icon)
                                                    <x-icon :name="$subject->icon" class="w-4 h-4 {{ $this->getIconClass($subject->color) }}" />
                                                @else
                                                    <x-icon name="o-tag" class="w-4 h-4 {{ $this->getIconClass($subject->color) }}" />
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">{{ $subject->name }}</p>
                                                @if($subject->description)
                                                    <p class="text-xs text-base-content/60">{{ Str::limit($subject->description, 40) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm">{{ $subject->slug }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <x-badge :value="$subject->courses_count" icon="o-academic-cap" class="badge-info badge-soft" />
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($subject->is_active)
                                            <x-badge value="{{ __('Active') }}" class="badge-success badge-soft" />
                                        @else
                                            <x-badge value="{{ __('Inactive') }}" class="badge-neutral badge-soft" />
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Edit') }}" wire:click="openModal({{ $subject->id }})" />
                                            <x-button icon="{{ $subject->is_active ? 'o-eye-slash' : 'o-eye' }}" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ $subject->is_active ? __('Deactivate') : __('Activate') }}" wire:click="toggleActive({{ $subject->id }})" />
                                            <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteSubject({{ $subject->id }})" wire:confirm="{{ __('Are you sure?') }}" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-base-200">
                    {{ $subjects->links() }}
                </div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($subjects as $subject)
                    <x-card class="shadow-sm">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center {{ $this->getColorClass($subject->color) }}">
                                    @if($subject->icon)
                                        <x-icon :name="$subject->icon" class="w-5 h-5 {{ $this->getIconClass($subject->color) }}" />
                                    @else
                                        <x-icon name="o-tag" class="w-5 h-5 {{ $this->getIconClass($subject->color) }}" />
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold">{{ $subject->name }}</p>
                                    <p class="text-xs text-base-content/60">{{ $subject->slug }}</p>
                                </div>
                            </div>
                            @if($subject->is_active)
                                <x-badge value="{{ __('Active') }}" class="badge-success badge-soft" />
                            @else
                                <x-badge value="{{ __('Inactive') }}" class="badge-neutral badge-soft" />
                            @endif
                        </div>
                        <div class="flex items-center justify-between pt-2 mt-2 border-t">
                            <div class="flex items-center gap-1">
                                <x-icon name="o-academic-cap" class="w-4 h-4 text-base-content/50" />
                                <span class="text-sm text-base-content/60">{{ $subject->courses_count }} {{ __('courses') }}</span>
                            </div>
                            <div class="flex gap-2">
                                <x-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="openModal({{ $subject->id }})" />
                                <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteSubject({{ $subject->id }})" wire:confirm="{{ __('Are you sure?') }}" />
                            </div>
                        </div>
                    </x-card>
                @endforeach
                <div class="mt-4">{{ $subjects->links() }}</div>
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-tag" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No subjects found') }}</h3>
                <p class="mb-4 text-base-content/60">{{ __('Create your first subject.') }}</p>
                <x-button wire:click="openModal" label="{{ __('Create first subject →') }}" class="btn-primary" />
            </x-card>
        @endif

        {{-- Subject Modal --}}
        <x-modal wire:model="showModal" title="{{ $editingId ? __('Edit Subject') : __('New Subject') }}" separator>
            <x-form wire:submit="save" no-separator>
                <x-input wire:model="name" label="{{ __('Subject Name') }}" placeholder="{{ __('e.g. German, Mathematics, ...') }}" icon="o-tag" required />
                <x-input wire:model="slug" label="{{ __('URL Slug') }}" placeholder="{{ __('german') }}" icon="o-link" hint="{{ __('Auto-generated from name') }}" />
                <x-select wire:model="icon" label="{{ __('Icon') }}" :options="$icons" placeholder="{{ __('No icon') }}" />
                <x-select wire:model="color" label="{{ __('Color') }}" :options="$colors" option-value="id" option-label="name" required />
                <x-textarea wire:model="description" label="{{ __('Description') }}" placeholder="{{ __('Short description of the subject') }}" rows="2" icon="o-document-text" />
                <x-toggle wire:model="is_active" label="{{ __('Active') }}" hint="{{ __('Inactive subjects will not be displayed') }}" />
                <x-slot:actions>
                    <x-button label="{{ __('Cancel') }}" wire:click="$set('showModal', false)" class="btn-ghost" />
                    <x-button label="{{ $editingId ? __('Save') : __('Create') }}" class="btn-primary" type="submit" spinner="save" />
                </x-slot:actions>
            </x-form>
        </x-modal>
    </div>
</div>
