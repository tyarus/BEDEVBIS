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
        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id')->unsigned()->unique();
            $table->bigInteger('buyer_id')->unsigned();
            $table->bigInteger('seller_id')->unsigned();

            // Cancellation reason
            $table->enum('reason', [
                'urgent_payment_delay',
                'product_mismatch',
                'ordering_mistake',
                'other'
            ])->nullable(false);

            // Details about cancellation
            $table->longText('details')->nullable();

            // Request status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Seller response
            $table->longText('seller_notes')->nullable();
            $table->longText('rejection_reason')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('buyer_id')->references('id')->on('users');
            $table->foreign('seller_id')->references('id')->on('users');

            // Indexes
            $table->index(['seller_id', 'status']);
            $table->index(['buyer_id', 'status']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cancellation_requests');
    }
};
