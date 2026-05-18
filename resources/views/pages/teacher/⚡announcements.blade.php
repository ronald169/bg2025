<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\Course;
use App\Models\Announcement;
use Mary\Traits\Toast;

new
#[Title('Announcements - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use WithPagination, Toast;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'course', history: true)]
    public $selectedCourse = null;

    public bool $showModal = false;
    public $editingId = null;
    public string $title = '';
    public string $content = '';
    public ?int $course_id = null;
    public string $type = 'info';
    public string $color = 'blue';
    public bool $is_published = true;
    public bool $is_pinned = false;
    public bool $send_notification = true;

    // Getters
    public function getCoursesProperty()
    {
        return Course::where('teacher_id', auth()->id())
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function getAnnouncementsProperty()
    {
        return Announcement::where('teacher_id', auth()->id())
            ->when($this->search, function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('content', 'like', '%' . $this->search . '%');
            })
            ->when($this->selectedCourse, fn($q) => $q->where('course_id', $this->selectedCourse))
            ->with('course')
            ->latest()
            ->paginate(15);
    }

    public function openModal($id = null): void
    {
        if ($id) {
            $announcement = Announcement::findOrFail($id);
            $this->editingId = $id;
            $this->title = $announcement->title;
            $this->content = $announcement->content;
            $this->course_id = $announcement->course_id;
            $this->type = $announcement->type;
            $this->color = $announcement->color;
            $this->is_published = $announcement->is_published;
            $this->is_pinned = $announcement->is_pinned;
            $this->send_notification = $announcement->send_notification;
        } else {
            $this->resetForm();
        }
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'required|string|min:10',
            'course_id'  => 'required|exists:courses,id',
            'type'       => 'required|in:info,warning,success,danger',
            'color'      => 'nullable|string',
        ], [
            'title.required'    => __('Please enter a title.'),
            'content.required'  => __('Please enter the announcement content.'),
            'content.min'       => __('Content must be at least 10 characters.'),
            'course_id.required'=> __('Please select a course.'),
        ]);

        $data = [
            'teacher_id'        => auth()->id(),
            'course_id'         => $this->course_id,
            'title'             => $this->title,
            'content'           => $this->content,
            'type'              => $this->type,
            'color'             => $this->color,
            'is_published'      => $this->is_published,
            'is_pinned'         => $this->is_pinned,
            'send_notification' => $this->send_notification,
            'published_at'      => $this->is_published ? now() : null,
        ];

        if ($this->editingId) {
            $announcement = Announcement::findOrFail($this->editingId);
            $announcement->update($data);
            $this->success(__('Announcement updated successfully!'));
        } else {
            $announcement = Announcement::create($data);

            // Si send_notification est activé, créer les notifications pour les étudiants du cours
            if ($this->send_notification) {
                $this->createNotificationsForCourse($announcement);
            }

            $this->success(__('Announcement created successfully!'));
        }

        $this->showModal = false;
        $this->resetForm();
    }

    private function createNotificationsForCourse($announcement): void
    {
        $students = $announcement->course->students()->get();
        foreach ($students as $student) {
            \App\Models\AnnouncementNotification::create([
                'announcement_id' => $announcement->id,
                'user_id' => $student->id,
                'is_read' => false,
            ]);
        }
    }

    public function deleteAnnouncement($id): void
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->delete();
        $this->success(__('Announcement deleted.'));
    }

    public function togglePublish($id): void
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->update([
            'is_published' => !$announcement->is_published,
            'published_at' => !$announcement->is_published ? now() : null,
        ]);
        $this->success($announcement->is_published ? __('Announcement published.') : __('Announcement hidden.'));
    }

    public function togglePin($id): void
    {
        $announcement = Announcement::findOrFail($id);
        $announcement->update(['is_pinned' => !$announcement->is_pinned]);
        $this->success($announcement->is_pinned ? __('Announcement pinned.') : __('Announcement unpinned.'));
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'selectedCourse']);
        $this->resetPage();
        $this->success(__('Filters reset.'));
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->content = '';
        $this->course_id = null;
        $this->type = 'info';
        $this->color = 'blue';
        $this->is_published = true;
        $this->is_pinned = false;
        $this->send_notification = true;
    }

    public function getTypeOptionsProperty()
    {
        return [
            ['id' => 'info', 'name' => 'Info', 'color' => 'blue'],
            ['id' => 'warning', 'name' => 'Warning', 'color' => 'yellow'],
            ['id' => 'success', 'name' => 'Success', 'color' => 'green'],
            ['id' => 'danger', 'name' => 'Danger', 'color' => 'red'],
        ];
    }

    public function render()
    {
        return $this->view([
            'courses'      => $this->courses,
            'announcements'=> $this->announcements,
            'typeOptions'  => $this->typeOptions,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📢 {{ __('Announcements') }}</h1>
                <p class="mt-0.5 text-sm text-base-content/70">{{ __('Manage announcements for your courses') }}</p>
            </div>
            <x-button wire:click="openModal" label="{{ __('New announcement') }}" icon="o-plus" class="btn-primary" />
        </div>

        {{-- Filters --}}
        <div class="p-4 mb-5 shadow-sm bg-base-100 rounded-xl">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search announcements...') }}" icon="o-magnifying-glass" clearable />
                <x-select wire:model.live="selectedCourse" :options="collect($courses)->prepend(['id' => '', 'title' => __('All courses')])->toArray()" option-value="id" option-label="title" id="course_filter" name="course_filter" />
            </div>
            @if($search || $selectedCourse)
                <div class="flex justify-end mt-3">
                    <x-button wire:click="clearFilters" label="{{ __('Reset filters') }}" icon="o-x-mark" class="btn-ghost btn-sm" />
                </div>
            @endif
        </div>

        {{-- Announcements List --}}
        @if($announcements->count() > 0)
            <div class="hidden overflow-hidden shadow-sm md:block bg-base-100 rounded-xl">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-base-200">
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Title') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Course') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-left">{{ __('Created') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-center">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($announcements as $announcement)
                                <tr class="border-b hover:bg-base-200">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="font-medium">{{ Str::limit($announcement->title, 50) }}</p>
                                            <p class="mt-1 text-xs text-base-content/60">{{ Str::limit($announcement->content, 80) }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ $announcement->course->title }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @if($announcement->is_published)
                                                <x-badge value="{{ __('Published') }}" class="badge-success badge-soft" />
                                            @else
                                                <x-badge value="{{ __('Draft') }}" class="badge-warning badge-soft" />
                                            @endif
                                            @if($announcement->is_pinned)
                                                <x-badge value="{{ __('Pinned') }}" icon="o-map-pin" class="badge-info badge-soft" />
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-base-content/60">{{ $announcement->created_at->format('d.m.Y') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <x-button icon="o-pencil" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Edit') }}" wire:click="openModal({{ $announcement->id }})" />
                                            <x-button icon="o-map-pin" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ __('Pin/Unpin') }}" wire:click="togglePin({{ $announcement->id }})" />
                                            <x-button icon="{{ $announcement->is_published ? 'o-eye-slash' : 'o-eye' }}" class="btn-circle btn-ghost btn-sm" tooltip-left="{{ $announcement->is_published ? __('Hide') : __('Publish') }}" wire:click="togglePublish({{ $announcement->id }})" />
                                            <x-button icon="o-trash" class="btn-circle btn-ghost btn-sm text-error" tooltip-left="{{ __('Delete') }}" wire:click="deleteAnnouncement({{ $announcement->id }})" wire:confirm="{{ __('Delete this announcement?') }}" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t bg-base-200">{{ $announcements->links() }}</div>
            </div>

            {{-- Mobile cards --}}
            <div class="space-y-3 md:hidden">
                @foreach($announcements as $announcement)
                    <x-card class="shadow-sm">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold">{{ Str::limit($announcement->title, 40) }}</h3>
                                <p class="mt-1 text-xs text-base-content/60">{{ $announcement->created_at->format('d.m.Y') }}</p>
                            </div>
                            @if($announcement->is_published)
                                <x-badge value="{{ __('Published') }}" class="badge-success badge-soft" />
                            @else
                                <x-badge value="{{ __('Draft') }}" class="badge-warning badge-soft" />
                            @endif
                        </div>
                        <p class="mt-2 text-sm text-base-content/80">{{ Str::limit($announcement->content, 100) }}</p>
                        <p class="mt-1 text-xs text-base-content/60">{{ __('Course') }}: {{ $announcement->course->title }}</p>
                        <div class="flex justify-end gap-2 pt-2 mt-3 border-t">
                            <x-button icon="o-pencil" class="btn-ghost btn-sm" wire:click="openModal({{ $announcement->id }})" />
                            <x-button icon="o-map-pin" class="btn-ghost btn-sm" wire:click="togglePin({{ $announcement->id }})" />
                            <x-button icon="{{ $announcement->is_published ? 'o-eye-slash' : 'o-eye' }}" class="btn-ghost btn-sm" wire:click="togglePublish({{ $announcement->id }})" />
                            <x-button icon="o-trash" class="btn-ghost btn-sm text-error" wire:click="deleteAnnouncement({{ $announcement->id }})" />
                        </div>
                    </x-card>
                @endforeach
                <div class="mt-4">{{ $announcements->links() }}</div>
            </div>
        @else
            <x-card class="py-12 text-center">
                <x-icon name="o-megaphone" class="w-16 h-16 mx-auto mb-4 text-base-content/30" />
                <h3 class="mb-2 text-lg font-semibold">{{ __('No announcements yet') }}</h3>
                <p class="text-base-content/60">{{ __('Create your first announcement to inform your students.') }}</p>
                <x-button wire:click="openModal" label="{{ __('Create announcement') }}" class="mt-4 btn-primary" />
            </x-card>
        @endif

        {{-- Modal (Create/Edit) --}}
        <x-modal wire:model="showModal" title="{{ $editingId ? __('Edit announcement') : __('New announcement') }}" size="2xl" separator>
            <x-form wire:submit="save" no-separator>
                <x-input wire:model="title" label="{{ __('Title') }}" placeholder="{{ __('Important announcement') }}" required />
                <x-textarea wire:model="content" label="{{ __('Content') }}" rows="6" placeholder="{{ __('Write your announcement here...') }}" required />

                <x-select wire:model="course_id" label="{{ __('Course') }}" :options="$courses->map(fn($c) => ['id' => $c->id, 'name' => $c->title])->toArray()" option-value="id" option-label="name" placeholder="{{ __('Select a course') }}" required />

                <div class="grid gap-4 md:grid-cols-2">
                    <x-select wire:model="type" label="{{ __('Type') }}" :options="$typeOptions" option-value="id" option-label="name" />
                    <x-input wire:model="color" label="{{ __('Color') }}" placeholder="blue, red, green, yellow" />
                </div>

                <div class="flex flex-wrap gap-4">
                    <x-toggle wire:model="is_published" label="{{ __('Publish immediately') }}" hint="{{ __('Published announcements are visible to students') }}" />
                    <x-toggle wire:model="is_pinned" label="{{ __('Pin announcement') }}" hint="{{ __('Pinned announcements appear at the top') }}" />
                    <x-toggle wire:model="send_notification" label="{{ __('Send notification') }}" hint="{{ __('Notify students by email and in-app') }}" />
                </div>

                <x-slot:actions>
                    <x-button label="{{ __('Cancel') }}" wire:click="$set('showModal', false)" class="btn-ghost" />
                    <x-button label="{{ __('Save') }}" class="btn-primary" type="submit" spinner="save" />
                </x-slot:actions>
            </x-form>
        </x-modal>
    </div>
</div>
