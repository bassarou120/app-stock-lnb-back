<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SortiePatrimoine;
use App\Http\Resources\PostResource;
use App\Models\Vehicule;
use App\Models\Immobilisation;
use App\Models\Exercice;
use Illuminate\Support\Facades\DB;
use App\Models\Parametrage\StatusImmo;

class SortiePatrimoineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 1. Trouver l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();

        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé. Veuillez ouvrir un exercice pour consulter les sorties de patrimoine.'
            ], 404);
        }

        $exerciceId = $exerciceOuvert->id;

        // 2. Filtrer les sorties de patrimoine par l'ID de l'exercice OUVERT
        // On utilise les requêtes de la base de données (Eloquent) pour le filtrage
        $sortiespatrimoines = SortiePatrimoine::where('isdeleted', false)
            ->where('exercice_id', $exerciceId) // <-- C'est ici qu'on ajoute le filtre
            ->latest()
            ->paginate(1000); // paginate est beaucoup plus efficace directement sur la requête

        // 3. Retourner la réponse
        return new PostResource(true, 'Liste des sorties de patrimoine pour l\'exercice ouvert', $sortiespatrimoines);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Trouver l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();

        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé. Impossible d\'enregistrer la sortie.'
            ], 404);
        }

        $exerciceId = $exerciceOuvert->id;

        // 2. Validation des données (pour un seul élément)
        $validatedData = $request->validate([
            'code_immo'        => 'required|string|max:50',
            'designation_immo' => 'required|string|max:50',
            'type_immo'        => 'required|string|max:50',
            'valeur'          => 'required|numeric|min:0',
            'date_sortie'      => 'required|date_format:Y-m-d',
            'observation'      => 'nullable|string',
        ]);

        // 3. Création de l'enregistrement
        try {
            $sortiePatrimoine = SortiePatrimoine::create(array_merge($validatedData, [
                'exercice_id' => $exerciceId, // Liaison à l'exercice ouvert
                'isdeleted'   => false,
            ]));

            // 4. Retourner la réponse
            return new PostResource(true, 'Sortie de patrimoine enregistrée avec succès.', $sortiePatrimoine);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la sortie de patrimoine.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeBatch(Request $request)
    {
        // 1. Trouver l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();

        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé. Veuillez ouvrir un exercice pour enregistrer les sorties.'
            ], 404);
        }

        $exerciceId = $exerciceOuvert->id;

        // 2. Validation des données
        $validatedData = $request->validate([
            'sorties' => 'required|array|min:1',
            'sorties.*.code_immo' => 'required|string|max:50',
            'sorties.*.designation_immo' => 'required|string|max:50',
            'sorties.*.type_immo' => 'required|string|max:50',
            'sorties.*.valeur' => 'required|numeric|min:0',
            'sorties.*.date_sortie' => 'required|date_format:Y-m-d',
            'sorties.*.observation' => 'nullable|string',
        ]);

        // 3. Récupérer l'ID du statut "Sortie de patrimoine"
        $statusSortie = StatusImmo::where('libelle_status_immo', 'Sortie de patrimoine')->first();

        if (!$statusSortie) {
            return response()->json([
                'success' => false,
                'message' => 'Le statut "Sortie de patrimoine" n\'existe pas. Veuillez le créer dans la table status_immo.'
            ], 404);
        }

        // 4. Préparation des données pour insertion
        $sortiesToInsert = [];
        $timestamp = now();

        DB::beginTransaction();
        try {
            foreach ($validatedData['sorties'] as $sortie) {

                // Insertion dans la table SortiePatrimoine
                $sortiesToInsert[] = [
                    'code_immo'        => $sortie['code_immo'],
                    'designation_immo' => $sortie['designation_immo'],
                    'type_immo'        => $sortie['type_immo'],
                    'valeur'           => $sortie['valeur'],
                    'date_sortie'      => $sortie['date_sortie'],
                    'observation'      => $sortie['observation'] ?? null,
                    'exercice_id'      => $exerciceId,
                    'isdeleted'        => false,
                    'created_at'       => $timestamp,
                    'updated_at'       => $timestamp,
                ];

                // === Mise à jour du statut selon le type d'immo ===
                if (strtolower($sortie['type_immo']) === 'vehicule') {
                    Vehicule::where('code', $sortie['code_immo'])
                        ->update(['id_status_immo' => $statusSortie->id]);
                } else {
                    Immobilisation::where('code', $sortie['code_immo'])
                        ->update(['id_status_immo' => $statusSortie->id]);
                }
            }

            // Insertion en masse
            SortiePatrimoine::insert($sortiesToInsert);

            DB::commit();

            return new PostResource(true, count($sortiesToInsert) . ' sorties de patrimoine enregistrées avec succès.', null);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement multiple des sorties.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // 1. Trouver l'enregistrement
        // On utilise findOrFail() pour générer automatiquement une réponse 404 si non trouvé
        $sortiePatrimoine = SortiePatrimoine::find($id);

        // 2. Vérifier si l'enregistrement existe
        if (!$sortiePatrimoine) {
            return response()->json([
                'success' => false,
                'message' => 'Sortie de patrimoine non trouvée.'
            ], 404);
        }

        // 3. (Optionnel mais recommandé) Vérifier si l'enregistrement n'est pas "supprimé"
        // Si vous voulez interdire l'affichage des sorties logiquement supprimées
        if ($sortiePatrimoine->isdeleted) {
            return response()->json([
                'success' => false,
                'message' => 'Cette sortie de patrimoine a été supprimée et ne peut pas être consultée.'
            ], 404);
        }

        // 4. Retourner la réponse
        // Si vous avez des relations (comme la relation avec l'Exercice), vous pouvez la charger ici :
        // $sortiePatrimoine->load('exercice');

        return new PostResource(true, 'Détails de la sortie de patrimoine.', $sortiePatrimoine);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // 1. Trouver l'enregistrement
        $sortiePatrimoine = SortiePatrimoine::find($id);

        // Vérifier si la sortie existe
        if (!$sortiePatrimoine) {
            return response()->json([
                'success' => false,
                'message' => 'Sortie de patrimoine non trouvée.'
            ], 404);
        }

        // Vous pouvez également ajouter ici une vérification pour s'assurer que l'exercice est ouvert si nécessaire,
        // mais la mise à jour des données existantes n'est généralement pas bloquée par l'état de l'exercice.

        // 2. Validation des données
        // Les règles de validation sont similaires au "store" mais adaptées si besoin
        $validatedData = $request->validate([
            'code_immo'        => 'sometimes|required|string|max:50',
            'designation_immo' => 'sometimes|required|string|max:50',
            'type_immo'        => 'sometimes|required|string|max:50',
            'valeur'          => 'sometimes|required|numeric|min:0',
            'date_sortie'      => 'sometimes|required|date_format:Y-m-d',
            'observation'      => 'nullable|string',
            // 'exercice_id' (ne devrait pas être modifiable facilement)
        ]);

        // 3. Mise à jour de l'enregistrement
        try {
            // La méthode fill() met à jour toutes les colonnes présentes dans $validatedData
            $sortiePatrimoine->fill($validatedData);
            $sortiePatrimoine->save(); // Sauvegarde les changements en base de données

            // 4. Retourner la réponse
            return new PostResource(true, 'Sortie de patrimoine mise à jour avec succès.', $sortiePatrimoine);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la sortie de patrimoine.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
/*     public function destroy($id)
    {
        // 1. Trouver l'enregistrement
        $sortiePatrimoine = SortiePatrimoine::find($id);

        // Vérifier si la sortie existe
        if (!$sortiePatrimoine) {
            return response()->json([
                'success' => false,
                'message' => 'Sortie de patrimoine non trouvée.'
            ], 404);
        }

        // 2. Vérifier si elle est déjà supprimée
        if ($sortiePatrimoine->isdeleted) {
            return response()->json([
                'success' => false,
                'message' => 'Cette sortie de patrimoine est déjà marquée comme supprimée.'
            ], 400);
        }

        try {
            // 3. Marquer comme supprimée
            $sortiePatrimoine->isdeleted = true;
            $sortiePatrimoine->save();

            // 4. Récupérer le code immo
            $codeImmo = $sortiePatrimoine->code_immo;

            // 5. Chercher si c’est un véhicule ou un immobilisation
            $vehicule = Vehicule::where('code_vehicule', $codeImmo)->first();
            $immobilisation = Immobilisation::where('code_immo', $codeImmo)->first();

            // 6. Remettre le statut à "En magasin"
            if ($vehicule) {
                $vehicule->statut = 'En magasin';
                $vehicule->save();
            } elseif ($immobilisation) {
                $immobilisation->statut = 'En magasin';
                $immobilisation->save();
            }

            // 7. Retourner la réponse
            return new PostResource(true, 'Sortie de patrimoine supprimée logiquement et statut remis à "En magasin".', null);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression logique.',
                'error' => $e->getMessage()
            ], 500);
        }
    } */

public function destroy($id)
{
    // 1. Trouver l'enregistrement
    $sortiePatrimoine = SortiePatrimoine::find($id);

    if (!$sortiePatrimoine) {
        return response()->json([
            'success' => false,
            'message' => 'Sortie de patrimoine non trouvée.'
        ], 404);
    }

    try {
        $codeImmo = $sortiePatrimoine->code_immo;

        // 🔹 Récupérer l'ID du statut "En magasin"
        $statusEnMagasin = StatusImmo::where('libelle_status_immo', 'En magasin')->first();

        if (!$statusEnMagasin) {
            return response()->json([
                'success' => false,
                'message' => 'Le statut "En magasin" est introuvable.'
            ], 500);
        }

        // 🔹 Vérifier si c’est un véhicule ou une immobilisation
        $vehicule = Vehicule::where('code', $codeImmo)->first();
        $immobilisation = Immobilisation::where('code', $codeImmo)->first();

        if ($vehicule) {
            $vehicule->id_status_immo = $statusEnMagasin->id;
            $vehicule->save();
        } elseif ($immobilisation) {
            $immobilisation->id_status_immo = $statusEnMagasin->id;
            $immobilisation->save();
        }

        // 🔹 Supprimer l'entrée dans SortiePatrimoine
        $sortiePatrimoine->delete();

        return new PostResource(true, 'Sortie de patrimoine annulée, actif remis en magasin et retrait de la table SortiePatrimoine.', null);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l’annulation de la sortie de patrimoine.',
            'error' => $e->getMessage()
        ], 500);
    }
}



}
