<?php

namespace App\Livewire\Student;

use App\Models\LearningPath;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
#[Title('Mein Lernpfad - Deutsch lernen')]
#[Layout('layouts.app')]
class  extends Component
{
    use Toast;
    
    public $learningPath;
    public $recommendations = [];
    public $activeTab = 'overview';
    
    public function mount()
    {
        $this->learningPath = auth()->user()->learningPath;
        
        if (!$this->learningPath) {
            // Créer un learning path par défaut
            $this->learningPath = LearningPath::create([
                'user_id' => auth()->id(),
                'current_level' => auth()->user()->german_level ?? 'A1',
                'target_level' => auth()->user()->german_level ?? 'B1',
                'learning_goal' => auth()->user()->learning_goal ?? 'certification',
                'started_at' => now(),
                'is_active' => true,
            ]);
        }
        
        $this->recommendations = $this->learningPath->getRecommendations();
    }
    
    public function updateTargetLevel($level)
    {
        $this->learningPath->update(['target_level' => $level]);
        $this->success('Dein Zielniveau wurde aktualisiert! 🎯');
    }
    
    public function updateExamDate($date)
    {
        $this->learningPath->update(['target_exam_date' => $date]);
        $this->success('Prüfungsdatum gespeichert! 📅');
    }
    
    public function registerForExam()
    {
        $this->learningPath->update(['exam_registered' => true]);
        $this->success('Viel Erfolg bei der Prüfung! 🍀');
    }
    
    public function getSkillLevelClass($skill)
    {
        if ($skill >= 80) return 'bg-green-500';
        if ($skill >= 60) return 'bg-blue-500';
        if ($skill >= 40) return 'bg-yellow-500';
        return 'bg-red-500';
    }
} ?>

<div class="py-8">
    <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">📚 Mein Lernpfad</h1>
            <p class="mt-2 text-gray-600">Von {{ $learningPath->current_level }} bis {{ $learningPath->target_level }} - Deine persönliche Reise zum Deutschlernen</p>
        </div>
        
        <!-- Progression principale -->
        <div class="bg-gradient-to-r from-[#FF6B35] to-[#1E6091] rounded-2xl p-6 text-white mb-8">
            <div class="flex flex-col items-start justify-between md:flex-row md:items-center">
                <div>
                    <h2 class="mb-2 text-2xl font-bold">Gesamtfortschritt</h2>
                    <p class="text-white/80">Du bist auf dem besten Weg, dein Ziel zu erreichen!</p>
                </div>
                <div class="mt-4 text-center md:mt-0">
                    <div class="text-4xl font-bold">{{ $learningPath->overall_progress }}%</div>
                    <div class="text-sm text-white/80">abgeschlossen</div>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="w-full h-3 rounded-full bg-white/20">
                    <div class="h-3 transition-all duration-500 bg-white rounded-full" 
                         style="width: {{ $learningPath->overall_progress }}%"></div>
                </div>
            </div>
            
            <div class="flex justify-between mt-4 text-sm">
                <span>🏁 {{ $learningPath->current_level }}</span>
                <span>🎯 {{ $learningPath->target_level }}</span>
            </div>
        </div>
        
        <!-- Statistiques clés -->
        <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
            <div class="p-4 text-center bg-white shadow-sm rounded-xl">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ number_format($learningPath->total_hours_studied, 1) }}</div>
                <div class="text-sm text-gray-500">Studienstunden</div>
            </div>
            <div class="p-4 text-center bg-white shadow-sm rounded-xl">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $learningPath->total_points }}</div>
                <div class="text-sm text-gray-500">Punkte</div>
            </div>
            <div class="p-4 text-center bg-white shadow-sm rounded-xl">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $learningPath->total_quizzes_taken }}</div>
                <div class="text-sm text-gray-500">Quiz absolviert</div>
            </div>
            <div class="p-4 text-center bg-white shadow-sm rounded-xl">
                <div class="text-2xl font-bold text-[#FF6B35]">{{ $learningPath->days_since_start }}</div>
                <div class="text-sm text-gray-500">Tage aktiv</div>
            </div>
        </div>
        
        <!-- Tabs de navigation -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-8">
                <button wire:click="$set('activeTab', 'overview')" 
                        class="pb-4 px-1 border-b-2 font-medium transition-colors
                               {{ $activeTab === 'overview' ? 'border-[#FF6B35] text-[#FF6B35]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    📊 Übersicht
                </button>
                <button wire:click="$set('activeTab', 'skills')" 
                        class="pb-4 px-1 border-b-2 font-medium transition-colors
                               {{ $activeTab === 'skills' ? 'border-[#FF6B35] text-[#FF6B35]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        ⭐ Kompetenzen
                </button>
                <button wire:click="$set('activeTab', 'milestones')" 
                        class="pb-4 px-1 border-b-2 font-medium transition-colors
                               {{ $activeTab === 'milestones' ? 'border-[#FF6B35] text-[#FF6B35]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    🏆 Meilensteine
                </button>
                <button wire:click="$set('activeTab', 'exam')" 
                        class="pb-4 px-1 border-b-2 font-medium transition-colors
                               {{ $activeTab === 'exam' ? 'border-[#FF6B35] text-[#FF6B35]' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    🎓 Prüfungsvorbereitung
                </button>
            </nav>
        </div>
        
        <!-- Contenu des tabs -->
        <div class="mt-6">
            @if($activeTab === 'overview')
                <!-- Vue d'ensemble -->
                <div class="grid gap-6 md:grid-cols-2">
                    <!-- Recommandations -->
                    @if(count($recommendations) > 0)
                    <div class="p-4 border border-yellow-200 bg-yellow-50 rounded-xl">
                        <h3 class="mb-3 font-bold text-yellow-800">💡 Empfehlungen für dich</h3>
                        @foreach($recommendations as $rec)
                            <div class="mb-2 text-sm text-yellow-700">
                                • {{ $rec['message'] }}
                            </div>
                        @endforeach
                    </div>
                    @endif
                    
                    <!-- Prochaines étapes -->
                    <div class="p-6 bg-white shadow-sm rounded-xl">
                        <h3 class="mb-4 font-bold">🎯 Nächste Schritte</h3>
                        <div class="space-y-3">
                            @if($learningPath->next_level)
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 text-green-600 bg-green-100 rounded-full">✓</div>
                                <div>
                                    <p class="font-medium">Nächstes Level: {{ $learningPath->next_level }}</p>
                                    <div class="w-32 h-1 mt-1 bg-gray-200 rounded-full">
                                        <div class="h-1 bg-green-500 rounded-full" style="width: {{ $learningPath->progress_to_next_level }}%"></div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-100 rounded-full">📚</div>
                                <div>
                                    <p class="font-medium">Nächste Lektion</p>
                                    <p class="text-sm text-gray-500">Fortsetzen mit deinem aktuellen Kurs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            @if($activeTab === 'skills')
                <!-- Compétences -->
                <div class="p-6 bg-white shadow-sm rounded-xl">
                    <h3 class="mb-6 font-bold">Deine Deutsch-Kompetenzen</h3>
                    
                    <div class="space-y-4">
                        @php
                            $skills = [
                                ['name' => 'Lesen', 'value' => $learningPath->reading_skill, 'icon' => '📖'],
                                ['name' => 'Schreiben', 'value' => $learningPath->writing_skill, 'icon' => '✍️'],
                                ['name' => 'Hören', 'value' => $learningPath->listening_skill, 'icon' => '🎧'],
                                ['name' => 'Sprechen', 'value' => $learningPath->speaking_skill, 'icon' => '🗣️'],
                                ['name' => 'Grammatik', 'value' => $learningPath->grammar_skill, 'icon' => '📝'],
                                ['name' => 'Wortschatz', 'value' => $learningPath->vocabulary_skill, 'icon' => '📚'],
                            ];
                        @endphp
                        
                        @foreach($skills as $skill)
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $skill['icon'] }} {{ $skill['name'] }}</span>
                                <span class="text-sm text-gray-500">{{ $skill['value'] }}%</span>
                            </div>
                            <div class="w-full h-2 bg-gray-200 rounded-full">
                                <div class="h-2 rounded-full transition-all duration-500 {{ $this->getSkillLevelClass($skill['value']) }}" 
                                     style="width: {{ $skill['value'] }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="p-4 mt-6 rounded-lg bg-gray-50">
                        <p class="text-sm text-gray-600">
                            <strong>Durchschnitt:</strong> {{ $learningPath->skills_average }}% • 
                            <strong>Nächster Meilenstein:</strong> Erreiche 50% in allen Kompetenzen
                        </p>
                    </div>
                </div>
            @endif
            
            @if($activeTab === 'milestones')
                <!-- Meilensteine -->
                <div class="p-6 bg-white shadow-sm rounded-xl">
                    <h3 class="mb-6 font-bold">🏆 Errungenschaften</h3>
                    
                    @php
                        $milestones = $learningPath->milestones ?? [];
                    @endphp
                    
                    @if(count($milestones) > 0)
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach($milestones as $milestone)
                            <div class="flex items-start gap-3 p-3 rounded-lg bg-gray-50">
                                <div class="flex items-center justify-center w-10 h-10 text-green-600 bg-green-100 rounded-full">
                                    🏅
                                </div>
                                <div>
                                    <p class="font-medium">{{ $milestone->title }}</p>
                                    <p class="text-xs text-gray-500">{{ $milestone->description }}</p>
                                    <p class="mt-1 text-xs text-green-600">Erreicht am {{ \Carbon\Carbon::parse($milestone->achieved_at)->format('d.m.Y') }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="py-8 text-center text-gray-500">Beginne mit dem Lernen, um deine ersten Meilensteine zu erreichen! 🚀</p>
                    @endif
                </div>
            @endif
            
            @if($activeTab === 'exam')
                <!-- Préparation examen -->
                <div class="p-6 bg-white shadow-sm rounded-xl">
                    <h3 class="mb-6 font-bold">🎓 Prüfungsvorbereitung</h3>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Angestrebtes Zertifikat</label>
                            <select wire:change="updateTargetLevel($event.target.value)" class="w-full border-gray-300 rounded-lg">
                                <option value="A1" {{ $learningPath->target_level == 'A1' ? 'selected' : '' }}>Goethe-Zertifikat A1</option>
                                <option value="A2" {{ $learningPath->target_level == 'A2' ? 'selected' : '' }}>Goethe-Zertifikat A2</option>
                                <option value="B1" {{ $learningPath->target_level == 'B1' ? 'selected' : '' }}>Goethe-Zertifikat B1</option>
                                <option value="B2" {{ $learningPath->target_level == 'B2' ? 'selected' : '' }}>Goethe-Zertifikat B2</option>
                                <option value="C1" {{ $learningPath->target_level == 'C1' ? 'selected' : '' }}>Goethe-Zertifikat C1</option>
                                <option value="C2" {{ $learningPath->target_level == 'C2' ? 'selected' : '' }}>Goethe-Zertifikat C2</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-700">Prüfungsdatum (optional)</label>
                            <input type="date" 
                                   wire:change="updateExamDate($event.target.value)"
                                   value="{{ $learningPath->target_exam_date ? $learningPath->target_exam_date->format('Y-m-d') : '' }}"
                                   class="w-full border-gray-300 rounded-lg">
                        </div>
                        
                        @if($learningPath->target_exam_date)
                            @php
                                $daysLeft = $learningPath->estimated_days_remaining;
                            @endphp
                            <div class="p-4 bg-{{ $daysLeft < 30 ? 'orange' : 'blue' }}-50 rounded-lg">
                                <p class="font-medium">Noch {{ $daysLeft }} Tage bis zur Prüfung</p>
                                @if($daysLeft < 30)
                                    <p class="mt-2 text-sm">Intensivierungsphase! Empfohlen: 1-2 Stunden pro Tag lernen.</p>
                                @endif
                            </div>
                        @endif
                        
                        @if(!$learningPath->exam_registered && $learningPath->target_level >= 'B1')
                            <button wire:click="registerForExam" class="bg-[#FF6B35] text-white px-6 py-2 rounded-lg hover:bg-[#E55A2A] transition">
                                Für Prüfung anmelden 📝
                            </button>
                        @endif
                        
                        @if($learningPath->exam_registered)
                            <div class="p-4 rounded-lg bg-green-50">
                                <p class="text-green-800">✅ Du bist für die Prüfung angemeldet! Viel Erfolg!</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>