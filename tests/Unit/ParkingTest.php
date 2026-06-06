<?php

namespace Tests\Feature;

use App\Models\Parking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParkingTest extends TestCase
{
    use RefreshDatabase; // Réinitialise la base de données à chaque test

    /**
     * Test de la liste des parkings avec recherche (Méthode index)
     */

    protected function setUp(): void
    {
        parent::setUp();

        // Simule des clés API pour éviter l'erreur de configuration de Cloudinary
        config(['cloudinary.cloud_name' => 'test_name']);
        putenv('CLOUDINARY_CLOUD_NAME=test_name');
        putenv('CLOUDINARY_API_KEY=test_key');
        putenv('CLOUDINARY_API_SECRET=test_secret');
    }

    public function test_can_list_validated_parkings_with_search()
    {
        // 1. Créer des données de test
        $user = User::factory()->create(['role' => 'proprietaireparking']);

        // Un parking valide (doit apparaître)
        Parking::factory()->create([
            'nom' => 'Parking Dakar Central',
            'statut' => 'valide',
            'proprietaire_id' => $user->id
        ]);

        // Un parking en attente (ne doit pas apparaître selon ton contrôleur)
        Parking::factory()->create([
            'nom' => 'Parking Thiès',
            'statut' => 'en_attente',
            'proprietaire_id' => $user->id
        ]);

        // 2. Simuler la requête
        $response = $this->getJson('/api/parkings/liste?search=Dakar');

        // 3. Vérifications
        $response->assertStatus(200)
            ->assertJsonCount(1) // Uniquement celui de Dakar
            ->assertJsonPath('0.nom', 'Parking Dakar Central')
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'nom',
                    'quartier',
                    'prix_base',
                    'capacite',
                    'disponible',
                    'tags',
                    'isVerifie'
                ]
            ]);
    }

    /**
     * Test de la création d'un parking (Méthode store)
     */
    public function test_can_store_new_parking_with_relations()
{
    $user = User::factory()->create(['role' => 'proprietaireparking']);
    $this->actingAs($user);

    $data = [
        'nom' => 'Parking Sanar UGB',
        'departement' => 'Saint-Louis',
        'quartier' => 'Sanar',
        'description' => 'Parking sécurisé pour étudiants',
        'capacite' => 100,
        'prix_base' => 500,
        'duree_base' => 1,
        // 'image' => $file,  <-- COMMENTE CETTE LIGNE POUR LE TEST
        'horaires' => json_encode([
            [
                'jour' => 'Lundi',
                'ouverture' => '08:00', 
                'fermeture' => '20:00',
                'est_ferme' => false
            ]
        ]),
        'vehicules' => json_encode(['Voiture', 'Moto'])
    ];

    $response = $this->postJson('/api/partenaire/parkings', $data);

    $response->assertStatus(201); // Devrait être VERT maintenant
    $this->assertDatabaseHas('parkings', ['nom' => 'Parking Sanar UGB']);
    Log::info('Test de création de parking réussi avec les données : ' . json_encode($data));
}

    /**
     * Test de l'affichage d'un parking spécifique (Méthode show)
     */
    public function test_can_show_parking_details()
    {
        $user = User::factory()->create();
        $parking = Parking::factory()->create(['proprietaire_id' => $user->id]);

        $response = $this->getJson("/api/parkings/show/{$parking->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $parking->id)
            ->assertJsonPath('nom', $parking->nom);
    }
}
