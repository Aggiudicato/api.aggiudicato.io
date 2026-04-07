<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('pvp_id')->nullable();
            $table->string('primary_id')->nullable();
            $table->string('type');
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
            // Real estate
            $table->string('availability')->nullable();
            $table->string('square_meters')->nullable();
            $table->string('rooms')->nullable();
            $table->string('floor')->nullable();
            // Movable
            $table->string('delivery_method')->nullable();
            $table->json('viewing_location')->nullable();
            $table->json('pickup_location')->nullable();
            // Business
            $table->json('ateco_categories')->nullable();
            // Complaint/report
            $table->string('complaint_type')->nullable();
            $table->string('complaint_year')->nullable();
            $table->string('complaint_number')->nullable();
            $table->date('complaint_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
