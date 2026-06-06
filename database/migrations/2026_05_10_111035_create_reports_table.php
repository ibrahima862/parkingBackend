<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Le client
            $table->foreignId('parking_id')->constrained()->onDelete('cascade'); // Le parking
            
            // Détails du signalement
            $table->string('category'); // 'infrastructure', 'tarif', 'comportement', etc.
            $table->text('description');
            $table->string('evidence_url')->nullable(); // Photo optionnelle
            
            // État du traitement
            $table->enum('status', ['en_attente', 'en_investigation', 'resolu', 'rejete'])
                  ->default('en_attente');
            
            // Pour que l'admin puisse laisser une note interne
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            // Index pour la performance
            $table->index(['status', 'category']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('reports');
    }
};