<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicules', function (Blueprint $table) {
            $table->id();
            
            // Relation avec l'utilisateur (le propriétaire)
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('vehicule_type_id')
                  ->constrained('vehicule_types') 
                  ->onDelete('restrict'); 

            // Informations spécifiques au véhicule
            $table->string('plaque_immatriculation')->unique();
            $table->string('marque')->nullable();
            $table->string('modele')->nullable(); 
            $table->string('couleur')->nullable(); 
            
            // Pour permettre à l'utilisateur de définir un véhicule par défaut
            $table->boolean('is_main')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicules');
    }
};