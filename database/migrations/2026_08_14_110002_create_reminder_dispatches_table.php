<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before');
            $table->string('channel')->default('email');
            $table->date('sent_on');
            $table->timestamps();

            $table->unique(['asset_id', 'days_before', 'channel', 'sent_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_dispatches');
    }
};
