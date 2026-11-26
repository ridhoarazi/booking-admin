<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('field_schedule_id')
                ->constrained('field_schedules')
                ->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('available');
            $table->timestamps();

            $table->unique(['field_schedule_id', 'start_time']);
            $table->index(['status', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_slots');
    }
};
