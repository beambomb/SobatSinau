<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('attachment_path')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->unsignedInteger('max_points')->default(100);
            $table->timestamps();
            $table->index(['classroom_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
