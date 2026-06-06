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
       
            Schema::create('reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('parking_id')->constrained()->onDelete('cascade');
                $table->string('nom_conducteur');
                $table->string('prenom_conducteur');
                $table->string('matricule_vehicule');
                $table->dateTime('date_debut');
                $table->dateTime('date_fin');
                $table->string('telephone')->nullable();
                $table->decimal('montant_total', 10, 2);
                $table->enum('statut', ['en_attente', 'confirme', 'annule', 'termine'])->default('en_attente');
                $table->string('parking_status')->default('attendu');
                $table->timestamps();
            });
    
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
