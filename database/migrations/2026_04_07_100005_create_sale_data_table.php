<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insertion_id')->constrained('insertions')->cascadeOnDelete();
            $table->dateTime('sale_datetime');
            $table->string('sale_type');
            $table->string('sale_method');
            $table->string('base_price')->nullable();
            $table->string('minimum_bid')->nullable();
            $table->string('minimum_raise')->nullable();
            $table->string('security_deposit')->nullable();
            $table->string('expense_deposit')->nullable();
            $table->dateTime('bid_deadline');
            // venue
            $table->string('venue_address')->nullable();
            $table->string('venue_street_number')->nullable();
            $table->string('venue_zip')->nullable();
            $table->string('venue_city')->nullable();
            $table->string('venue_province')->nullable();
            $table->string('venue_region')->nullable();
            $table->string('venue_country')->nullable();
            // payment
            $table->string('exemption_reason')->nullable();
            $table->boolean('prepaid_expense')->nullable();
            $table->boolean('contribution_not_due')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_data');
    }
};
