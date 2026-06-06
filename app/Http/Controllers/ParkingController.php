<?php

namespace App\Http\Controllers;

use App\Models\Parking;
use App\Models\Reservation;
use App\Models\User;
use App\Models\VehiculeType;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AvisClient;

class ParkingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $parkings = Parking::query()
            ->with(['plans', 'services']) 
            ->where('statut', 'valide') 
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('nom', 'like', "%$search%")
                        ->orWhere('quartier', 'like', "%$search%")
                        ->orWhere('departement', 'like', "%$search%");
                });
            })
            ->get()
            ->map(function ($parking) {
                // 1. On récupère le prix le plus bas de la relation 'plans'
                $prixHeure = $parking->plans->min('prix') ?? 0;
                // 2. On transforme les noms des services en 'tags' pour le design React
                $tags = $parking->services->pluck('nom')->toArray();

                // Compte les voitures actuellement garées (statut 'confirme' ou parking_status 'garé')
                $occupes = $parking->reservations()
                    ->where('parking_status', 'gare')
                    ->count();

                $disponible = $parking->capacite - $occupes;
                $note=AvisClient::where('parking_id',$parking->id)->Avg('note');
                $resultat=number_format($note,1,'.',' ');

                return [
                    'id' => $parking->id,
                    'nom' => $parking->nom,
                    'quartier' => $parking->quartier,
                    'prix_base' => (int) $prixHeure,
                    'capacite' => (int) $parking->capacite,
                    'disponible' => (int) $disponible,
                    'statut' => $parking->statut,
                    'image' => $parking->image,
                    'tags' => $tags,
                    'isVerifie' => $parking->statut === 'valide',
                    'longitude' => $parking->longitude,
                    'latitude' => $parking->latitude,
                    'note' => $resultat,
                    'nbAvis' => AvisClient::where('parking_id',$parking->id)->count(),
                    'distance' => '0.8 km',

                ];
            });

        return response()->json($parkings);
    }

    public function store(Request $request)
    {
        // 1. Validation (Ajout des horaires et véhicules)
        $validated = $request->validate([
            'nom' => 'required|string',
            'departement' => 'required|string',
            'quartier' => 'required|string',
            'description' => 'required|string',
            'capacite' => 'required|integer',
            'image' => 'nullable|image|max:2048',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'prix_base' => 'required|numeric',
            'duree_base' => 'required|integer',
            'nom_service' => 'nullable|string',
            'description_service' => 'nullable|string',
            'prix_service' => 'nullable|numeric',
            'horaires' => 'nullable|string',
            'vehicules' => 'nullable|string',
        ]);

        try {
            return DB::transaction(function () use ($request, $validated) {

                // --- GESTION CLOUDINARY ---
                if ($request->hasFile('image')) {
                    Configuration::instance([
                        'cloud' => [
                            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                            'api_key' => env('CLOUDINARY_API_KEY'),
                            'api_secret' => env('CLOUDINARY_API_SECRET')
                        ],
                    ]);

                    $upload = new UploadApi();
                    $response = $upload->upload($request->file('image')->getRealPath(), [
                        'folder' => 'senovapark/parkings',
                    ]);
                    $validated['image'] = $response['secure_url'];
                }

                // --- PRÉPARATION DES DONNÉES DU PARKING ---
                $parkingData = collect($validated)->except([
                    'prix_base',
                    'duree_base',
                    'nom_service',
                    'description_service',
                    'prix_service',
                    'horaires',
                    'vehicules'
                ])->toArray();

                $parkingData['proprietaire_id'] = auth()->id();
                $parkingData['statut'] = 'en_attente';
                $parkingData['pays'] = 'Sénégal';

                // 1. Création du Parking
                $parking = Parking::create($parkingData);

                // 2. Création du Plan
                $parking->plans()->create([
                    'nom' => 'Tarif Standard',
                    'description' => 'Plan créé à l\'inscription',
                    'prix' => $request->prix_base,
                    'duree_jours' => $request->duree_base,
                    'is_active' => true
                ]);

                // 3. Création du Service
                if ($request->filled('nom_service')) {
                    $parking->services()->create([
                        'nom' => $request->nom_service,
                        'description' => $request->description_service,
                        'prix' => $request->prix_service ?? 0,
                    ]);
                }

                // 4. GESTION DES HORAIRES (Nouveau)
                if ($request->filled('horaires')) {
                    $horairesArr = json_decode($request->horaires, true);
                    if (is_array($horairesArr)) {
                        foreach ($horairesArr as $h) {
                            $parking->horaires()->create([
                                'jour' => $h['jour'],
                                'heure_ouverture' => $h['ouverture'],
                                'heure_fermeture' => $h['fermeture'],
                                'est_ferme' => $h['est_ferme'] ?? false,
                            ]);
                        }
                    }
                }

                // 5. GESTION DES VÉHICULES (Auto-création "FirstOrCreate")
                if ($request->filled('vehicules')) {
                    $vehiculesArr = json_decode($request->vehicules, true);
                    if (is_array($vehiculesArr)) {
                        $typeIds = [];
                        foreach ($vehiculesArr as $v) {
                            $type =VehiculeType::firstOrCreate(
                                ['libelle' => $v['libelle'] ?? $v], // Gère format string ou objet
                                ['icon' => 'car']
                            );
                            $typeIds[] = $type->id;
                        }
                        $parking->vehiculeTypes()->sync($typeIds);
                    }
                }

                return response()->json([
                    'message' => 'Parking soumis avec succès !',
                    'data' => $parking->load(['plans', 'services', 'horaires', 'vehiculeTypes'])
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'debug' => ['line' => $e->getLine()]
            ], 500);
        }
    }
    public function show($id)
    {
        $parking = Parking::with(['plans', 'services', 'user', 'vehiculeTypes', 'avis_clients.user'])
            ->findOrFail($id);

        $ca_mensuel = $parking->reservations()
            ->where('statut', 'confirme')
            ->sum('montant_total');
        return response()->json([
            'id' => $parking->id,
            'nom' => $parking->nom,
            'description' => $parking->description,
            'quartier' => $parking->quartier,
            'capacite' => $parking->capacite,
            'prix_base' => $parking->plans->first()->prix ?? 0,
            'latitude' => $parking->latitude ?? 0,
            'longitude' => $parking->longitude ?? 0,
            'image' => $parking->image ? $parking->image : null,
            'proprietaire' => $parking->user->name ?? 'Anonyme',
            'plans' => $parking->plans,
            'services' => $parking->services,
            'statut' => $parking->statut,
            'ca_mensuel' => $ca_mensuel,
            'vehicules' => $parking->vehiculeTypes,
            'avis_clients' => $parking->avis_clients,
        ]);
    }

    public function addParkingToBecomePartner(Request $request)
    {
        $validated = $request->validate([
            'nomParking' => 'required|string|max:255',
            'quartier' => 'required|string|max:255',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'capacite' => 'required|integer|min:1', // Ne pas oublier !
            'rectoCIN' => 'required|image|mimes:png,jpg,jpeg|max:5120',
            'versoCIN' => 'required|image|mimes:png,jpg,jpeg|max:5120',
            'description' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($validated, $request) {

            // --- GESTION CLOUDINARY ---
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key' => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET')
                ],
            ]);

            $upload = new UploadApi();

            // Upload systématique car c'est un futur partenaire
            $rectoUrl = $upload->upload($request->file('rectoCIN')->getRealPath(), [
                'folder' => 'senovapark/cin',
            ])['secure_url'];

            $versoUrl = $upload->upload($request->file('versoCIN')->getRealPath(), [
                'folder' => 'senovapark/cin',
            ])['secure_url'];

            // 1. Mise à jour de l'utilisateur
            $user = $request->user(); // Récupère l'utilisateur connecté
            $user->update([
                'role' => 'proprietaireparking',
                'rectoCIN' => $rectoUrl,
                'versoCIN' => $versoUrl,
                'is_approved' => false
            ]);

            // 2. Création du parking
            $parking = Parking::create([
                'nom' => $validated['nomParking'],
                'quartier' => $validated['quartier'],
                'longitude' => $validated['longitude'],
                'latitude' => $validated['latitude'],
                'description' => $validated['description'], // Correction typo
                'statut' => 'en_attente',
                'capacite' => $validated['capacite'],
                'pays' => 'Sénégal',
                'proprietaire_id' => $user->id,
                'departement' => 'Dakar'
            ]);

            return response()->json([
                'message' => 'Demande de partenariat envoyée avec succès',
                'user' => $user,
                'parking' => $parking
            ], 200);
        });
    }

}
