<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('file_path');
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('total_rows')->nullable();
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->unsignedBigInteger('error_count')->default(0);
            $table->unsignedInteger('batch_count')->nullable();
            $table->unsignedInteger('completed_batches')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};


