<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('parking_horaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_id')->constrained()->onDelete('cascade');
            $table->string('jour');
            $table->time('heure_ouverture');
            $table->time('heure_fermeture');
            $table->boolean('est_ferme')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_horaires');
    }
};
