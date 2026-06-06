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
       Schema::create('paiements', function (Blueprint $table) {
        $table->id();
        $table->decimal('montant', 10, 2); 
        $table->string('transaction_id')->unique()->nullable();
        $table->string('methode_paiement')->nullable(); 
        $table->enum('statut', ['en_attente', 'effectue', 'echoue', 'remboursement_en_attente','rembourse'])->default('en_attente');
  
        $table->foreignId('reservation_id')->nullable()->constrained('reservations')->onDelete('cascade');
        $table->foreignId('abonnement_id')->nullable()->constrained('abonnements')->onDelete('cascade');
        
        $table->timestamp('date_paiement')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
