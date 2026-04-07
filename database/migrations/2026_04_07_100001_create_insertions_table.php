<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insertions', function (Blueprint $table) {
            $table->id();
            $table->string('pvp_id')->unique();
            $table->string('message_id')->nullable();
            $table->string('type');
            $table->string('advertising_days')->nullable();
            $table->date('publication_date');
            $table->string('xml_path')->nullable();
            $table->longText('xml_raw')->nullable();
            $table->enum('status', ['received', 'published', 'error'])->default('received');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insertions');
    }
};
