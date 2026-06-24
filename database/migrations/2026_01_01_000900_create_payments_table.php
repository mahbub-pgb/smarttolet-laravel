<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan', 50)->nullable();
            $table->enum('gateway', ['bkash', 'nagad', 'rocket']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BDT');
            $table->enum('status', ['initiated', 'pending', 'completed', 'failed', 'cancelled'])
                ->default('initiated')->index();
            $table->string('gateway_ref')->nullable()->index(); // payment/transaction id
            $table->string('intent_id')->nullable()->unique();  // our idempotency token
            $table->json('raw')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
