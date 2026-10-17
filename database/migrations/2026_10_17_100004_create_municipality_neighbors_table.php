<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipality_neighbors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_version_id')->constrained()->cascadeOnDelete();
            $table->string('psgc', 10);
            $table->string('neighbor_psgc', 10);
            $table->enum('kind', ['knn', 'nearby']);
            $table->unsignedTinyInteger('neighbor_order');
            $table->double('km');
            $table->unique(['dataset_version_id', 'psgc', 'neighbor_psgc', 'kind'], 'neighbors_unique');
            $table->index(['dataset_version_id', 'psgc', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_neighbors');
    }
};
