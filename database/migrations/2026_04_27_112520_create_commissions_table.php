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
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            // nullable() est essentiel ici !
            $table->foreignId('reservation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('abonnement_id')->nullable()->constrained()->cascadeOnDelete();

            $table->decimal('taux', 5, 2);
            $table->decimal('montant', 15, 2); 

            // Ex: 'en_attente', 'valide'
            $table->string('statut')->default('en_attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
