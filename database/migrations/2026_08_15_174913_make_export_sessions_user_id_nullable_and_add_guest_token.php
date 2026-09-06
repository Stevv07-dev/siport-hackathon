<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guests can now go through the whole Category → Questionnaire flow
     * without an account (see routes/web.php). Their session is tied to the
     * browser via guest_token instead of user_id until they log in/register
     * to see the result (App\Services\ExportSessionFinalizer).
     */
    public function up(): void
    {
        Schema::table('export_sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('export_sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });

        Schema::table('export_sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('guest_token')->nullable()->after('user_id');
            $table->index('guest_token');
        });
    }

    public function down(): void
    {
        Schema::table('export_sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('guest_token');
        });

        Schema::table('export_sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });

        Schema::table('export_sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
