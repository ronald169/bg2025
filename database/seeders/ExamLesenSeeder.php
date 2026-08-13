<?php
// database/seeders/ExamLesenSeeder.php
namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\ExamModule;
use App\Models\ExamQuestion;
use App\Models\ExamTeil;
use App\Services\TeilToModuleService;
use Illuminate\Database\Seeder;

class ExamLesenSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(TeilToModuleService::class);

        // 1. Examen
        $exam = Exam::create([
            'title' => 'ÖSD Zertifikat B1',
            'slug' => 'osd-b1-modellsatz-erwachsene',
            'subtitle' => 'Modellsatz Erwachsene',
            'level' => 'B1',
            'total_duration_minutes' => 180,
            'is_active' => true,
        ]);

        // 2. Module Lesen
        $module = ExamModule::create([
            'exam_id' => $exam->id,
            'name' => 'Lesen',
            'code' => 'lesen',
            'order' => 1,
            'duration_minutes' => 65,
            'general_instructions' => 'Das Modul Lesen hat fünf Teile. Sie lesen mehrere Texte und lösen Aufgaben dazu. Sie können mit jeder Aufgabe beginnen. Für jede Aufgabe gibt es nur eine richtige Lösung.',
            'has_global_numbering' => true,
        ]);

        // ============================================================
        // TEIL 1 : Blog Susanne (Richtig / Falsch) - Questions 1-6
        // ============================================================
        $teil1 = ExamTeil::create([
            'title' => 'Teil 1',
            'order' => 0,
            'duration_minutes' => 10,
            'instructions' => 'Lesen Sie den Text und die Aufgaben 1 bis 6 dazu. Wählen Sie: Sind die Aussagen Richtig oder Falsch?',
            'content' => <<<'TEXT'
Mein Alltag, meine Gedanken, mein Leben ...

Donnerstag, den 23. Juni

Was mir heute passiert ist, das glaubt mir keiner: Als ich zu Mittag nichts ahnend in der Küche beim Kochen stand, läutete mein Handy. Eine Frauenstimme erklärte mir, dass meine Brieftasche in der Bankfiliale abgegeben worden war und ich sie dort abholen könnte. Mir wurde ganz heiß – mir war noch gar nicht aufgefallen, dass sie fehlte. Und ich hatte ja auch noch relativ viel Bargeld eingesteckt! Schnell holte ich meine Handtasche hervor und suchte nach der Brieftasche. Es stimmte! Auch nach längerem Kramen in der Tasche konnte ich sie nicht finden. Mein Geld war tatsächlich verschwunden! Ich machte mich also auf den Weg zur Bank und überlegte, wo ich meine Brieftasche liegen gelassen hatte: Sicherlich im Supermarkt an der Kassa. Jedenfalls kam ich bei der Bank an und war schon gespannt darauf zu erfahren, wo meine Brieftasche gefunden worden war und natürlich, ob etwas fehlte. Die Bankangestellte teilte mir mit, dass ein junger Mann die Brieftasche abgegeben hatte. Er hatte sie auf dem Parkplatz vor dem Supermarkt gefunden und wollte sie eigentlich ins Fundbüro bringen – wie man es in so einem Fall eben macht. Der Weg dorthin war für ihn zu weit und so suchte er nach einer anderen Möglichkeit, mir die Brieftasche zurückzugeben. Das muss man sich einmal vorstellen: Er war so clever, dass er auf der Bankomatkarte nach meinem und dem Namen meiner Bank suchte ... Die Bank würde ja die Kontaktdaten zu meinem Namen haben und könnte mich so anrufen. Er fuhr in die nächste Filiale meiner Bank und dank der Computervernetzung der Filialen konnte meine Telefonnummer schnell herausgefunden werden. Da stand ich nun mit meiner Brieftasche, die mir beim Verlassen des Supermarktes aus der Handtasche gerutscht sein muss. Zum Glück war alles noch da! Ich bin sooo froh, dass diese Episode so gut ausgegangen ist.

Bis bald, eure Susanne
TEXT,
            'content_image' => null,
            'audio_path' => null,
            'source' => 'SusannesAlltagsBlog.at',
        ]);

        $service->attach($module, $teil1, 0);

        // Questions Teil 1 (Richtig/Falsch)
        $teil1Questions = [
            [
                'content' => 'Susanne glaubte, die Brieftasche beim Bezahlen vergessen zu haben.',
                'correct_answer' => ['Richtig'],
                'explanation' => 'Sie überlegte: "Sicherlich im Supermarkt an der Kassa."',
            ],
            [
                'content' => 'Der Finder hatte die Brieftasche ins Fundbüro gebracht.',
                'correct_answer' => ['Falsch'],
                'explanation' => 'Er brachte sie in eine Bankfiliale.',
            ],
            [
                'content' => 'Die Telefonnummer der Bank war in der Brieftasche.',
                'correct_answer' => ['Falsch'],
                'explanation' => 'Die Bank fand die Nummer über die Computervernetzung.',
            ],
            [
                'content' => 'In Susannes Brieftasche fehlte nichts.',
                'correct_answer' => ['Richtig'],
                'explanation' => '"Zum Glück war alles noch da!"',
            ],
            [
                'content' => 'Susanne konnte dem Finder persönlich für seine Ehrlichkeit danken.',
                'correct_answer' => ['Falsch'],
                'explanation' => 'Sie bedankte sich im Blog ("Vielen Dank, lieber Finder!").',
            ],
            [
                'content' => 'Der Finder fuhr zur Bank, weil der Weg zum Fundbüro zu weit war.',
                'correct_answer' => ['Richtig'],
                'explanation' => 'Der Weg zum Fundbüro war zu weit.',
            ],
        ];

        foreach ($teil1Questions as $index => $q) {
            ExamQuestion::create([
                'teil_id' => $teil1->id,
                'sort_order' => $index,
                'question_type' => QuestionType::TRUE_FALSE->value,
                'content' => $q['content'],
                'points' => 1,
                'options' => [
                    ['label' => 'Richtig', 'content' => null],
                    ['label' => 'Falsch', 'content' => null],
                ],
                'correct_answer' => $q['correct_answer'],
                'correct_answer_explanation' => $q['explanation'],
            ]);
        }

        // ============================================================
        // TEIL 2 : Texte de presse (QCM a/b/c) - Questions 7-12
        // ============================================================
        $teil2 = ExamTeil::create([
            'title' => 'Teil 2',
            'order' => 0,
            'duration_minutes' => 20,
            'instructions' => 'Lesen Sie die Texte aus der Presse und die Aufgaben 7 bis 12 dazu. Wählen Sie bei jeder Aufgabe die richtige Lösung a, b oder c.',
            'content' => <<<'TEXT'
TEXTE 1:
Ein Dorf für grüne Energie
Das Dorf Feldheim in Brandenburg macht sich unabhängig von Öl und Gas. Seit Kurzem deckt das Dorf seinen kompletten Strombedarf und drei Viertel des Wärmebedarfs durch moderne Energien. "Das funktioniert mithilfe einer modernen Anlage für Bio-Gas", erklärt der Diplom-Physiker Eckhard Meier. "Da kommen Abfall von den Tieren, Getreide und Holz rein und werden erwärmt. Ein Motor verbrennt das Gas und erzeugt dabei Wärme. Der Motor treibt dann einen Generator an, der Strom leisten." Tatsächlich: Die Bio-Gasanlage erzeugt jährlich doppelt so viel Strom wie die Gemeinde verbraucht. Der Rest wird in das Stromnetz abgegeben und kostenlos anderen Dörfern zur Verfügung gestellt. Passt das Konzept auch für andere Dörfer? "Im Prinzip schon", meint Eckhard Meier. Die technischen Anlagen könnten an anderen Orten genauso aufgebaut werden – der Raumbedarf ist gering. Man benötigt allerdings vor allem eines: aktive und begeisterte Einwohner! Entstanden ist die Idee des "Bio-Energiedorfs" an der Universität Göttingen. Ziel der Wissenschaftler war es zu zeigen, dass es möglich ist, ein Dorf komplett mit erneuerbaren Energien zu versorgen und damit einen Beitrag zum Klimaschutz zu leisten.

TEXTE 2:
Tour durch Murtens Geschichte
Mit der Rundfahrt "Zeitreise per Velo" können Touristen das Städtchen Murten und seine Geschichte sportlich neu entdecken. Die Tour startet am Bahnhof von Murten, wo die sportlichen Teilnehmer auf das eigene oder ein gemietetes Velo steigen. Die weniger sportlichen und jene, die es schon immer ausprobieren wollten, steigen aufs Elektro-Velo. Dieses kann ebenfalls am Bahnhof gemietet werden. Vom Bahnhof führt der Weg auf den historischen Hügel, wo Karl der Kühne sein Hauptquartier aufbaute, bevor sein Heer im Jahr 1476 besiegt wurde. Oben angekommen kann man die wunderbare Aussicht auf den Murtensee genießen. Nach einer kurzen Pause geht es weiter nach Merlach. Dort steht ein Denkmal für Soldaten, die in der Schlacht bei Murten 1476 umgekommen sind. Danach geht die Fahrt zum Hafen und in die Altstadt. Unterwegs erfahren die Velofahrer vieles über die Region. "Mit der Velorundfahrt für Gruppen wollen wir unser Angebot für aktive Radfahrer erweitern", sagt der Geschäftsführer von Murten Tourismus. Damit soll sowohl das Gebiet für Velo-Touristen interessant gemacht als auch der Trend zum E-Bike unterstützt werden.
TEXT,
            'content_image' => null,
            'audio_path' => null,
            'source' => 'Texte 1: aus einer deutschen Zeitung | Texte 2: aus einer Schweizer Broschüre',
        ]);

        $service->attach($module, $teil2, 1);

        $teil2Questions = [
            // Texte 1 (Feldheim) - Q7-9
            [
                'content' => 'In diesem Text geht es um ...',
                'options' => [
                    ['label' => 'a', 'content' => 'die neue Technologie von Eckhard Meier.'],
                    ['label' => 'b', 'content' => 'die umweltfreundliche Stromproduktion in Feldheim.'],
                    ['label' => 'c', 'content' => 'einen Studiengang an der Universität Göttingen.'],
                ],
                'correct_answer' => ['b'],
                'explanation' => 'Le texte parle de la production d\'énergie verte à Feldheim.',
            ],
            [
                'content' => 'Die Wissenschaftler wollten zeigen, dass ...',
                'options' => [
                    ['label' => 'a', 'content' => 'man große Mengen Strom sparen kann.'],
                    ['label' => 'b', 'content' => 'ein Dorf komplett mit erneuerbaren Energien versorgen kann.'],
                    ['label' => 'c', 'content' => 'die Bio-Gasanlage sehr teuer ist.'],
                ],
                'correct_answer' => ['b'],
                'explanation' => 'Ziel: "ein Dorf komplett mit erneuerbaren Energien zu versorgen".',
            ],
            [
                'content' => 'Damit die Idee auch in anderen Dörfern funktioniert, ...',
                'options' => [
                    ['label' => 'a', 'content' => 'benötigt man viel Geld.'],
                    ['label' => 'b', 'content' => 'braucht man genug Platz für die Technik.'],
                    ['label' => 'c', 'content' => 'muss die Bevölkerung dafür sein.'],
                ],
                'correct_answer' => ['c'],
                'explanation' => '"Man benötigt... aktive und begeisterte Einwohner!"',
            ],
            // Texte 2 (Murten) - Q10-12
            [
                'content' => 'In diesem Text geht es darum, dass ...',
                'options' => [
                    ['label' => 'a', 'content' => 'die Geschichte von Murten neu erzählt wird.'],
                    ['label' => 'b', 'content' => 'es ein neues Tourismus-Angebot gibt.'],
                    ['label' => 'c', 'content' => 'man in Murten neue Velo-Wege bauen will.'],
                ],
                'correct_answer' => ['b'],
                'explanation' => 'Il s\'agit d\'une nouvelle offre touristique à vélo.',
            ],
            [
                'content' => 'Für die Rundfahrt ...',
                'options' => [
                    ['label' => 'a', 'content' => 'braucht man ein eigenes Velo.'],
                    ['label' => 'b', 'content' => 'muss man nicht sportlich sein.'],
                    ['label' => 'c', 'content' => 'sollte man mit der Bahn anreisen.'],
                ],
                'correct_answer' => ['b'],
                'explanation' => 'Les E-Bikes permettent aux moins sportifs de participer.',
            ],
            [
                'content' => 'Der Geschäftsführer von Murten Tourismus will, dass ...',
                'options' => [
                    ['label' => 'a', 'content' => 'es in Murten mehr Stadtführungen für Gruppen gibt.'],
                    ['label' => 'b', 'content' => 'die Leute normale Velos statt Elektro-Velos benutzen.'],
                    ['label' => 'c', 'content' => 'mehr Velo-Touristen in die Region kommen.'],
                ],
                'correct_answer' => ['c'],
                'explanation' => '"wir unser Angebot für aktive Radfahrer erweitern"',
            ],
        ];

        foreach ($teil2Questions as $index => $q) {
            ExamQuestion::create([
                'teil_id' => $teil2->id,
                'sort_order' => $index,
                'question_type' => QuestionType::SINGLE_CHOICE->value,
                'content' => $q['content'],
                'points' => 1,
                'options' => $q['options'],
                'correct_answer' => $q['correct_answer'],
                'correct_answer_explanation' => $q['explanation'],
            ]);
        }

        // ============================================================
        // TEIL 3 : Annonces (Short Answer lettre) - Questions 13-19
        // ============================================================
        $teil3 = ExamTeil::create([
            'title' => 'Teil 3',
            'order' => 0,
            'duration_minutes' => 10,
            'instructions' => 'Lesen Sie die Situationen 13 bis 19 und die Anzeigen A bis J aus verschiedenen deutschsprachigen Medien. Wählen Sie: Welche Anzeige passt zu welcher Situation? Sie können jede Anzeige nur einmal verwenden. Die Anzeige aus dem Beispiel können Sie nicht mehr verwenden. Für eine Situation gibt es keine passende Anzeige. In diesem Fall schreiben Sie X.',
            'content' => <<<'TEXT'
ANZEIGEN:

A) Neu im Verlagsprogramm: Schweizer Autoren, leicht gemacht. Nach 100 Lernstunden schon literarische Kurzgeschichten, Romane und Gedichte lesen? Kein Problem! Die Reihe "Schweizer Autoren, leicht gemacht" bietet Deutschlernern vereinfachte Originalversionen für uneingeschränktes Lesevergnügen. www.schweizerverlag.ch

B) Trainingsprogramm Deutsch. Sie wollen Ihre Sprachkenntnisse verbessern, haben aber keine Zeit für Kurse? Dann lernen Sie Deutsch im Internet! Unser Lernportal bietet Ihnen gratis: – 10 Kurslektionen für Anfänger und Fortgeschrittene – Erklärungen zur Grammatik – alle Übungen online verfügbar

C) Deutsch in der Schweiz. Unser Angebot: – Intensivkurse mit 20–30 Wochenstunden – Schreibkurse (auch als Fernstudium!) – Sommerkurse für Jugendliche und Erwachsene (mit Freizeitprogramm) – Kurs: Deutsch im Hotel. Wir bieten nur Tageskurse an! www.deutschinderschweiz.ch

D) Job & Sprache-Net. Wir bieten Jobs für Deutschlernende in Deutschland, Österreich und in der Schweiz. Perfektionieren Sie Ihre Sprachkenntnisse und sammeln Sie Erfahrungen in den Arbeitsbereichen Hotel und Restaurant. Dauer: bis zu 3 Monate (Juni–August). Kosten für Unterkunft und Verpflegung werden übernommen. www.jobundsprache-net.com

E) Sprachschule ORION sucht engagierte Trainer und Trainerinnen (Vollzeit). Kurszeiten von 8:00–17:00 h. Niveaus A1–C1. Allgemeine und berufsbezogene Sprachkurse (z. B. Deutsch für den Tourismus). Bewerbungen an: office@deutschintensiv.de

F) Deutsch erLesen. Das Magazin Deutsch erLesen richtet sich an Deutschinteressierte im In- und Ausland. Es erscheint einmal im Monat und enthält aktuelle Originalartikel aus der deutschen Presse. Deutschland erfahren & Deutsch lernen! Bestellen Sie noch heute Ihr Probeexemplar: info@deutsch-erlesen.de

G) Verlag für deutsche Literatur sucht Lektor/Lektorin für die Auswahl und Korrektur von Werken junger deutscher Autoren. Unser Verlagsprogramm umfasst Romane, Gedichtbände und Hörbücher. Schicken Sie Ihre Bewerbung an: junge-literatur@berlin.de

H) Deutsch in Linz. Deutsch-Intensivkurse Mo bis Fr von 9:30–13 h und von 14:00–17 h. Kurse für Berufstätige. Für Berufstätige und Vielbeschäftigte bieten wir flexible Kurszeiten an (Termine nach Wunsch). Online-Einstufungstest auf www.deutschinlinz-schule.at

I) Sprache und Kultur in Wien. Deutschkurse ganzjährig! Spezialangebote für den Sommer. Infos unter: www.sprache-kultur@aon.at

J) Neues Computerprogramm von DIGITAL LEARNING. Für Büromanagement und Buchhaltung in englischer und deutscher Sprache. Ab sofort im Buchhandel erhältlich. Infos: software@digital-learning.net
TEXT,
            'content_image' => null, // Les annonces étaient en image dans le PDF
            'audio_path' => null,
            'source' => 'Aus verschiedenen deutschsprachigen Medien',
        ]);

        $service->attach($module, $teil3, 2);

        $teil3Questions = [
            ['content' => 'Leon möchte im Sommer im Tourismus-Bereich arbeiten, um sein Deutsch zu verbessern.', 'correct' => 'd'],
            ['content' => 'Giovanna sucht deutsche Hörbücher, damit sie unterwegs Deutsch lernen kann.', 'correct' => 'g'],
            ['content' => 'Mirjeta hat keine Zeit für einen Kurs, möchte sich aber regelmäßig über Neuigkeiten aus Deutschland informieren.', 'correct' => 'f'],
            ['content' => 'Maria möchte am Computer Deutsch lernen.', 'correct' => 'b'],
            ['content' => 'Susan liest am liebsten Literatur, wenn die Texte nicht zu schwierig sind.', 'correct' => 'a'],
            ['content' => 'Miroslav will den schriftlichen Ausdruck verbessern, weil er im Studium viel schreiben muss.', 'correct' => 'c'],
            ['content' => 'Juan kann nur am Abend einen Kurs besuchen.', 'correct' => 'h'],
        ];

        foreach ($teil3Questions as $index => $q) {
            ExamQuestion::create([
                'teil_id' => $teil3->id,
                'sort_order' => $index,
                'question_type' => QuestionType::SHORT_ANSWER->value,
                'content' => $q['content'],
                'points' => 1,
                'options' => null, // Pas d'options affichées, le candidat écrit la lettre
                'correct_answer' => [$q['correct']],
                'correct_answer_explanation' => 'Anzeige ' . strtoupper($q['correct']),
            ]);
        }

        // ============================================================
        // TEIL 4 : Leserbriefe (Ja / Nein) - Questions 20-26
        // ============================================================
        $teil4 = ExamTeil::create([
            'title' => 'Teil 4',
            'order' => 0,
            'duration_minutes' => 15,
            'instructions' => 'Lesen Sie die Texte 20 bis 26. Wählen Sie: Ist die Person für ein Verbot von Videospielen mit Gewalt, Ja oder Nein?',
            'content' => <<<'TEXT'
In einer Zeitschrift lesen Sie Kommentare zu einem Artikel über das Verbot von Videospielen, in denen viel Gewalt vorkommt (sogenannte "Killerspiele").

20) Stefan, 19, Graz:
"Ich könnte mir vorstellen, dass ein Verbot die gegenteilige Wirkung hätte, denn ein verbotenes Spiel ist doch noch interessanter als ein nicht verbotenes! Außerdem ist es gar nicht möglich, alle Killerspiele abzuschaffen, weil es davon schon viel zu viele gibt. Mein Fazit: Warum 'Killerspiele' verbieten, wenn es im Endeffekt sowieso alle spielen und das Ganze gerade durch ein Verbot noch interessanter wird?"

21) Dagmar, 23, Leipzig:
"Wer entscheidet letztlich darüber, welche Spiele man nicht braucht? Dürfen diese Menschen dann auch darüber entscheiden, welche Bücher, Filme oder Musik wir nicht brauchen? Viel wichtiger ist es doch, dass Kinder und Jugendliche lernen, selbst zwischen virtueller und realer Gewalt zu unterscheiden!"

22) Kathleen, 49, Cuxhaven:
"'Töten auf Probe' soll erlaubt sein? Das bedeutet: Mal schnell zu üben, wie man jemanden umbringt, ist eine Freizeitbeschäftigung. Wie zynisch kann man sein? Nicht jeder wird zum Glück zum Monster, der sich mit so viel Gewalt und Zerstörung beschäftigt. Die Einstellung dahinter ist aber Ausdruck einer unglaublichen Gleichgültigkeit. Das muss man stoppen, und zwar schnell."

23) Marius, 34, St. Gallen:
"Ich spiele sogenannte Killerspiele wie CaDu seit bald drei Jahren regelmäßig. Ich habe eine kleine Tochter, eine Frau und einen Job und spiele für den Ausgleich. Nur weil es mal dazu kommt, dass einer auf dieser Welt das Spiel als Realität sieht und durchdreht, müssen dann all die anderen ein Verbot hinnehmen? Es wäre besser, die Altersbeschränkung auf 18 Jahre festzulegen und sie auch strikt einzuhalten."

24) Jonny, 21, Berlin:
"'Killerspiele' machen schnell aggressiv und man wird davon abhängig. Außerdem besteht die Gefahr, dass jemand nicht mit solchen Spielen umgehen kann und zum Nachahmungstäter wird. Das sind nur zwei Gründe, warum man gegen diese Spiele endlich etwas tun sollte."

25) Robert, 18, Winterthur:
"In dieser Diskussion fehlt immer die genaue Kenntnis! Meistens ist es bei sogenannten 'Killerspielen' nämlich so, dass man in einem Team spielt. Ein solches Spiel stärkt also den Teamgeist. Außerdem steht die Taktik im Vordergrund und nicht eine bestimmte Methode, jemanden umzubringen. So wird das taktische bzw. logische Denken gefördert!"

26) Marinette, 38, Frankfurt:
"Ich denke, dass gewisse Situationen oder Dinge einen Menschen dazu bringen können, etwas zu tun, das er sonst nicht tun würde. Das kann gerade bei sogenannten 'Killerspielen' der Fall sein. Deshalb scheint mir ein Verbot sinnvoll zu sein, auch wenn so ein Verbot allein wahrscheinlich nicht viel nützt, denn Killerspiele sind ja nur eine 'Inspirationsquelle' für Gewalt."
TEXT,
            'content_image' => null,
            'audio_path' => null,
            'source' => 'Leserbriefe aus einer Zeitschrift',
        ]);

        $service->attach($module, $teil4, 3);

        $teil4Questions = [
            ['content' => 'Stefan', 'correct' => 'Nein'],
            ['content' => 'Dagmar', 'correct' => 'Nein'],
            ['content' => 'Kathleen', 'correct' => 'Ja'],
            ['content' => 'Marius', 'correct' => 'Nein'],
            ['content' => 'Jonny', 'correct' => 'Ja'],
            ['content' => 'Robert', 'correct' => 'Nein'],
            ['content' => 'Marinette', 'correct' => 'Ja'],
        ];

        foreach ($teil4Questions as $index => $q) {
            ExamQuestion::create([
                'teil_id' => $teil4->id,
                'sort_order' => $index,
                'question_type' => QuestionType::YES_NO->value,
                'content' => $q['content'],
                'points' => 1,
                'options' => [
                    ['label' => 'Ja', 'content' => null],
                    ['label' => 'Nein', 'content' => null],
                ],
                'correct_answer' => [$q['correct']],
                'correct_answer_explanation' => null,
            ]);
        }

        // ============================================================
        // TEIL 5 : Hausordnung (QCM a/b/c) - Questions 27-30
        // ============================================================
        $teil5 = ExamTeil::create([
            'title' => 'Teil 5',
            'order' => 0,
            'duration_minutes' => 10,
            'instructions' => 'Lesen Sie die Aufgaben 27 bis 30 und den Text dazu. Wählen Sie bei jeder Aufgabe die richtige Lösung a, b oder c.',
            'content' => <<<'TEXT'
HAUSORDNUNG

Unterrichtszeiten: Die vereinbarten Unterrichtszeiten sind verbindlich. Ist die Lehrperson zehn Minuten nach Unterrichtsbeginn nicht da, informiert die Klassenvertretung das Sekretariat.

Ordnung: In sämtlichen Räumen und Anlagen unserer Schule ist auf Ordnung und Sauberkeit zu achten. Schulräume, Einrichtungen und Anlagen sind sorgfältig zu benutzen. Außerhalb der Unterrichtszeiten dürfen sich Lernende nicht in den Klassenräumen aufhalten. Es ist untersagt, in den Klassenräumen etwas an die Wände zu kleben oder zu schreiben und Schulmöbel in andere Räume zu bringen. Mitarbeitende und Lernende, die Schäden feststellen, melden diese dem Sekretariat.

Störungen: Mitarbeitende und Lernende sorgen dafür, dass der Schulbetrieb nicht gestört wird.

Alkohol- und Drogenkonsum: Der Konsum von Alkohol, illegalen Drogen sowie anderen psychoaktiven Substanzen ist auf dem gesamten Schulareal und während schulischer Veranstaltungen (einschließlich aller Pausen) verboten. In Ausnahmefällen kann die Schulleitung den Konsum von Alkohol genehmigen.

Rauchen: Rauchen ist nur im Freien beziehungsweise in den dafür vorgesehenen Zonen gestattet. Wir bitten darum, die aufgestellten Aschenbecher zu benutzen.

Diebstahl: Es empfiehlt sich, Wertsachen und Bargeld sorgfältig aufzubewahren. Die Schule stellt den Lernenden und Mitarbeitenden kostenlos Schließfächer zur Verfügung. Für verlorene Schlüssel wird eine Gebühr von Euro 50,– erhoben. Die Schule übernimmt für Diebstähle keine Haftung.

Fundgegenstände: Fundgegenstände bitte im Sekretariat abgeben.

Parkplätze: Auf dem Schulareal stehen keine Gratis-Autoparkplätze zur Verfügung. Fahrräder müssen in den dafür vorgesehenen Fahrradkeller gebracht und abgeschlossen werden. Mopeds und Motorräder sind auf dem Schulareal nicht erlaubt.
TEXT,
            'content_image' => null,
            'audio_path' => null,
            'source' => 'Hausordnung Dresdner Berufsbildungszentrum BZW',
        ]);

        $service->attach($module, $teil5, 4);

        $teil5Questions = [
            [
                'content' => 'Schüler ...',
                'options' => [
                    ['label' => 'a', 'content' => 'dürfen keine Fahrräder mit zur Schule bringen.'],
                    ['label' => 'b', 'content' => 'dürfen ihre Fahrräder auf den Schulhof stellen.'],
                    ['label' => 'c', 'content' => 'müssen ihre Fahrräder in einen speziellen Raum stellen.'],
                ],
                'correct' => 'c',
            ],
            [
                'content' => 'Für die Klassenräume des BZW gilt:',
                'options' => [
                    ['label' => 'a', 'content' => 'Schüler dürfen keine Poster aufhängen.'],
                    ['label' => 'b', 'content' => 'Schüler müssen dort selber aufräumen.'],
                    ['label' => 'c', 'content' => 'Schüler können dort nach dem Unterricht lernen.'],
                ],
                'correct' => 'b',
            ],
            [
                'content' => 'Um die verschließbaren Fächer benutzen zu können, muss man ...',
                'options' => [
                    ['label' => 'a', 'content' => 'einen Schlüssel im Sekretariat verlangen.'],
                    ['label' => 'b', 'content' => 'einmalig 50,– Euro zahlen.'],
                    ['label' => 'c', 'content' => 'Schüler sein oder im BZW arbeiten.'],
                ],
                'correct' => 'c',
            ],
            [
                'content' => 'Das Trinken von Alkohol ...',
                'options' => [
                    ['label' => 'a', 'content' => 'kann von der Schulleitung genehmigt werden.'],
                    ['label' => 'b', 'content' => 'muss der Lehrperson gemeldet werden.'],
                    ['label' => 'c', 'content' => 'ist ohne Ausnahme verboten.'],
                ],
                'correct' => 'c',
            ],
        ];

        foreach ($teil5Questions as $index => $q) {
            ExamQuestion::create([
                'teil_id' => $teil5->id,
                'sort_order' => $index,
                'question_type' => QuestionType::SINGLE_CHOICE->value,
                'content' => $q['content'],
                'points' => 1,
                'options' => $q['options'],
                'correct_answer' => [$q['correct']],
                'correct_answer_explanation' => null,
            ]);
        }

        // Mise à jour du score max du module
        $maxScore = $service->calculateMaxScore($module);
        $module->attempts()->update(['max_possible_score' => $maxScore]);
    }
}
