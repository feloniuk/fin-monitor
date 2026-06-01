<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monobank_account_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id')->unique();
            $table->timestamp('time');
            $table->text('description')->nullable();
            $table->integer('mcc')->nullable();
            $table->bigInteger('amount');
            $table->bigInteger('operation_amount');
            $table->integer('currency_code');
            $table->bigInteger('commission_rate')->default(0);
            $table->bigInteger('cashback_amount')->default(0);
            $table->bigInteger('balance');
            $table->boolean('hold')->default(false);
            $table->text('comment')->nullable();
            $table->string('receipt_id')->nullable();
            $table->string('counter_edrpou')->nullable();
            $table->string('counter_iban')->nullable();
            $table->string('counter_name')->nullable();
            $table->timestamps();

            $table->index(['monobank_account_id', 'time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
