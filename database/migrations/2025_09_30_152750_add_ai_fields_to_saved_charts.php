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
        Schema::table('saved_charts', function (Blueprint $table) {
            if (!Schema::hasColumn('saved_charts', 'aiSql')) {
                $table->longText('aiSql')->nullable()->after('ai_configuration');
            }
            if (!Schema::hasColumn('saved_charts', 'aiChart')) {
                $table->longText('aiChart')->nullable()->after('aiSql');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saved_charts', function (Blueprint $table) {
            if (Schema::hasColumn('saved_charts', 'aiChart')) {
                $table->dropColumn('aiChart');
            }
            if (Schema::hasColumn('saved_charts', 'aiSql')) {
                $table->dropColumn('aiSql');
            }
        });
    }
};
