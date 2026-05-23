<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('game_slug', 80)->index();
            $table->unsignedBigInteger('score');
            $table->unsignedInteger('level')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('accuracy')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();

            $table->index(['game_slug', 'score']);
            $table->index(['user_id', 'game_slug', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_scores');
    }
};
