<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminPartenaireController extends Controller
{
    public function index()
    {
        // On récupère les users avec le rôle 'partenaire'
        $partners = User::where('role', 'proprietaireparking')
            ->withCount('parkings') // Nombre de parkings possédés
            ->get()
            ->map(function ($partner) {
                // On calcule le CA généré par tous ses parkings
                $caTotal = \DB::table('reservations')
                    ->join('parkings', 'reservations.parking_id', '=', 'parkings.id')
                    ->where('parkings.proprietaire_id', $partner->id)
                    ->where('reservations.statut', 'confirme')
                    ->sum('montant_total');

                return [
                    'id' => $partner->id,
                    'name' => $partner->name,
                    'email' => $partner->email,
                    'telephone' => $partner->telephone,
                    'nb_parkings' => $partner->parkings_count,
                    'ca_genere' => (float)$caTotal,
                    'date_inscription' => $partner->created_at->format('d/m/Y'),
                    'statut' => $partner->is_approved ? 'actif' : 'en_attente',
                ];
            });

        return response()->json($partners);
    }
}