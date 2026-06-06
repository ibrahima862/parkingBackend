<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehiculeController extends Controller
{
    /**
     * Liste les véhicules de l'utilisateur connecté
     */
    public function index()
    {
        return Auth::user()->vehicules()
            ->with('type') 
            ->latest()
            ->get();
    }

    /**
     * Enregistre un nouveau véhicule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plaque_immatriculation' => 'required|string|unique:vehicules',
            'marque' => 'nullable|string',
            'modele' => 'nullable|string',
            'vehicule_type_id' => 'required|exists:vehicule_types,id',
            'couleur' => 'nullable|string',
        ]);

        // On lie le véhicule à l'utilisateur connecté
        $vehicule = Auth::user()->vehicules()->create($validated);

        return response()->json($vehicule, 201);
    }

    /**
     * Supprime un véhicule
     */
    public function destroy(Vehicule $vehicule)
    {
        // Vérification de sécurité : l'utilisateur possède-t-il ce véhicule ?
        if ($vehicule->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $vehicule->delete();

        return response()->json(['message' => 'Véhicule supprimé']);
    }
}