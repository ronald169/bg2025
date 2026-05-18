@props(['course', 'featured' => false])

<div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1 {{ $featured ? 'ring-2 ring-primary-500' : '' }}">
    <!-- Course Image -->
    <div class="relative h-48 bg-gradient-to-br from-primary-500 to-primary-600">
        @if($course->thumbnail)
            <img src="{{ Storage::url($course->thumbnail) }}"
                 alt="{{ $course->title }}"
                 class="object-cover w-full h-full">
        @else
            <div class="flex items-center justify-center w-full h-full">
                <x-icon name="o-academic-cap" class="w-16 h-16 text-white opacity-50" />
            </div>
        @endif

        @if($featured)
            <div class="absolute top-2 left-2">
                <span class="px-2 py-1 text-xs font-bold text-yellow-900 bg-yellow-400 rounded-full">
                    {{ __('Featured') }}
                </span>
            </div>
        @endif

        @if($course->price == 0)
            <div class="absolute top-2 right-2">
                <span class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full">
                    {{ __('Free') }}
                </span>
            </div>
        @endif
    </div>

    <!-- Course Content -->
    <div class="p-5">
        <!-- Subject & Level -->
        <div class="flex items-center justify-between mb-3">
            <span class="px-2 py-1 text-xs font-medium rounded-full text-primary-600 bg-primary-50">
                {{ $course->subject->name ?? 'General' }}
            </span>
            <span class="text-xs text-gray-500">
                {{ ucfirst($course->level) }}
            </span>
        </div>

        <!-- Title -->
        <h3 class="mb-2 text-lg font-semibold text-gray-900 line-clamp-2">
            {{ $course->title }}
        </h3>

        <!-- Description -->
        <p class="mb-4 text-sm text-gray-600 line-clamp-2">
            {{ $course->short_description ?? Str::limit($course->description, 100) }}
        </p>

        <!-- Meta Info -->
        <div class="flex items-center justify-between mb-4 text-sm text-gray-500">
            <div class="flex items-center space-x-3">
                <span class="flex items-center">
                    <x-icon name="o-book-open" class="w-4 h-4 mr-1" />
                    {{ $course->lessons_count }} {{ __('lessons') }}
                </span>

                <!-- Badge Quiz -->
                @if($course->quizzes_count > 0)
                    <span class="flex items-center text-purple-600">
                        <x-icon name="o-document-text" class="w-4 h-4 mr-1" />
                        {{ $course->quizzes_count }} {{ __('quizzes') }}
                    </span>
                @endif
            </div>
        </div>

        <!-- Rating -->
        <div class="flex items-center mb-4">
            <div class="flex text-yellow-400">
                @for($i = 1; $i <= 5; $i++)
                    <x-icon name="o-star" class="w-4 h-4"
                            :class="$i <= round($course->reviews_avg_rating) ? 'text-yellow-400' : 'text-gray-300'" />
                @endfor
            </div>
            <span class="ml-2 text-sm text-gray-600">
                ({{ $course->reviews_count ?? 0 }})
            </span>
        </div>

        <!-- Price & Action -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <div>
                @if($course->sale_price && $course->sale_price < $course->price)
                    <span class="text-lg font-bold text-primary-600">${{ $course->sale_price }}</span>
                    <span class="ml-2 text-sm text-gray-400 line-through">${{ $course->price }}</span>
                @elseif($course->price > 0)
                    <span class="text-lg font-bold text-gray-900">${{ $course->price }}</span>
                @else
                    <span class="text-lg font-bold text-green-600">{{ __('Free') }}</span>
                @endif
            </div>

            <a href="{{ route('student.course.show', $course) }}"
               wire:navigate
               class="inline-flex items-center font-medium text-primary-600 hover:text-primary-700">
                {{ __('View Course') }}
                <x-icon name="o-arrow-right" class="w-4 h-4 ml-1" />
            </a>
        </div>
    </div>
</div>
