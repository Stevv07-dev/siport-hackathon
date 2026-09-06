<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('category_key');
            $table->string('category_name');
            $table->string('material');

            // 'in_progress' while the user is answering; 'completed' once the
            // AI engine has produced a verdict. Kept as a string (not enum)
            // so new statuses don't require a migration.
            $table->string('status')->default('in_progress');

            // Snapshot of the AI-generated question set at creation time, so a
            // session's history stays reproducible even if the engine's output
            // changes later (different model version, different prompt, etc).
            $table->json('questions');

            // Answers keyed by question key; updated as the user progresses
            // through the questionnaire (see progress endpoint) — this is what
            // makes a session resumable after the user leaves and comes back.
            $table->json('answers')->nullable();

            // Verdict + issues returned by the AI engine once submitted.
            $table->json('result')->nullable();

            // Which engine produced this session's questions/result — useful
            // for support/debugging when the driver changes over time.
            $table->string('engine');

            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_sessions');
    }
};
