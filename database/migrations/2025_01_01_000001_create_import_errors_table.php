<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('row_number')->nullable();
            $table->json('row_data')->nullable();
            $table->string('error_message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};


