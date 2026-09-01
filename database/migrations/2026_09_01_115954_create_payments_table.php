<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('claim_id')->constrained()->cascadeOnDelete();
            $table->date('payment_date');
            $table->bigInteger('amount');
            $table->string('currency', 3);
            $table->decimal('fx_rate_snapshot', 12, 6);
            $table->timestamps();

            $table->index('currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
