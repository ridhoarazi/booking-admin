<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_category_id')
                ->constrained('sport_categories')
                ->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('location', 200);
            $table->text('description')->nullable();
            $table->decimal('price_per_hour', 12, 2);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['sport_category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fields');
    }
};
