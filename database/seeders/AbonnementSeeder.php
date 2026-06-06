<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Abonnement;
use App\Models\User;
use App\Models\Parking;
use Carbon\Carbon;

class AbonnementSeeder extends Seeder
{
    public function run(): void
    {
        // On récupère quelques IDs existants pour les lier
        $userIds = User::pluck('id')->toArray();
        $parkingIds = Parking::pluck('id')->toArray();

        if (empty($userIds) || empty($parkingIds)) {
            $this->command->warn("Veuillez peupler les tables users et parkings avant de lancer ce seeder.");
            return;
        }

        $abonnements = [
           
            [
                'user_id'    => $userIds[0],
                'parking_id' => $parkingIds[0],
                'type'       => 'Mensuel',
                'prix'       => 15000,
                'date_debut' => Carbon::now()->startOfMonth(),
                'date_fin'   => Carbon::now()->endOfMonth(),
                'statut'     => 'actif',
            ],
            // 2. Un abonnement EXPIRÉ
            [
                'user_id'    => $userIds[0],
                'parking_id' => $parkingIds[1] ?? $parkingIds[0],
                'type'       => 'Hebdomadaire',
                'prix'       => 5000,
                'date_debut' => Carbon::now()->subMonths(2),
                'date_fin'   => Carbon::now()->subMonths(2)->addDays(7),
                'statut'     => 'expiré',
            ],
            // 3. Un abonnement EN ATTENTE (Paiement non fini)
            [
                'user_id'    => $userIds[1] ?? $userIds[0],
                'parking_id' => $parkingIds[0],
                'type'       => 'Annuel',
                'prix'       => 150000,
                'date_debut' => Carbon::now(),
                'date_fin'   => Carbon::now()->addYear(),
                'statut'     => 'actif',
            ],
        ];

        foreach ($abonnements as $data) {
            Abonnement::create($data);
        }

        $this->command->info("Table Abonnements peuplée avec succès !");
    }
}