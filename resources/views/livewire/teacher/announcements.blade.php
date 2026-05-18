<?php
// app/Livewire/Teacher/Announcements.php

namespace App\Livewire\Teacher;

use App\Models\Announcement;
use App\Models\Course;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Announcements - Teacher')]
#[Layout('components.layouts.dashboard-teacher')]
class extends Component {
    use WithPagination, Toast;

    // Filtres
    public $selectedCourse = null;
    
    // Formulaire
    public $title = '';
    public $content = '';
    public $course_id = null;
    public $is_important = false;
    public $send_email = true;
    
    // État du modal
    public $showModal = false;
    public $editingId = null;

    // Propriétés calculées
    public function getCoursesProperty()
    {
        return Course::where('teacher_id', auth()->id())->get();
    }

    public function getAnnouncementsProperty()
    {
        return Announcement::where('teacher_id', auth()->id())
            ->when($this->selectedCourse, fn($q) => $q->where('course_id', $this->selectedCourse))
            ->with('course')
            ->latest()
            ->paginate(10);
    }

    public function openModal($courseId = null, $id = null): void
    {
        $this->resetForm();
        
        if ($id) {
            $announcement = Announcement::where('teacher_id', auth()->id())->find($id);
            if ($announcement) {
                $this->editingId = $announcement->id;
                $this->title = $announcement->title;
                $this->content = $announcement->content;
                $this->course_id = $announcement->course_id;
                $this->is_important = $announcement->is_important;
                $this->send_email = $announcement->send_email;
            }
        } elseif ($courseId) {
            $this->course_id = $courseId;
        }
        
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'course_id' => 'required|exists:courses,id',
        ], [
            'title.required' => 'Please enter a title.',
            'content.required' => 'Please enter the announcement content.',
            'content.min' => 'The content must be at least 10 characters.',
            'course_id.required' => 'Please select a course.',
        ]);

        $data = [
            'teacher_id' => auth()->id(),
            'course_id' => $this->course_id,
            'title' => $this->title,
            'content' => $this->content,
            'is_important' => $this->is_important,
            'send_email' => $this->send_email,
            'is_published' => true,
        ];

        if ($this->editingId) {
            $announcement = Announcement::where('teacher_id', auth()->id())->find($this->editingId);
            if ($announcement) {
                $announcement->update($data);
                $this->success(__('Announcement updated successfully!'));
            }
        } else {
            $announcement = Announcement::create($data);
            
            // Envoyer les notifications par email si demandé
            if ($this->send_email) {
                $this->sendEmailNotifications($announcement);
            }
            
            $this->success(__('Announcement created and sent to students!'));
        }

        $this->closeModal();
    }

    private function sendEmailNotifications($announcement): void
    {
        $course = Course::find($this->course_id);
        $students = $course->students()->get();
        
        foreach ($students as $student) {
            // Ici vous pouvez envoyer un email réel
            // Mail::to($student->email)->send(new AnnouncementMail($announcement, $course));
        }
    }

    public function delete($id): void
    {
        $announcement = Announcement::where('teacher_id', auth()->id())->find($id);
        
        if ($announcement) {
            $announcement->delete();
            $this->success(__('Announcement deleted successfully!'));
        }
    }

    public function toggleImportant($id): void
    {
        $announcement = Announcement::where('teacher_id', auth()->id())->find($id);
        
        if ($announcement) {
            $announcement->update(['is_important' => !$announcement->is_important]);
            $this->success($announcement->is_important ? __('Announcement marked as important!') : __('Important mark removed.'));
        }
    }

    public function togglePublish($id): void
    {
        $announcement = Announcement::where('teacher_id', auth()->id())->find($id);
        
        if ($announcement) {
            $announcement->update(['is_published' => !$announcement->is_published]);
            $this->success($announcement->is_published ? __('Announcement published!') : __('Announcement hidden.'));
        }
    }

    public function clearFilter(): void
    {
        $this->selectedCourse = null;
    }

    private function resetForm(): void
    {
        $this->title = '';
        $this->content = '';
        $this->course_id = null;
        $this->is_important = false;
        $this->send_email = true;
        $this->editingId = null;
    }
}
?>


<div class="py-4 md:py-6">
    <div class="max-w-6xl px-3 mx-auto md:px-4">
        
        <!-- Header -->
        <div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 md:text-2xl">📢 {{ __('Announcements') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ __('Communicate with your students') }}</p>
            </div>
            <div>
                <x-button wire:click="openModal" icon="o-plus" class="btn-primary">
                    {{ __('New Announcement') }}
                </x-button>
            </div>
        </div>

        <!-- Filter by Course -->
        <div class="p-4 mb-5 bg-white shadow-sm rounded-xl">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-gray-700">{{ __('Filter by course') }}:</span>
                <div class="flex flex-wrap gap-2">
                    <button wire:click="clearFilter" 
                            class="px-3 py-1.5 text-sm rounded-lg transition-all
                                   {{ $selectedCourse === null ? 'bg-[#FF6B35] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ __('All Courses') }}
                    </button>
                    @foreach($this->courses as $course)
                    <button wire:click="$set('selectedCourse', {{ $course->id }})"
                            class="px-3 py-1.5 text-sm rounded-lg transition-all
                                   {{ $selectedCourse == $course->id ? 'bg-[#FF6B35] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $course->title }}
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Announcements List -->
        @if($this->announcements->count() > 0)
            <div class="space-y-4">
                @foreach($this->announcements as $announcement)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden border-l-4 {{ $announcement->is_important ? 'border-l-red-500' : 'border-l-[#FF6B35]' }}">
                    <div class="p-5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="flex-1">
                                <!-- Badges -->
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    @if($announcement->is_important)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-700">
                                            <x-icon name="o-exclamation-triangle" class="w-3 h-3" />
                                            {{ __('Important') }}
                                        </span>
                                    @endif
                                    @if(!$announcement->is_published)
                                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600">
                                            {{ __('Hidden') }}
                                        </span>
                                    @endif
                                    <span class="text-xs text-gray-400">{{ $announcement->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                                
                                <!-- Course info -->
                                <div class="flex items-center gap-2 mb-2">
                                    <x-icon name="o-academic-cap" class="w-4 h-4 text-gray-400" />
                                    <span class="text-sm text-gray-500">{{ $announcement->course->title }}</span>
                                </div>
                                
                                <!-- Title & Content -->
                                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ $announcement->title }}</h3>
                                <p class="text-gray-600">{{ $announcement->content }}</p>
                                
                                <!-- Email notification indicator -->
                                @if($announcement->send_email)
                                    <div class="flex items-center gap-1 mt-3 text-xs text-gray-400">
                                        <x-icon name="o-envelope" class="w-3 h-3" />
                                        {{ __('Email notification sent to students') }}
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex gap-1">
                                <button wire:click="toggleImportant({{ $announcement->id }})" 
                                        class="p-2 rounded-lg {{ $announcement->is_important ? 'text-red-500 hover:text-red-700' : 'text-gray-400 hover:text-red-500' }} transition"
                                        title="{{ $announcement->is_important ? __('Remove important mark') : __('Mark as important') }}">
                                    <x-icon name="o-exclamation-triangle" class="w-4 h-4" />
                                </button>
                                
                                <button wire:click="togglePublish({{ $announcement->id }})" 
                                        class="p-2 text-gray-400 transition rounded-lg hover:text-green-600"
                                        title="{{ $announcement->is_published ? __('Hide') : __('Publish') }}">
                                    <x-icon :name="$announcement->is_published ? 'o-eye-slash' : 'o-eye'" class="w-4 h-4" />
                                </button>
                                
                                <button wire:click="openModal(null, {{ $announcement->id }})" 
                                        class="p-2 text-gray-400 transition rounded-lg hover:text-orange-600"
                                        title="{{ __('Edit') }}">
                                    <x-icon name="o-pencil" class="w-4 h-4" />
                                </button>
                                
                                <button wire:click="delete({{ $announcement->id }})" 
                                        wire:confirm="{{ __('Delete this announcement?') }}"
                                        class="p-2 text-gray-400 transition rounded-lg hover:text-red-600"
                                        title="{{ __('Delete') }}">
                                    <x-icon name="o-trash" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $this->announcements->links() }}
            </div>
        @else
            <div class="p-12 text-center bg-white shadow-sm rounded-xl">
                <x-icon name="o-megaphone" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                <h3 class="mb-2 text-lg font-semibold text-gray-900">{{ __('No announcements yet') }}</h3>
                <p class="mb-4 text-gray-500">{{ __('Create your first announcement to communicate with your students') }}</p>
                <x-button wire:click="openModal" class="btn-primary">
                    {{ __('Create Announcement') }}
                </x-button>
            </div>
        @endif

        <!-- Modal Create/Edit -->
        @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" wire:click.self="closeModal">
            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="sticky top-0 p-5 bg-white border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ $editingId ? __('Edit Announcement') : __('Create Announcement') }}
                        </h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <x-icon name="o-x-mark" class="w-6 h-6" />
                        </button>
                    </div>
                </div>
                
                <div class="p-5 space-y-4">
                    <!-- Course selection -->
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">{{ __('Course') }} *</label>
                        <select wire:model="course_id" class="w-full px-3 py-2 border rounded-lg focus:ring-[#FF6B35] focus:border-[#FF6B35]">
                            <option value="">{{ __('Select a course') }}</option>
                            @foreach($this->courses as $course)
                                <option value="{{ $course->id }}">{{ $course->title }}</option>
                            @endforeach
                        </select>
                        @error('course_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Title -->
                    <x-input
                        wire:model="title"
                        label="{{ __('Title') }} *"
                        placeholder="{{ __('Announcement title...') }}"
                        icon="o-megaphone"
                        required />

                    <!-- Content -->
                    <x-textarea
                        wire:model="content"
                        label="{{ __('Content') }} *"
                        placeholder="{{ __('Write your announcement content here...') }}"
                        rows="5"
                        icon="o-document-text"
                        required />

                    <!-- Options -->
                    <div class="pt-2 space-y-3">
                        <x-toggle
                            wire:model="is_important"
                            label="{{ __('Mark as important') }}"
                            hint="{{ __('Important announcements are highlighted in red') }}" />

                        <x-toggle
                            wire:model="send_email"
                            label="{{ __('Send email notification') }}"
                            hint="{{ __('Students will receive an email notification') }}" />
                    </div>
                </div>

                <div class="flex justify-end gap-3 p-5 border-t bg-gray-50">
                    <x-button wire:click="closeModal" class="btn-ghost">
                        {{ __('Cancel') }}
                    </x-button>
                    <x-button wire:click="save" class="btn-primary" spinner="save">
                        {{ $editingId ? __('Save Changes') : __('Create Announcement') }}
                    </x-button>
                </div>
            </div>
        </div>
        @endif

        <!-- MVP Note -->
        <div class="p-4 mt-6 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-start gap-3">
                <x-icon name="o-information-circle" class="w-5 h-5 text-blue-600 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-blue-800">{{ __('MVP Version') }}</p>
                    <p class="text-xs text-blue-700 md:text-sm">{{ __('Email notifications will be fully implemented in the next version.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>