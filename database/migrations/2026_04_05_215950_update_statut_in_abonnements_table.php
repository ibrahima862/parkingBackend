<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            // On redéfinit la colonne avec la nouvelle option 'en_attente'
            $table->enum('statut', ['actif', 'expire', 'annule', 'en_attente'])
                  ->default('en_attente')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            // Revenir à l'état précédent en cas de rollback
            $table->enum('statut', ['actif', 'expire', 'annule'])
                  ->default('actif')
                  ->change();
        });
    }
};