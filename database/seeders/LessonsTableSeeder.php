<?php
// database/seeders/LessonsTableSeeder.php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonsTableSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::all();

        $lessonsByLevel = [
            'A1' => [
                ['title' => 'Greetings and Introductions', 'content' => '<h2>Willkommen!</h2><p>In this lesson, you will learn how to greet people and introduce yourself in German.</p><h3>Key phrases:</h3><ul><li>Hallo! - Hello!</li><li>Guten Morgen! - Good morning!</li><li>Wie geht es dir? - How are you?</li><li>Ich heiße... - My name is...</li><li>Woher kommst du? - Where are you from?</li></ul><h3>Practice:</h3><p>Try introducing yourself to a partner using these phrases.</p>'],
                ['title' => 'Numbers and Counting', 'content' => '<h2>Zahlen von 1 bis 100</h2><p>Learn to count from 1 to 100 and use numbers in everyday situations.</p><h3>Numbers 1-10:</h3><ul><li>1 - eins</li><li>2 - zwei</li><li>3 - drei</li><li>4 - vier</li><li>5 - fünf</li></ul><h3>Practice:</h3><p>Practice saying your phone number and age in German.</p>'],
                ['title' => 'Basic Verbs and Conjugation', 'content' => '<h2>Verb Conjugation in Present Tense</h2><p>Learn how to conjugate regular verbs in German.</p><h3>Example: wohnen (to live)</h3><ul><li>ich wohne</li><li>du wohnst</li><li>er/sie/es wohnt</li><li>wir wohnen</li><li>ihr wohnt</li><li>sie/Sie wohnen</li></ul>'],
            ],
            'A2' => [
                ['title' => 'Past Tense (Perfekt)', 'content' => '<h2>The Perfect Tense</h2><p>Learn to talk about past events using the Perfekt tense.</p><h3>Structure:</h3><p>haben/sein + past participle</p><h3>Examples:</h3><ul><li>Ich habe gegessen. - I ate.</li><li>Wir sind gefahren. - We drove.</li></ul>'],
                ['title' => 'Modal Verbs', 'content' => '<h2>können, müssen, wollen, dürfen, sollen, mögen</h2><p>Master the modal verbs to express ability, necessity, desire, and permission.</p><h3>Examples:</h3><ul><li>Ich kann Deutsch sprechen. - I can speak German.</li><li>Du musst lernen. - You must study.</li></ul>'],
                ['title' => 'Prepositions and Cases', 'content' => '<h2>Prepositions with Accusative and Dative</h2><p>Learn which prepositions take accusative and which take dative.</p><h3>Accusative prepositions:</h3><p>durch, für, gegen, ohne, um</p><h3>Dative prepositions:</h3><p>aus, bei, mit, nach, von, zu</p>'],
            ],
            'B1' => [
                ['title' => 'Subordinate Clauses (Nebensätze)', 'content' => '<h2>Using weil, dass, ob, wenn</h2><p>Learn to form complex sentences with subordinate clauses.</p><h3>Example with "weil" (because):</h3><p>Ich lerne Deutsch, weil ich in Deutschland arbeiten möchte.</p>'],
                ['title' => 'Passive Voice', 'content' => '<h2>The Passive Voice in German</h2><p>Learn to form and use the passive voice in present and past tense.</p><h3>Present passive:</h3><p>Das Haus wird gebaut. - The house is being built.</p>'],
                ['title' => 'Comparative and Superlative', 'content' => '<h2>Comparing Things in German</h2><p>Learn to use comparative and superlative forms of adjectives.</p><h3>Examples:</h3><ul><li>groß - größer - am größten</li><li>gut - besser - am besten</li></ul>'],
            ],
            'B2' => [
                ['title' => 'Konjunktiv II (Subjunctive)', 'content' => '<h2>Express Hypothetical Situations</h2><p>Learn to use Konjunktiv II for wishes, polite requests, and unreal situations.</p><h3>Examples:</h3><ul><li>Ich würde gerne reisen. - I would like to travel.</li><li>Wenn ich Zeit hätte... - If I had time...</li></ul>'],
                ['title' => 'Nominalization', 'content' => '<h2>Turning Verbs into Nouns</h2><p>Learn to nominalize verbs to create more formal and academic language.</p><h3>Examples:</h3><ul><li>lesen → das Lesen</li><li>ankommen → die Ankunft</li></ul>'],
                ['title' => 'Discourse Markers', 'content' => '<h2>Connect Your Ideas</h2><p>Use discourse markers to structure your speech and writing.</p><h3>Examples:</h3><p>zunächst (firstly), außerdem (furthermore), jedoch (however), schließlich (finally)</p>'],
            ],
            'C1' => [
                ['title' => 'Advanced Idiomatic Expressions', 'content' => '<h2>Sound Like a Native</h2><p>Learn common idioms and colloquial expressions.</p><h3>Examples:</h3><ul><li>Die Daumen drücken - Keep your fingers crossed</li><li>Tomaten auf den Augen haben - To be oblivious</li></ul>'],
                ['title' => 'Stylistic Devices in Writing', 'content' => '<h2>Elevate Your Writing Style</h2><p>Learn to use metaphors, irony, and rhetorical questions effectively.</p>'],
                ['title' => 'Debate and Argumentation', 'content' => '<h2>Express and Defend Opinions</h2><p>Learn phrases and strategies for debates and persuasive arguments.</p>'],
            ],
            'C2' => [
                ['title' => 'Nuances and Register', 'content' => '<h2>Master Language Register</h2><p>Learn to switch between formal, neutral, and informal registers appropriately.</p>'],
                ['title' => 'Rhetoric and Persuasion', 'content' => '<h2>Advanced Rhetorical Techniques</h2><p>Learn classical rhetorical devices used in German speeches and literature.</p>'],
                ['title' => 'Analyzing Complex Texts', 'content' => '<h2>Interpretation and Analysis</h2><p>Learn to analyze literary, philosophical, and academic texts in depth.</p>'],
            ],
        ];

        foreach ($courses as $course) {
            $level = $course->level;
            $levelLessons = $lessonsByLevel[$level] ?? $lessonsByLevel['B1'];

            foreach ($levelLessons as $index => $lessonData) {
                Lesson::create([
                    'course_id' => $course->id,
                    'title' => $lessonData['title'],
                    'slug' => \Illuminate\Support\Str::slug($lessonData['title']),
                    'description' => substr($lessonData['content'], 0, 150),
                    'content' => $lessonData['content'],
                    'duration' => rand(300, 900),
                    'order' => $index + 1,
                    'is_free' => $level === 'A1',
                    'is_published' => true,
                ]);
            }
        }
    }
}
