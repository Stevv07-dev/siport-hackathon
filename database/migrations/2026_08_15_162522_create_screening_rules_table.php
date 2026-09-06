<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screening_rules', function (Blueprint $table) {
            $table->id();

            // '*' matches every category when no more specific rule exists —
            // see ScreeningRule::forProduct() for the fallback order.
            $table->string('category_key');

            // NULL applies to every material in the category; a specific
            // value only applies to that exact material and takes priority.
            $table->string('material')->nullable();

            $table->string('field_key');
            $table->string('label');
            $table->string('type'); // 'boolean' | 'select'
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            // Data-driven verdict logic: if the user's answer for this field
            // equals flags_on_value, the issue below is raised during
            // evaluation. NULL means this field never raises an issue on its
            // own (informational only).
            $table->string('flags_on_value')->nullable();
            $table->string('issue_title')->nullable();
            $table->text('issue_detail')->nullable();

            $table->timestamps();

            $table->index(['category_key', 'material']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_rules');
    }
};
