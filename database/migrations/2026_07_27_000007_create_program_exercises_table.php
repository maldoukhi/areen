<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_exercises', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();

            $table->unsignedSmallInteger('sort')->default(0);

            $table->unsignedTinyInteger('sets')->default(3);
            // Free text on purpose: coaches write "8-12", "الفشل", "٣٠ ثانية".
            $table->string('reps')->nullable();
            $table->unsignedSmallInteger('rest_seconds')->default(90);
            $table->string('tempo')->nullable();
            $table->string('weight_note')->nullable();

            $table->text('coach_notes_ar')->nullable();
            $table->text('coach_notes_en')->nullable();

            // Rows sharing a group are performed back to back as a superset.
            $table->string('superset_group', 8)->nullable();

            $table->timestamps();

            $table->index(['program_day_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_exercises');
    }
};
