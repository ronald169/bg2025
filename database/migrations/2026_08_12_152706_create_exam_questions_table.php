<?php
// database/migrations/xxxx_create_exam_questions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            // Plus de module_id ici : la question est partagée via le Teil
            $table->foreignId('teil_id')->constrained('exam_teils')->cascadeOnDelete();

            // Ordre local dans le Teil (pas un numéro global d'affichage)
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->string('question_type'); // true_false, yes_no, single_choice, short_answer, medium_text, oral_task
            $table->text('content');
            $table->string('image_path')->nullable();
            $table->decimal('points', 4, 1)->default(1.0);

            // Options JSON pour QCM / true_false / yes_no
            $table->json('options')->nullable();

            // Réponse attendue : "b", "Richtig", "Ja", "d", ou null pour medium_text
            $table->json('correct_answer')->nullable();

            $table->text('correct_answer_explanation')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['teil_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
