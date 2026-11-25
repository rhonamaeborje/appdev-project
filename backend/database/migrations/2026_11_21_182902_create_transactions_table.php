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

            $table->foreignId('borrower_id')
                ->nullable()
                ->constrained('borrowers')
                ->restrictOnDelete();

            $table->foreignId('tourist_id')
                ->nullable()
                ->constrained('tourists')
                ->restrictOnDelete();

            // Staff accounts currently use the users table.
            $table->foreignId('staff_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('date_borrowed_or_visited');
            $table->date('return_date')->nullable();
            $table->string('purpose')->nullable();
            $table->time('arrival_time')->nullable();
            $table->time('departure_time')->nullable();
            $table->string('status')->default('Pending');
            $table->text('remarks')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};