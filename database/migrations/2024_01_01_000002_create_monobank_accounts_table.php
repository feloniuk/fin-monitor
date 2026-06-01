<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monobank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monobank_token_id')->constrained()->cascadeOnDelete();
            $table->string('account_id')->unique();
            $table->integer('currency_code');
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('credit_limit')->default(0);
            $table->json('masked_pan')->nullable();
            $table->string('type')->nullable();
            $table->string('iban')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_statement_sync_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monobank_accounts');
    }
};
