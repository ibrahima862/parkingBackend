<?php

namespace Database\Factories;

use App\Models\Parking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parking>
 */
class ParkingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
    return [
        'nom' => $this->faker->company() . ' Parking',
        'pays' => 'Sénégal',
        'departement' => $this->faker->city(),
        'quartier' => $this->faker->streetName(),
        'description' => $this->faker->paragraph(), // Ajouté car requis dans ton store()
        'capacite' => $this->faker->numberBetween(10, 100),
        'latitude' => $this->faker->latitude(),
        'longitude' => $this->faker->longitude(),
        'statut' => 'valide',
        // Crée automatiquement un User si tu n'en passes pas un
        'proprietaire_id' => \App\Models\User::factory(), 
    ];
}
}
