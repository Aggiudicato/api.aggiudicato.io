<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insertion_id')->constrained('insertions')->cascadeOnDelete();
            $table->unsignedBigInteger('pvp_id');
            $table->string('primary_id');
            $table->string('code');
            $table->string('genre');
            $table->string('category');
            $table->text('description_it');
            $table->text('description_de')->nullable();
            // location
            $table->string('location_address')->nullable();
            $table->string('location_street_number')->nullable();
            $table->string('location_zip')->nullable();
            $table->string('location_city')->nullable();
            $table->string('location_province')->nullable();
            $table->string('location_region')->nullable();
            $table->string('location_country')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
