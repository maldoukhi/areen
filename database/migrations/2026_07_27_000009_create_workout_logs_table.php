<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_exercise_id')->constrained()->cascadeOnDelete();

            $table->date('performed_on');
            $table->unsignedTinyInteger('set_number');

            $table->unsignedSmallInteger('reps_done')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->boolean('is_completed')->default(true);
            $table->string('note')->nullable();

            /*
             * Generated in the browser before the set is ever sent. A set logged
             * with no signal is stored locally and pushed later, possibly more
             * than once — the server upserts on this column so a retry can never
             * duplicate a set.
             */
            $table->uuid('client_uuid')->unique();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();

            // Drives the progress charts and the "what did I lift last time" lookup.
            $table->index(['user_id', 'performed_on']);
            $table->index(['user_id', 'program_exercise_id', 'performed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_logs');
    }
};
