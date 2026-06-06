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
    Schema::create('parkings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('proprietaire_id')->constrained('users')->onDelete('cascade');
        $table->string('nom');
        $table->string('pays')->default('Sénégal'); 
        $table->string('departement')->index();
        $table->string('quartier')->index();
        $table->text('description')->nullable(); 
        $table->decimal('latitude', 10, 7);
        $table->decimal('longitude', 11, 7);
        $table->unsignedInteger('capacite');    
        $table->string('image')->nullable();
        $table->enum('statut', ['en_attente', 'valide', 'rejete'])->default('en_attente');
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parkings');
    }
};
