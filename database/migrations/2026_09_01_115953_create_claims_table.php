<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('policy_number')->unique();
            $table->string('insured_name');
            $table->date('loss_date');
            $table->date('date_notified');
            $table->string('loss_nature');
            $table->string('reserve_currency', 3);
            $table->bigInteger('estimated_loss_amount');
            $table->bigInteger('approved_amount')->nullable();
            $table->timestamps();

            $table->index('reserve_currency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
