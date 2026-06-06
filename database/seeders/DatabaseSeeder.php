<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\VehiculeType;
use App\Models\Reservation;
use App\Models\Parking;
use App\Models\ParkingHoraire;
use App\Models\Paiement;
use App\Models\Commission;
use App\Models\Retrait;
use App\Models\Activity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Nettoyage (Optionnel mais recommandé pour les tests)
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Créer les types de véhicules (Indispensable)
        echo "Création des types de véhicules...\n";
        $typesRefs = [];
        $types = [
            ['libelle' => 'Voiture', 'icon' => 'fa-car'],
            ['libelle' => 'Moto', 'icon' => 'fa-motorcycle'],
            ['libelle' => 'Camion', 'icon' => 'fa-truck'],
        ];
        foreach ($types as $t) {
            $typesRefs[] = VehiculeType::create($t);
        }

        // 2. Créer 500 Utilisateurs avec des rôles variés
        echo "Création de 500 utilisateurs...\n";
        User::factory(100)->create(['role' => 'proprietaireparking']);
        User::factory(390)->create(['role' => 'client']);
        User::factory(10)->create(['role' => 'admin']);

        // Admin de test
        User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@parking.com',
            'role' => 'admin',
            'password' => bcrypt('password')
        ]);

        // 3. Créer des Parkings pour les propriétaires
        echo "Création des parkings...\n";
        $proprios = User::where('role', 'proprietaireparking')->get();
        foreach ($proprios as $proprio) {
            $parkings = Parking::factory(rand(1, 2))->create([
                'proprietaire_id' => $proprio->id
            ]);

            foreach ($parkings as $parking) {
                // Ajouter des services par défaut
                Service::create(['nom' => 'Lavage', 'prix' => 2000, 'parking_id' => $parking->id]);
                Service::create(['nom' => 'Gardiennage 24h', 'prix' => 0, 'parking_id' => $parking->id]);

                // Ajouter des horaires
                $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
                foreach ($jours as $jour) {
                    ParkingHoraire::create([
                        'parking_id' => $parking->id,
                        'jour' => $jour,
                        'heure_ouverture' => '07:00',
                        'heure_fermeture' => '23:00',
                        'est_ferme' => false
                    ]);
                }

                // Associer des types de véhicules acceptés
                $parking->vehiculeTypes()->attach(
                    VehiculeType::inRandomOrder()->take(rand(1, 3))->pluck('id')
                );

                // Créer des Plans d'abonnement pour ce parking
                Plan::create([
                    'nom' => 'Mensuel Classique',
                    'prix' => 30000,
                    'duree_jours' => 30,
                    'parking_id' => $parking->id,
                    'is_active' => true
                ]);
            }
        }

        // 4. Créer des Véhicules pour les clients
        echo "Attribution de véhicules aux clients...\n";
        $clients = User::where('role', 'client')->get();
        foreach ($clients as $client) {
            Vehicule::create([
                'user_id' => $client->id, // Attention: vérifie si c'est user_id ou client_id dans ton Model
                'vehicule_type_id' => VehiculeType::inRandomOrder()->first()->id,
                'plaque_immatriculation' => strtoupper(fake()->bothify('??-####-?')),
                'marque' => fake()->randomElement(['Toyota', 'Peugeot', 'Mercedes']),
                'is_main' => true
            ]);
        }

        // 5. Créer des Réservations (1000)
        echo "Création de 1000 réservations...\n";
        for ($i = 0; $i < 1000; $i++) {
            $parking = Parking::inRandomOrder()->first();
            $client = User::where('role', 'client')->inRandomOrder()->first();

            $res = Reservation::create([
                'user_id' => $client->id,
                'parking_id' => $parking->id,
                'nom_conducteur' => $client->name,
                'prenom_conducteur' => fake()->firstName(),
                'matricule_vehicule' => strtoupper(fake()->bothify('??-###-??')),
                'telephone' => $client->telephone ?? fake()->phoneNumber(),
                'date_debut' => now()->subDays(rand(1, 30)),
                'date_fin' => now()->addDays(rand(1, 5)),
                'montant_total' => rand(2000, 15000),
                'statut' => fake()->randomElement(['confirme', 'termine', 'annule']),
                'parking_status' => fake()->randomElement(['en_attente', 'gare', 'sorti'])
            ]);

            // Si payé, on crée la commission et le paiement
            if ($res->statut !== 'annule') {
                Commission::create([
                    'reservation_id' => $res->id,
                    'taux' => 10,
                    'montant' => $res->montant_total * 0.1,
                    'statut' => 'valide'
                ]);

                Paiement::create([
                    'reservation_id' => $res->id,
                    'montant' => $res->montant_total,
                    'transaction_id' => 'PAY-' . uniqid(),
                    'methode_paiement' => 'Mobile Money',
                    'statut' => 'effectue',
                    'date_paiement' => now()
                ]);
            }
        }

        // 6. Retraits pour les propriétaires (Simulation de gains)
        echo "Simulation des retraits...\n";
        foreach ($proprios->take(50) as $proprio) {
            Retrait::create([
                'user_id' => $proprio->id,
                'montant' => rand(5000, 20000),
                'methode' => 'Wave',
                'numero_compte' => $proprio->telephone,
                'statut' => 'valide',
                'date_paiement' => now()
            ]);
        }

        // 7. Logs d'activité
        echo "Génération des logs...\n";
        Activity::log('System', 'Initialisation complète de la base de données terminée.');

        echo "Base de données polluée avec succès ! 🚀\n";
    }
}