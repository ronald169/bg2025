<?php
// database/seeders/SubjectsTableSeeder.php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectsTableSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            // ==================== GRAMMATIK ====================
            ['name' => 'Grammar A1', 'slug' => 'grammar-a1', 'icon' => 'book-open', 'color' => 'blue', 'description' => 'Learn German grammar at A1 level', 'is_active' => true],
            ['name' => 'Grammar A2', 'slug' => 'grammar-a2', 'icon' => 'book-open', 'color' => 'blue', 'description' => 'Learn German grammar at A2 level', 'is_active' => true],
            ['name' => 'Grammar B1', 'slug' => 'grammar-b1', 'icon' => 'book-open', 'color' => 'blue', 'description' => 'Learn German grammar at B1 level', 'is_active' => true],
            ['name' => 'Grammar B2', 'slug' => 'grammar-b2', 'icon' => 'book-open', 'color' => 'blue', 'description' => 'Learn German grammar at B2 level', 'is_active' => true],
            ['name' => 'Grammar C1', 'slug' => 'grammar-c1', 'icon' => 'book-open', 'color' => 'blue', 'description' => 'Learn German grammar at C1 level', 'is_active' => true],
            ['name' => 'Grammar C2', 'slug' => 'grammar-c2', 'icon' => 'book-open', 'color' => 'blue', 'description' => 'Learn German grammar at C2 level', 'is_active' => true],

            // ==================== WORTSCHATZ ====================
            ['name' => 'Vocabulary A1', 'slug' => 'vocabulary-a1', 'icon' => 'bookmark', 'color' => 'green', 'description' => 'Build your German vocabulary at A1 level', 'is_active' => true],
            ['name' => 'Vocabulary A2', 'slug' => 'vocabulary-a2', 'icon' => 'bookmark', 'color' => 'green', 'description' => 'Build your German vocabulary at A2 level', 'is_active' => true],
            ['name' => 'Vocabulary B1', 'slug' => 'vocabulary-b1', 'icon' => 'bookmark', 'color' => 'green', 'description' => 'Build your German vocabulary at B1 level', 'is_active' => true],
            ['name' => 'Vocabulary B2', 'slug' => 'vocabulary-b2', 'icon' => 'bookmark', 'color' => 'green', 'description' => 'Build your German vocabulary at B2 level', 'is_active' => true],
            ['name' => 'Vocabulary C1', 'slug' => 'vocabulary-c1', 'icon' => 'bookmark', 'color' => 'green', 'description' => 'Build your German vocabulary at C1 level', 'is_active' => true],
            ['name' => 'Vocabulary C2', 'slug' => 'vocabulary-c2', 'icon' => 'bookmark', 'color' => 'green', 'description' => 'Build your German vocabulary at C2 level', 'is_active' => true],

            // ==================== LESEN ====================
            ['name' => 'Reading A1', 'slug' => 'reading-a1', 'icon' => 'document-text', 'color' => 'purple', 'description' => 'Improve your German reading skills at A1 level', 'is_active' => true],
            ['name' => 'Reading A2', 'slug' => 'reading-a2', 'icon' => 'document-text', 'color' => 'purple', 'description' => 'Improve your German reading skills at A2 level', 'is_active' => true],
            ['name' => 'Reading B1', 'slug' => 'reading-b1', 'icon' => 'document-text', 'color' => 'purple', 'description' => 'Improve your German reading skills at B1 level', 'is_active' => true],
            ['name' => 'Reading B2', 'slug' => 'reading-b2', 'icon' => 'document-text', 'color' => 'purple', 'description' => 'Improve your German reading skills at B2 level', 'is_active' => true],
            ['name' => 'Reading C1', 'slug' => 'reading-c1', 'icon' => 'document-text', 'color' => 'purple', 'description' => 'Improve your German reading skills at C1 level', 'is_active' => true],
            ['name' => 'Reading C2', 'slug' => 'reading-c2', 'icon' => 'document-text', 'color' => 'purple', 'description' => 'Improve your German reading skills at C2 level', 'is_active' => true],

            // ==================== SCHREIBEN ====================
            ['name' => 'Writing A1', 'slug' => 'writing-a1', 'icon' => 'pencil', 'color' => 'orange', 'description' => 'Improve your German writing skills at A1 level', 'is_active' => true],
            ['name' => 'Writing A2', 'slug' => 'writing-a2', 'icon' => 'pencil', 'color' => 'orange', 'description' => 'Improve your German writing skills at A2 level', 'is_active' => true],
            ['name' => 'Writing B1', 'slug' => 'writing-b1', 'icon' => 'pencil', 'color' => 'orange', 'description' => 'Improve your German writing skills at B1 level', 'is_active' => true],
            ['name' => 'Writing B2', 'slug' => 'writing-b2', 'icon' => 'pencil', 'color' => 'orange', 'description' => 'Improve your German writing skills at B2 level', 'is_active' => true],
            ['name' => 'Writing C1', 'slug' => 'writing-c1', 'icon' => 'pencil', 'color' => 'orange', 'description' => 'Improve your German writing skills at C1 level', 'is_active' => true],
            ['name' => 'Writing C2', 'slug' => 'writing-c2', 'icon' => 'pencil', 'color' => 'orange', 'description' => 'Improve your German writing skills at C2 level', 'is_active' => true],

            // ==================== SPRECHEN ====================
            ['name' => 'Speaking A1', 'slug' => 'speaking-a1', 'icon' => 'chat-bubble-left-right', 'color' => 'red', 'description' => 'Improve your German speaking skills at A1 level', 'is_active' => true],
            ['name' => 'Speaking A2', 'slug' => 'speaking-a2', 'icon' => 'chat-bubble-left-right', 'color' => 'red', 'description' => 'Improve your German speaking skills at A2 level', 'is_active' => true],
            ['name' => 'Speaking B1', 'slug' => 'speaking-b1', 'icon' => 'chat-bubble-left-right', 'color' => 'red', 'description' => 'Improve your German speaking skills at B1 level', 'is_active' => true],
            ['name' => 'Speaking B2', 'slug' => 'speaking-b2', 'icon' => 'chat-bubble-left-right', 'color' => 'red', 'description' => 'Improve your German speaking skills at B2 level', 'is_active' => true],
            ['name' => 'Speaking C1', 'slug' => 'speaking-c1', 'icon' => 'chat-bubble-left-right', 'color' => 'red', 'description' => 'Improve your German speaking skills at C1 level', 'is_active' => true],
            ['name' => 'Speaking C2', 'slug' => 'speaking-c2', 'icon' => 'chat-bubble-left-right', 'color' => 'red', 'description' => 'Improve your German speaking skills at C2 level', 'is_active' => true],

            // ==================== HÖREN ====================
            ['name' => 'Listening A1', 'slug' => 'listening-a1', 'icon' => 'ear', 'color' => 'teal', 'description' => 'Improve your German listening skills at A1 level', 'is_active' => true],
            ['name' => 'Listening A2', 'slug' => 'listening-a2', 'icon' => 'ear', 'color' => 'teal', 'description' => 'Improve your German listening skills at A2 level', 'is_active' => true],
            ['name' => 'Listening B1', 'slug' => 'listening-b1', 'icon' => 'ear', 'color' => 'teal', 'description' => 'Improve your German listening skills at B1 level', 'is_active' => true],
            ['name' => 'Listening B2', 'slug' => 'listening-b2', 'icon' => 'ear', 'color' => 'teal', 'description' => 'Improve your German listening skills at B2 level', 'is_active' => true],
            ['name' => 'Listening C1', 'slug' => 'listening-c1', 'icon' => 'ear', 'color' => 'teal', 'description' => 'Improve your German listening skills at C1 level', 'is_active' => true],
            ['name' => 'Listening C2', 'slug' => 'listening-c2', 'icon' => 'ear', 'color' => 'teal', 'description' => 'Improve your German listening skills at C2 level', 'is_active' => true],

            // ==================== PRÜFUNGSVORBEREITUNG ====================
            ['name' => 'Goethe Exam Prep', 'slug' => 'goethe-exam-prep', 'icon' => 'academic-cap', 'color' => 'yellow', 'description' => 'Prepare for the Goethe certificate exams', 'is_active' => true],
            ['name' => 'ÖSD Exam Prep', 'slug' => 'osd-exam-prep', 'icon' => 'academic-cap', 'color' => 'yellow', 'description' => 'Prepare for the ÖSD certificate exams', 'is_active' => true],
            ['name' => 'TELC Exam Prep', 'slug' => 'telc-exam-prep', 'icon' => 'academic-cap', 'color' => 'yellow', 'description' => 'Prepare for the TELC certificate exams', 'is_active' => true],
            ['name' => 'TestDaF Prep', 'slug' => 'testdaf-prep', 'icon' => 'academic-cap', 'color' => 'yellow', 'description' => 'Prepare for the TestDaF exam for university admission', 'is_active' => true],
            ['name' => 'DSH Prep', 'slug' => 'dsh-prep', 'icon' => 'academic-cap', 'color' => 'yellow', 'description' => 'Prepare for the DSH exam for university admission', 'is_active' => true],

            // ==================== KULTUR & LANDESKUNDE ====================
            ['name' => 'German Culture', 'slug' => 'german-culture', 'icon' => 'globe-alt', 'color' => 'indigo', 'description' => 'Discover German culture and traditions', 'is_active' => true],
            ['name' => 'German History', 'slug' => 'german-history', 'icon' => 'clock', 'color' => 'indigo', 'description' => 'Learn about German history', 'is_active' => true],
            ['name' => 'German Geography', 'slug' => 'german-geography', 'icon' => 'map-pin', 'color' => 'indigo', 'description' => 'Explore German geography and regions', 'is_active' => true],
            ['name' => 'German Literature', 'slug' => 'german-literature', 'icon' => 'book-open', 'color' => 'indigo', 'description' => 'Discover German literature and famous authors', 'is_active' => true],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}
