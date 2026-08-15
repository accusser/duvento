<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->foreignId('workspace_id')->nullable()->change();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->nullOnDelete();
            $table->foreignId('admin_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('admin_users')
                ->nullOnDelete();
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->timestamp('dismissed_at')->nullable()->after('read_at');
            $table->index(['author_type', 'dismissed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropIndex(['author_type', 'dismissed_at']);
            $table->dropColumn('dismissed_at');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_user_id');
            $table->dropForeign(['workspace_id']);
            $table->foreignId('workspace_id')->nullable(false)->change();
            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
        });
    }
};
