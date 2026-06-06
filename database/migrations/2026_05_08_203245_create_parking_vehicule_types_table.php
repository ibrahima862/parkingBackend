<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parking_vehicule_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicule_type_id')->constrained()->onDelete('cascade');
            $table->integer('nombre_places_allouees')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_vehicule_types');
    }
};
