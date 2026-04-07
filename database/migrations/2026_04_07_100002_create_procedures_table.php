<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insertion_id')->constrained('insertions')->cascadeOnDelete();
            $table->string('pvp_id');
            $table->string('type');
            // Judicial procedure fields
            $table->string('court_pvp_id')->nullable();
            $table->string('rite_pvp_id')->nullable();
            $table->string('registry_pvp_id')->nullable();
            $table->string('court')->nullable();
            $table->string('registry')->nullable();
            $table->string('rite')->nullable();
            $table->string('number')->nullable();
            $table->string('year')->nullable();
            // Other sale fields
            $table->string('proceeding')->nullable();
            $table->string('proceeding_number')->nullable();
            $table->string('proceeding_year')->nullable();
            $table->string('pledge_holder_first_name')->nullable();
            $table->string('pledge_holder_last_name')->nullable();
            $table->string('pledge_holder_tax_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
