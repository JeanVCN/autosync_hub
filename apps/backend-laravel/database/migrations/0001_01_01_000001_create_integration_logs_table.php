<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->index();
            $table->string('operation')->index();
            $table->string('status')->index();
            $table->string('external_reference')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('attempts')->default(1);
            $table->timestamp('last_attempt_at')->nullable()->index();
            $table->timestamps();

            $table->index(['vehicle_id', 'provider', 'operation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
