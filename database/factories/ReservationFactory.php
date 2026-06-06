<?php

namespace Database\Factories;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
    return [
        // Si aucun client n'existe, on en crée un à la volée
        'user_id' => \App\Models\User::where('role', 'client')->inRandomOrder()->first()?->id 
                     ?? \App\Models\User::factory(),
        
        // Si aucun parking n'existe, on en crée un
        'parking_id' => \App\Models\Parking::inRandomOrder()->first()?->id 
                        ?? \App\Models\Parking::factory(),

        'nom_conducteur' => fake()->lastName(),
        'prenom_conducteur' => fake()->firstName(),
        'matricule_vehicule' => strtoupper(fake()->bothify('??-###-??')),
        'telephone' => fake()->phoneNumber(),
        'date_debut' => now(),
        'date_fin' => now()->addDays(2),
        'montant_total' => fake()->numberBetween(2000, 10000),
        'statut' => 'confirme',
        'parking_status' => 'en_attente',
    ];
}
}
