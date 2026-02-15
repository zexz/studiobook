<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('slot_start');
            $table->time('slot_end');
            $table->enum('status', ['confirmed', 'cancelled'])->default('confirmed');
            $table->string('google_event_id')->nullable();
            $table->timestamps();
            
            // Unique constraint to prevent double bookings
            $table->unique(['date', 'slot_start']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
