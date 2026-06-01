<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monobank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // client_info / statements
            $table->string('status')->default('pending'); // pending / running / success / failed
            $table->timestamp('date_from')->nullable();
            $table->timestamp('date_to')->nullable();
            $table->integer('records_fetched')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
