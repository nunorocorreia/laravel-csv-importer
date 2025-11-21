<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')
                ->nullable()
                ->constrained('imports')
                ->nullOnDelete();
            $table->string('external_id')->unique();
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('external_id');
            $table->index('import_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};


