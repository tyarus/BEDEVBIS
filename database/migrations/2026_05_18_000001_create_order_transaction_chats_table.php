<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_transaction_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->enum('status', [
                'chat_open',
                'account_verification',
                'account_secured',
                'device_cleanup',
                'awaiting_completion_code',
                'completed',
                'disputed'
            ])->default('chat_open');
            $table->string('completion_code_hash')->nullable();
            $table->timestamp('completion_code_expires_at')->nullable();
            $table->timestamp('completion_code_verified_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_transaction_chats');
    }
};
