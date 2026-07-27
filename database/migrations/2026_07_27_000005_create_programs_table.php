<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('slug')->unique();

            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            $table->unsignedTinyInteger('days_count')->default(0);
            $table->string('level')->default('beginner');
            $table->string('goal')->nullable();
            $table->string('cover_path')->nullable();

            // Private programs are reachable only through their access code.
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('access_code', 32)->nullable()->unique();

            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Drives the public listing: published, public, ordered.
            $table->index(['is_public', 'published_at']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
