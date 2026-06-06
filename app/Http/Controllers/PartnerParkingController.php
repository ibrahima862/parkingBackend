<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use App\Models\Commission;
use App\Models\Parking;
use App\Models\Reservation;
use App\Models\VehiculeType;
use Carbon\Carbon;
use Cloudinary\Api\Upload\UploadApi;
use Illuminate\Http\Request;
use Cloudinary\Configuration\Configuration;
class PartnerParkingController extends Controller
{
    public function index(Request $request)
    {

        $search = $request->query('search');
        $parkings = Parking::query()
            ->with(['plans', 'services'])
            ->where('proprietaire_id', auth()->id())
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

                $disponible = $parking->capacite - $parking->reservations()
                    ->where('parking_status', 'garé')
                    ->count();
                $annulations = Reservation::where('statut', 'annule')
                    ->latest()
                    ->take(3)
                    ->get();
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
                    'note' => $parking->avis_clients()->avg('note') ?? 0,
                    'nbAvis' => $parking->avis_clients()->count(),
                    'distance' => '0.8 km',
                    'annulations' => $annulations

                ];
            });

        return response()->json($parkings);
    }
    public function show($id)
{
    // 1. Récupérer le parking avec absolument toutes les relations nécessaires pour les 2 pages
    $parking = Parking::with([
        'plans', 
        'services', 
        'vehiculeTypes', 
        'horaires',
        'avis_clients.user',
        'user',
        'reservations'
    ])->findOrFail($id);

    // 2. Sécurité : S'assurer que seul le propriétaire y accède
    if ($parking->proprietaire_id !== auth()->id()) {
        return response()->json(['success' => false, 'message' => 'Action non autorisée'], 403);
    }

    // 3. Calculs des statistiques pour le tableau de bord des détails
    $totalAbonnes = $parking->abonnements()
        ->where('statut', 'actif')
        ->count();

    $debutDuMois = Carbon::now()->startOfMonth();
    $finDuMois = Carbon::now()->endOfMonth();

    $revenusMois = $parking->reservations()
        ->where('statut', 'confirme')
        ->whereBetween('created_at', [$debutDuMois, $finDuMois])
        ->sum('montant_total');

    $ca_mensuel = $parking->reservations()
        ->where('statut', 'confirme')
        ->sum('montant_total');

    $signalementsActifs = $parking->avis_clients()
        ->where('note', '<=', 2)
        ->count();

    // 4. Construction d'une réponse unifiée et complète
    $parkingData = [
        'id' => $parking->id,
        'nom' => $parking->nom,
        'description' => $parking->description,
        'pays' => $parking->pays ?? '',
        'departement' => $parking->departement ?? '',
        'quartier' => $parking->quartier,
        'capacite' => $parking->capacite,
        'statut' => $parking->statut,
        'latitude' => $parking->latitude ?? 0,
        'longitude' => $parking->longitude ?? 0,
        'image' => $parking->image ? $parking->image : null,
        'proprietaire' => $parking->user->name ?? 'Anonyme',
        
        // Clés requises par ton interface de détails actuelle (ParkingDetails.tsx)
        'places_occupees' => $parking->places_occupees, 
        'is_full' => $parking->is_full,
        'revenus_mois' => (float) $revenusMois,
        'total_abonnes' => $totalAbonnes,
        'signalements_actifs' => $signalementsActifs,
        'tarif_heure' => (float) ($parking->plans->first()->prix ?? 0), 

        // Relations et autres agrégats requis pour l'édition ou les listes complexes
        'prix_base' => (float) ($parking->plans->first()->prix ?? 0),
        'ca_mensuel' => (float) $ca_mensuel,
        'plans' => $parking->plans,
        'services' => $parking->services,
        'horaires' => $parking->horaires,
        'vehicules' => $parking->vehiculeTypes,
        'avis_clients' => $parking->avis_clients,
        'reservations' => $parking->reservations()->with('user')->get(),
    ];

    // 5. Envoi au Front-end sous la structure standard { success: true, data: ... }
    return response()->json([
        'success' => true,
        'data' => $parkingData
    ], 200);
}
    public function update(Request $request, $id)
    {
        $parking = Parking::where('proprietaire_id', auth()->id())->findOrFail($id);

        // --- 1. PRÉ-TRAITEMENT DES DONNÉES JSON ---
        // On ajoute 'vehicules' au décodage car FormData envoie du texte
        $jsonFields = ['plans', 'services', 'vehicules'];
        foreach ($jsonFields as $field) {
            if ($request->has($field) && is_string($request->$field)) {
                $decoded = json_decode($request->$field, true);
                $request->merge([$field => $decoded]);
            }
        }

        // --- 2. VALIDATION ---
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacite' => 'required|integer',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'plans' => 'nullable|array',
            'services' => 'nullable|array',
            'vehicules' => 'nullable|array',
            'image' => 'nullable',
        ]);

        // --- 3. GESTION CLOUDINARY ---
        if ($request->hasFile('image')) {
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key' => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET')
                ]
            ]);

            $upload = new UploadApi();
            $response = $upload->upload($request->file('image')->getRealPath(), [
                'folder' => 'senovapark/parkings',
            ]);

            $parking->image = $response['secure_url'];
        }

        // On exclut toutes les relations du fill() pour éviter les erreurs SQL
        $parking->fill(collect($validated)->except(['image', 'plans', 'services', 'vehicules'])->toArray());
        $parking->save();

        // --- 5. SYNCHRONISATION DES PLANS ---
        if ($request->has('plans')) {
            $parking->plans()->delete();
            foreach ($request->plans as $plan) {
                $parking->plans()->create($plan);
            }
        }

        // --- 6. SYNCHRONISATION DES SERVICES ---
        if ($request->has('services')) {
            $parking->services()->delete();
            foreach ($request->services as $service) {
                $parking->services()->create($service);
            }
        }

        // --- 7. SYNCHRONISATION DES VÉHICULES (Nouveau) --
        if ($request->has('vehicules')) {
            $typeIds = [];

            foreach ($request->vehicules as $v) {
                // firstOrCreate vérifie si le libellé existe. 
                // S'il n'existe pas, il l'insère en base de données.
                $type = VehiculeType::firstOrCreate(
                    ['libelle' => $v['libelle']], // Condition de recherche
                    ['icon' => $v['icon'] ?? 'car'] // Données à ajouter si création
                );

                $typeIds[] = $type->id;
            }

            // On synchronise les IDs (qu'ils soient nouveaux ou anciens)
            $parking->vehiculeTypes()->sync($typeIds);
        }

        return response()->json([
            'message' => 'Espace mis à jour avec succès',
            'data' => $parking->load(['plans', 'services', 'vehiculeTypes'])
        ]);
    }
    public function getDashboardStats(Request $request)
    {
        $user = $request->user();
        $days = $request->query('days', 30);
        $startDate = now()->subDays((int) $days);
        $parkingIds = $user->parkings()->pluck('id');

        // 1. CHIFFRE D'AFFAIRES BRUT (CA)
        $totalMontantReservation = Reservation::whereIn('parking_id', $parkingIds)
            ->whereIn('statut', ['payé', 'terminé', 'confirme'])
            ->where('date_debut', '>=', $startDate)
            ->sum('montant_total');

        $totalMontantAbonnement = Abonnement::whereIn('parking_id', $parkingIds)
            ->where('statut', 'actif')
            ->sum('prix');

        $ca_brut = $totalMontantAbonnement + $totalMontantReservation;

        // 2. CALCUL DES COMMISSIONS (Le montant que la plateforme prélève)
        $totalCommissions = Commission::where(function ($query) use ($parkingIds) {
            $query->whereHas('reservation', function ($q) use ($parkingIds) {
                $q->whereIn('parking_id', $parkingIds);
            })->orWhereHas('abonnement', function ($q) use ($parkingIds) {
                $q->whereIn('parking_id', $parkingIds);
            });
        })
            ->where('statut', 'valide')
            ->where('created_at', '>=', $startDate)
            ->sum('montant');

        // Revenu Net pour le partenaire
        $revenu_net = $ca_brut - $totalCommissions;

        // 3. STATISTIQUES DE VOLUME
        $reservations_count = Reservation::whereIn('parking_id', $parkingIds)
            ->where('date_debut', '>=', $startDate)
            ->count();

        $nouveaux_clients = Reservation::whereIn('parking_id', $parkingIds)
            ->where('date_debut', '>=', $startDate)
            ->distinct('user_id')
            ->count();

        // 4. HEURES DE POINTE (Optimisé pour éviter 8 requêtes)
        $heures_pointe = [];
        $tranches = [8, 10, 12, 14, 16, 18, 20, 22];
        foreach ($tranches as $heure) {
            $heures_pointe[] = Reservation::whereIn('parking_id', $parkingIds)
                ->whereRaw("HOUR(date_debut) BETWEEN ? AND ?", [$heure, $heure + 1])
                ->where('date_debut', '>=', $startDate)
                ->count();
        }

        // 5. REVENUS NETS DES 6 DERNIERS MOIS (Graphique)
        $revenus_mois = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);

            // CA Brut du mois
            $sumBrut = Reservation::whereIn('parking_id', $parkingIds)
                ->whereIn('statut', ['confirme', 'termine'])
                ->whereMonth('date_debut', $month->month)
                ->whereYear('date_debut', $month->year)
                ->sum('montant_total');

            // Commissions du mois
            $sumCom = Commission::whereHas('reservation', function ($q) use ($parkingIds) {
                $q->whereIn('parking_id', $parkingIds);
            })
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('montant');

            $revenus_mois[] = (int) ($sumBrut - $sumCom);
        }

        // 6. DÉTAIL PAR PARKING (Net de commission)
        $revenus_detail = $user->parkings()->get()->map(function ($p) use ($startDate, $revenu_net) {
            $brut = $p->reservations()
                ->whereIn('statut', ['confirme', 'termine'])
                ->where('date_debut', '>=', $startDate)
                ->sum('montant_total');

            $com = Commission::whereHas('reservation', function ($q) use ($p) {
                $q->where('parking_id', $p->id);
            })
                ->where('created_at', '>=', $startDate)
                ->sum('montant');

            $net = $brut - $com;

            return [
                'label' => $p->nom,
                'value' => (int) $net,
                'pct' => $revenu_net > 0 ? round(($net / $revenu_net) * 100) : 0
            ];
        });

        // 7. TAUX D'OCCUPATION (Logique inchangée)
        $taux_semaine = [];
        for ($i = 0; $i < 7; $i++) {
            $dayCount = Reservation::whereIn('parking_id', $parkingIds)
                ->whereRaw("WEEKDAY(date_debut) = ?", [$i])
                ->where('date_debut', '>=', $startDate)
                ->count();
            $taux_semaine[] = min(100, $dayCount * 10);
        }
        $occupation_moyenne = count($taux_semaine) > 0 ? array_sum($taux_semaine) / count($taux_semaine) : 0;

        return response()->json([
            'ca_total' => (int) $revenu_net,
            'ca_brut' => (int) $ca_brut,
            'reservations' => $reservations_count,
            'occupation' => round($occupation_moyenne),
            'nouveaux_clients' => $nouveaux_clients,
            'heures_pointe' => $heures_pointe,
            'revenus_mois' => $revenus_mois,
            'revenus_detail' => $revenus_detail,
            'taux_semaine' => $taux_semaine,
        ]);
    }

   
}
