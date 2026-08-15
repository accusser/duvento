<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->text('telegram_bot_token')->nullable();
            $table->string('telegram_bot_username')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->string('telegram_chat_title')->nullable();
            $table->timestamp('telegram_connected_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_bot_token',
                'telegram_bot_username',
                'telegram_chat_id',
                'telegram_chat_title',
                'telegram_connected_at',
            ]);
        });
    }
};
