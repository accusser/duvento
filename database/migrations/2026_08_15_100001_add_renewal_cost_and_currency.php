<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('renewal_cost', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['renewal_cost', 'currency']);
        });

        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
