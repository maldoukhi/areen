<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single row holding everything that belongs to the club rather than to the
 * platform. Areen may be sold to another club, so none of this may ever be
 * written into the code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();

            $table->string('club_name_ar');
            $table->string('club_name_en')->nullable();
            $table->string('tagline_ar')->nullable();
            $table->string('tagline_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();

            $table->string('address_ar')->nullable();
            $table->string('address_en')->nullable();
            $table->string('city_ar')->nullable();
            $table->string('city_en')->nullable();
            $table->string('map_url')->nullable();

            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('instagram')->nullable();
            $table->string('email')->nullable();

            $table->string('logo_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
