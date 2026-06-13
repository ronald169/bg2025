<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Support\Str;
use Mary\Traits\Toast;
use Livewire\WithFileUploads;

new
#[Title('Create Course - Teacher')]
#[Layout('layouts.app')]
class extends Component {
    use Toast, WithFileUploads;

    // Basic Information
    public string $title = '';
    public string $slug = '';
    public string $description = '';
    public string $short_description = '';
    public $subject_id = '';
    public string $level = 'A1';
    public int $estimated_duration = 10;
    public float $price = 0;
    public ?float $sale_price = null;
    public bool $is_published = false;
    public bool $is_featured = false;

    // Dynamic lists
    public array $requirements = [];
    public array $what_you_will_learn = [];
    public string $newRequirement = '';
    public string $newLearning = '';
    public array $tags = [];
    public string $newTag = '';

    // SEO
    public string $meta_title = '';
    public string $meta_description = '';
    public string $meta_keywords = '';

    // Thumbnail (optional)
    public $thumbnail = null;

    // Levels
    public function getLevelsProperty()
    {
        return [
            ['id' => 'A1', 'name' => __('A1 - Beginner')],
            ['id' => 'A2', 'name' => __('A2 - Elementary')],
            ['id' => 'B1', 'name' => __('B1 - Intermediate')],
            ['id' => 'B2', 'name' => __('B2 - Upper Intermediate')],
            ['id' => 'C1', 'name' => __('C1 - Advanced')],
            ['id' => 'C2', 'name' => __('C2 - Mastery')],
        ];
    }

    public function getSubjectsProperty()
    {
        return Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function updatedTitle($value): void
    {
        $this->slug = Str::slug($value);
    }

    public function addRequirement(): void
    {
        if (trim($this->newRequirement) !== '') {
            $this->requirements[] = trim($this->newRequirement);
            $this->newRequirement = '';
        }
    }

    public function removeRequirement($index): void
    {
        unset($this->requirements[$index]);
        $this->requirements = array_values($this->requirements);
    }

    public function addLearning(): void
    {
        if (trim($this->newLearning) !== '') {
            $this->what_you_will_learn[] = trim($this->newLearning);
            $this->newLearning = '';
        }
    }

    public function removeLearning($index): void
    {
        unset($this->what_you_will_learn[$index]);
        $this->what_you_will_learn = array_values($this->what_you_will_learn);
    }

    public function addTag(): void
    {
        $tag = trim($this->newTag);
        if ($tag !== '' && !in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
            $this->newTag = '';
        }
    }

    public function removeTag($index): void
    {
        unset($this->tags[$index]);
        $this->tags = array_values($this->tags);
    }

    public function save(): void
    {
        $this->validate([
            'title'               => 'required|string|max:255',
            'slug'                => 'required|string|unique:courses,slug',
            'description'         => 'required|string|min:20',
            'short_description'   => 'nullable|string|max:200',
            'subject_id'          => 'required|exists:subjects,id',
            'level'               => 'required|in:A1,A2,B1,B2,C1,C2',
            'estimated_duration'  => 'required|integer|min:1',
            'price'               => 'nullable|numeric|min:0',
            'sale_price'          => 'nullable|numeric|min:0|lt:price',
            'meta_title'          => 'nullable|string|max:60',
            'meta_description'    => 'nullable|string|max:160',
            'thumbnail'           => 'nullable|image|max:1024',
        ], [
            'title.required'              => __('Please enter a course title.'),
            'slug.required'               => __('URL slug is required.'),
            'slug.unique'                 => __('This URL slug is already in use.'),
            'description.required'        => __('Please enter a course description.'),
            'description.min'             => __('Description must be at least 20 characters.'),
            'subject_id.required'         => __('Please select a subject.'),
            'level.required'              => __('Please select a level.'),
            'estimated_duration.required' => __('Please enter the estimated duration.'),
            'estimated_duration.min'      => __('Duration must be at least 1 minute.'),
            'sale_price.lt'               => __('Sale price must be lower than regular price.'),
        ]);

        $thumbnailPath = null;
        if ($this->thumbnail) {
            $thumbnailPath = $this->thumbnail->store('courses', 'public');
        }

        $course = Course::create([
            'title'               => $this->title,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'short_description'   => $this->short_description,
            'subject_id'          => $this->subject_id,
            'teacher_id'          => auth()->id(),
            'level'               => $this->level,
            'estimated_duration'  => $this->estimated_duration,
            'price'               => $this->price,
            'sale_price'          => $this->sale_price,
            'is_free'             => $this->price == 0,
            'is_published'        => $this->is_published,
            'is_featured'         => $this->is_featured,
            'requirements'        => $this->requirements,
            'what_you_will_learn' => $this->what_you_will_learn,
            'tags'                => $this->tags,
            'meta_title'          => $this->meta_title,
            'meta_description'    => $this->meta_description,
            'meta_keywords'       => $this->meta_keywords,
            'thumbnail'           => $thumbnailPath,
        ]);

        $this->success(__('Course created successfully! 🎉'));
        $this->redirectRoute('teacher.courses.edit', ['course' => $course]);
    }

    public function render()
    {
        return $this->view([
            'levels' => $this->levels,
            'subjects' => $this->subjects,
        ]);
    }
};

?>

<div class="py-4 md:py-6">
    <div class="max-w-5xl px-3 mx-auto md:px-4">

        {{-- Navigation --}}
        <div class="mb-5">
            <a href="{{ route('teacher.courses') }}" wire:navigate class="inline-flex items-center gap-1 text-sm transition text-primary hover:text-primary-focus">
                <x-icon name="o-arrow-left" class="w-4 h-4" />
                {{ __('Back to my courses') }}
            </a>
        </div>

        {{-- Header --}}
        <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold md:text-2xl">📚 {{ __('Create New Course') }}</h1>
                <p class="mt-1 text-sm text-base-content/70">{{ __('Create a new German course for your students') }}</p>
            </div>
        </div>

        <form wire:submit="save" class="space-y-5">
            {{-- Basic Information --}}
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-information-circle" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('Basic Information') }}</h2>
                </div>
                <div class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model.live="title" label="{{ __('Course Title') }}" placeholder="{{ __('e.g. German A1 for Beginners') }}" icon="o-pencil" required />
                        <x-input wire:model="slug" label="{{ __('URL Slug') }}" placeholder="{{ __('german-a1-beginners') }}" icon="o-link" hint="{{ __('Auto-generated from title') }}" required />
                    </div>
                    <x-textarea wire:model="short_description" label="{{ __('Short Description') }}" placeholder="{{ __('A short description of the course (max 200 characters)') }}" rows="2" icon="o-document-text" />
                    <x-textarea wire:model="description" label="{{ __('Full Description') }}" placeholder="{!! __('Detailed description of the course, learning objectives, methods, etc.') !!}" rows="5" icon="o-document" required />
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-select wire:model="subject_id" label="{{ __('Subject') }}" :options="$subjects->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->toArray()" option-value="id" option-label="name" placeholder="{{ __('Select subject') }}" icon="o-academic-cap" required />
                        <x-select wire:model="level" label="{!! __('German Level') !!}" :options="$levels" option-value="id" option-label="name" icon="o-chart-bar" required />
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model="estimated_duration" type="number" min="1" label="{{ __('Estimated Duration (minutes)') }}" placeholder="{{ __('e.g. 120') }}" icon="o-clock" hint="{{ __('Total duration of all lessons') }}" required />
                        <div>
                            <x-input wire:model="price" type="number" step="0.01" min="0" label="{{ __('Price (€)') }}" placeholder="{{ __('0 for free course') }}" icon="o-currency-euro" />
                            <p class="mt-1 text-xs text-base-content/50">{{ __('Free courses are accessible to all students') }}</p>
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <x-input wire:model="sale_price" type="number" step="0.01" min="0" label="{{ __('Sale Price (€)') }}" placeholder="{{ __('Optional') }}" icon="o-tag" />
                        <x-file wire:model="thumbnail" label="{{ __('Course Thumbnail') }}" accept="image/*" hint="{{ __('Recommended size: 800x400px') }}" />
                    </div>
                    <div class="flex flex-col gap-4 md:flex-row">
                        <label class="flex items-center justify-between w-full p-3 rounded-lg bg-base-200">
                            <div><span class="font-medium">{{ __('Publish course') }}</span><p class="text-xs text-base-content/60">{{ __('Published courses are visible to students') }}</p></div>
                            <input type="checkbox" wire:model="is_published" class="toggle toggle-primary" />
                        </label>
                        <label class="flex items-center justify-between w-full p-3 rounded-lg bg-base-200">
                            <div><span class="font-medium">{{ __('Featured course') }}</span><p class="text-xs text-base-content/60">{{ __('Featured courses appear on the homepage') }}</p></div>
                            <input type="checkbox" wire:model="is_featured" class="toggle toggle-primary" />
                        </label>
                    </div>
                </div>
            </x-card>

            {{-- Requirements --}}
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-clipboard-document-list" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('Requirements') }}</h2>
                </div>
                <div class="space-y-4">
                    <div class="flex gap-2">
                        <x-input wire:model="newRequirement" placeholder="{{ __('Add a requirement...') }}" class="flex-1" icon="o-plus-circle" />
                        <x-button wire:click="addRequirement" label="{{ __('Add') }}" icon="o-plus" class="btn-primary" />
                    </div>
                    @if(count($requirements) > 0)
                        <div class="mt-3 space-y-2">
                            @foreach($requirements as $index => $req)
                                <div class="flex items-center justify-between p-3 rounded-lg bg-base-200">
                                    <div class="flex items-center gap-2"><x-icon name="o-check-circle" class="w-4 h-4 text-success" /><span class="text-sm">{{ $req }}</span></div>
                                    <button type="button" wire:click="removeRequirement({{ $index }})" class="text-error hover:text-error/80"><x-icon name="o-trash" class="w-4 h-4" /></button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm italic text-base-content/50">{{ __('No requirements added yet') }}</p>
                    @endif
                </div>
            </x-card>

            {{-- Learning Objectives --}}
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-sparkles" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('Learning Objectives') }}</h2>
                </div>
                <div class="space-y-4">
                    <div class="flex gap-2">
                        <x-input wire:model="newLearning" placeholder="{!! __('Add a learning objective...') !!}" class="flex-1" icon="o-plus-circle" />
                        <x-button wire:click="addLearning" label="{{ __('Add') }}" icon="o-plus" class="btn-primary" />
                    </div>
                    @if(count($what_you_will_learn) > 0)
                        <div class="mt-3 space-y-2">
                            @foreach($what_you_will_learn as $index => $item)
                                <div class="flex items-center justify-between p-3 rounded-lg bg-base-200">
                                    <div class="flex items-center gap-2"><x-icon name="o-star" class="w-4 h-4 text-warning" /><span class="text-sm">{{ $item }}</span></div>
                                    <button type="button" wire:click="removeLearning({{ $index }})" class="text-error hover:text-error/80"><x-icon name="o-trash" class="w-4 h-4" /></button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm italic text-base-content/50">{{ __('No learning objectives added yet') }}</p>
                    @endif
                </div>
            </x-card>

            {{-- Tags --}}
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-tag" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('Tags') }}</h2>
                </div>
                <div class="space-y-4">
                    <div class="flex gap-2">
                        <x-input wire:model="newTag" placeholder="{{ __('Add a tag...') }}" class="flex-1" icon="o-plus-circle" />
                        <x-button wire:click="addTag" label="{{ __('Add') }}" icon="o-plus" class="btn-primary" />
                    </div>
                    @if(count($tags) > 0)
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach($tags as $index => $tag)
                                <span class="inline-flex items-center gap-1 px-3 py-1 text-sm rounded-full bg-base-200">
                                    {{ $tag }}
                                    <button type="button" wire:click="removeTag({{ $index }})" class="text-error hover:text-error/80"><x-icon name="o-x-mark" class="w-3 h-3" /></button>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm italic text-base-content/50">{{ __('No tags added yet') }}</p>
                    @endif
                </div>
            </x-card>

            {{-- SEO --}}
            <x-card class="shadow-sm">
                <div class="flex items-center gap-2 pb-2 mb-4 border-b">
                    <x-icon name="o-chart-bar" class="w-5 h-5 text-primary" />
                    <h2 class="font-semibold">{{ __('SEO Optimization') }}</h2>
                </div>
                <div class="space-y-4">
                    <x-input wire:model="meta_title" label="{{ __('Meta Title') }}" placeholder="{{ __('Title for search engines') }}" icon="o-document-text" hint="{{ __('Recommended: 50-60 characters') }}" />
                    <x-textarea wire:model="meta_description" label="{{ __('Meta Description') }}" placeholder="{{ __('Short description for search engines') }}" rows="2" icon="o-document" hint="{{ __('Recommended: 150-160 characters') }}" />
                    <x-input wire:model="meta_keywords" label="{{ __('Meta Keywords') }}" placeholder="{{ __('Keywords, separated by commas') }}" icon="o-tag" hint="{{ __('e.g. German learn, A1 course, grammar') }}" />
                </div>
            </x-card>

            {{-- Actions --}}
            <div class="flex flex-col justify-end gap-3 pt-4 sm:flex-row">
                <x-button label="{{ __('Cancel') }}" link="{{ route('teacher.courses') }}" class="btn-ghost" />
                <x-button label="{{ __('Create Course') }}" class="btn-primary" type="submit" spinner="save" />
            </div>
        </form>
    </div>
</div>
