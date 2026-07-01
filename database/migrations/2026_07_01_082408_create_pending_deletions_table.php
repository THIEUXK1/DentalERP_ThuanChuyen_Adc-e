<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_deletions', function (Blueprint $table) {
            $table->id();
            $table->morphs('deletable');
            $table->text('reason');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->timestamp('execute_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_deletions');
    }
};
