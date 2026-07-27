<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');

            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();

            $table->foreignId('focus_muscle_id')->nullable()
                ->constrained('muscle_groups')->nullOnDelete();

            $table->boolean('is_rest_day')->default(false);

            $table->text('notes_ar')->nullable();
            $table->text('notes_en')->nullable();

            $table->timestamps();

            // A program cannot have two day fours.
            $table->unique(['program_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_days');
    }
};
