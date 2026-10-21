<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Files attached to a project (task_id null) or to one of its tasks.
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 255);
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->string('disk', 32);
            $table->string('path', 255);
            $table->longText('text_content')->nullable(); // extracted once at upload, for AI reads
            $table->unsignedInteger('text_length')->default(0);
            $table->string('channel', 8)->default('web');
            $table->string('client', 80)->nullable();
            $table->timestamps();
            $table->index(['project_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
