<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * screening_rules held MAXPORT's own invented placeholder question set
 * (see the removed ScreeningRuleSeeder). Product Screening now sources
 * questions and verdicts from the AI Engineer's actual dataset at
 * backend-ai/usb-c-cable-dataset/ (App\Services\Compliance\BackendAiDataset)
 * instead — nothing worth keeping this table for.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('screening_rules');
    }

    public function down(): void
    {
        Schema::create('screening_rules', function (Blueprint $table) {
            $table->id();
            $table->string('category_key');
            $table->string('material')->nullable();
            $table->string('field_key');
            $table->string('label');
            $table->string('type');
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('flags_on_value')->nullable();
            $table->string('issue_title')->nullable();
            $table->text('issue_detail')->nullable();
            $table->timestamps();
            $table->index(['category_key', 'material']);
        });
    }
};
