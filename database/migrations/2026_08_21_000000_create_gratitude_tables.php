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
        Schema::create('gratitude_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->timestamps();

            // One entry per person per day; the page reaches for a day and gets the same row back.
            $table->unique(['user_id', 'entry_date']);
        });

        Schema::create('gratitude_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('gratitude_entries')->cascadeOnDelete();
            $table->text('body');
            $table->unsignedInteger('position');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gratitude_items');
        Schema::dropIfExists('gratitude_entries');
    }
};
