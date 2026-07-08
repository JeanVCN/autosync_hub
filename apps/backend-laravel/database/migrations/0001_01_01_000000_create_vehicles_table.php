<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('external_code')->unique();
            $table->string('brand');
            $table->string('model');
            $table->string('version')->nullable();
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('model_year');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('mileage')->default(0);
            $table->string('fuel_type')->nullable();
            $table->string('transmission')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
