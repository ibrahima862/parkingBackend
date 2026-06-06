<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_retraits_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('retraits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('montant', 15, 2);
            $table->string('methode');
            $table->string('numero_compte');
            $table->enum('statut', ['en_attente', 'valide', 'rejete'])->default('en_attente');
            $table->text('motif_rejet')->nullable();
            $table->string('reference_transaction')->nullable(); // ID de transaction Wave/OM une fois payé
            $table->timestamp('date_paiement')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('retraits');
    }
};