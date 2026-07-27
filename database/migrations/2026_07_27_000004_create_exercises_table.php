<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();

            $table->foreignId('muscle_group_id')->constrained()->restrictOnDelete();
            // Secondary muscles are a display hint, not a relationship worth a pivot.
            $table->json('secondary_muscles')->nullable();

            $table->string('equipment')->nullable();
            $table->string('difficulty')->default('beginner');

            $table->string('youtube_url')->nullable();
            $table->string('media_path')->nullable();

            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['muscle_group_id', 'is_active']);
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
