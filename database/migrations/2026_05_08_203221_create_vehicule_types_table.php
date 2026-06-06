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
        Schema::create('vehicule_types', function (Blueprint $table) {
            $table->id();
            $table->string('libelle'); 
            $table->string('icon')->nullable();
            $table->decimal('multiplicateur_prix', 8, 2)->default(1.0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicule_types');
    }
};
