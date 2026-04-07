<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadastral_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('section')->nullable();
            $table->string('sheet');
            $table->string('parcel');
            $table->string('sub_parcel')->nullable();
            $table->string('sub_unit')->nullable();
            $table->string('sub_unit_2')->nullable();
            $table->string('stapled')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadastral_records');
    }
};
