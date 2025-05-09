<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MouvementStock;
use App\Models\AffectationArticle;
use App\Models\PieceJointeMouvement;
use App\Models\Stock;
use App\Models\Parametrage\TypeMouvement;
use App\Models\Parametrage\TypeAffectation;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;



class MouvementStockController extends Controller
{
    // Afficher la liste des mouvements
    public function indexEntreeStock()
    {
        // Récupérer l'ID du type de mouvement "Entrée de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Entrée de Stock')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            $mouvements = MouvementStock::with(['article', 'fournisseur','piecesJointes'])->where('id_type_mouvement', $type_mouvement->id)->latest()->paginate(1000);

            return new PostResource(true, 'Liste des mouvements', $mouvements);
        }

        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Entrée de Stock".', []);
    }



    // store entrée simple
    public function storeEntreeStock(Request $request)
{
    // Validation
    $validator = Validator::make($request->all(), [
        "id_Article" => 'required|exists:articles,id',
        "id_fournisseur" => 'required|exists:fournisseurs,id',
        "description" => 'nullable|string|max:255',
        "qte" => 'required|integer',
        "date_mouvement" => 'required',
        "piece_jointe_mouvement" => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048', // <= ici
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Entrée de Stock")->latest()->first();

    $mouvement = MouvementStock::create([
        "id_Article" => $request->id_Article,
        "id_fournisseur" => $request->id_fournisseur,
        "description" => $request->description,
        "id_type_mouvement" => $type_mouvement->id,
        "qte" => $request->qte,
        "date_mouvement" => $request->date_mouvement,
    ]);

    // Si une pièce jointe est envoyée
    if ($request->hasFile('piece_jointe_mouvement')) {
        $file = $request->file('piece_jointe_mouvement');
        $fileName = time() . '_' . $file->getClientOriginalName();

        $file->storeAs('piece_jointe_mouvement', $fileName, 'public');

        PieceJointeMouvement::create([
            'url' => 'storage/piece_jointe_mouvement/' . $fileName,
            'id_mouvement_stock' => $mouvement->id
        ]);
    }

    // Gestion du stock
    $stock = Stock::where('id_Article', $request->id_Article)->latest()->first();

    if ($stock == null) {
        $stock = Stock::create([
            'id_Article' => $request->id_Article,
            'Qte_actuel' => 0
        ]);
    }

    $stock->Qte_actuel += $request->qte;
    $stock->save();

    return new PostResource(true, 'Le mouvement d\'entrée de stock a été bien enregistré !', $mouvement);
}



    // update entrée
    public function updateEntreeStock(Request $request, $id)
    {
        // Vérification de l'existence du mouvement
        $mouvement = MouvementStock::find($id);
        if (!$mouvement) {
            return response()->json(['message' => 'Mouvement introuvable'], 404);
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            "id_Article" => 'required|exists:articles,id',
            "id_fournisseur" => 'required|exists:fournisseurs,id',
            "description" => 'string|max:255',
            "qte" => 'required|integer',
            "date_mouvement" => 'required',
        ]);

        // Vérifier si la validation échoue
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Récupérer l'ancien stock avant modification
        $ancien_qte = $mouvement->qte;

        // Mise à jour du mouvement
        $mouvement->update([
            "id_Article" => $request->id_Article,
            "id_fournisseur" => $request->id_fournisseur,
            "description" => $request->description,
            "qte" => $request->qte,
            "date_mouvement" => $request->date_mouvement,
        ]);

        // Mise à jour du stock
        $stock = Stock::where('id_Article', $request->id_Article)->latest()->first();

        if ($stock) {
            $stock->Qte_actuel = $stock->Qte_actuel - $ancien_qte + $request->qte;
            $stock->save();
        } else {
            return response()->json(['message' => 'Stock introuvable'], 404);
        }

        // Retourner la réponse
        return new PostResource(true, 'Le mouvement d\'entrée de stock a été mis à jour avec succès !', $mouvement);
    }

    //delete entrée
    public function deleteEntreeStock($id)
    {
        $mouvement = MouvementStock::find($id);

        if (!$mouvement) {
            return response()->json([
                'success' => false,
                'message' => 'Mouvement introuvable.'
            ], 404);
        }

        // Vérifier si un stock existe pour cet article
        $stock = Stock::where('id_Article', $mouvement->id_Article)->latest()->first();

        if ($stock) {
            // Réduire la quantité du stock
            $stock->Qte_actuel -= $mouvement->qte;

            // Empêcher que la quantité devienne négative
            if ($stock->Qte_actuel < 0) {
                $stock->Qte_actuel = 0;
            }

            $stock->save();
        }

        // Supprimer le mouvement

        $mouvement->delete();

        return new PostResource(true, 'Mouvement supprimé avec succès !', null);
    }


    // Ajout multiple de mouvement de stock entree
    public function storeMultipleEntreeStock(Request $request)
    {
        // Validation des données communes
        $validator = Validator::make($request->all(), [
            "id_fournisseur" => 'required|exists:fournisseurs,id',
            "numero_borderau" => 'required|string|max:255',
            "date_mouvement" => 'required|date',
            "piece_jointe_mouvement" => 'nullable|array',
            "piece_jointe_mouvement.*" => 'file|mimes:pdf,jpg,jpeg,png', // Ajustez selon vos besoins
            "articles" => 'required|array|min:1',
            "articles.*.id_Article" => 'required|exists:articles,id',
            "articles.*.description" => 'nullable|string|max:255',
            "articles.*.qte" => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Récupération du type de mouvement "Entrée de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Entrée de Stock")->latest()->first();

        // Démarrer une transaction pour assurer l'intégrité des données
        DB::beginTransaction();

        try {
            $mouvements = [];

            // Traitement de chaque article
            foreach ($request->articles as $article) {
                // Création du mouvement de stock
                $mouvement = MouvementStock::create([
                    "id_Article" => $article['id_Article'],
                    "id_fournisseur" => $request->id_fournisseur,
                    "numero_borderau" => $request->numero_borderau,
                    "description" => $article['description'],
                    "id_type_mouvement" => $type_mouvement->id,
                    "qte" => $article['qte'],
                    "date_mouvement" => $request->date_mouvement,
                ]);

                // Mise à jour du stock
                $stock = Stock::where('id_Article', $article['id_Article'])->latest()->first();

                if ($stock == null) {
                    $stock = Stock::create([
                        'id_Article' => $article['id_Article'],
                        'Qte_actuel' => 0
                    ]);
                }

                $stock->Qte_actuel = $stock->Qte_actuel + $article['qte'];
                $stock->save();

                $mouvements[] = $mouvement;
            }

            // Traitement des pièces jointes si présentes
            if ($request->hasFile('piece_jointe_mouvement')) {
                foreach ($request->file('piece_jointe_mouvement') as $file) {
                    // Générer un nom unique pour le fichier
                    $fileName = time() . '_' . $file->getClientOriginalName();

                    // Stocker le fichier
                    $file->storeAs('piece_jointe_mouvement', $fileName, 'public');

                    // Créer une entrée pour chaque mouvement
                    foreach ($mouvements as $mouvement) {
                        PieceJointeMouvement::create([
                            'url' => 'storage/piece_jointe_mouvement/' . $fileName,
                            'id_mouvement_stock' => $mouvement->id
                        ]);
                    }
                }
            }

            // Valider la transaction
            DB::commit();

            return new PostResource(true, 'Les mouvements d\'entrée de stock ont été bien enregistrés !', $mouvements);
        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            DB::rollBack();

            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'enregistrement des mouvements',
                'error' => $e->getMessage()
            ], 500);
        }
    }















    // index sortieStock
    public function indexSortieStock()
    {
        // Récupérer l'ID du type de mouvement "Entrée de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Stock')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            $mouvements = MouvementStock::with(['bureau', 'employe', 'article','affectation.bureau', 'affectation.employe' => function ($query) {
                $query->select('id', 'nom', 'prenom')
                    ->selectRaw("CONCAT(nom, ' ', prenom) as full_name");
            }])
                ->where('id_type_mouvement', $type_mouvement->id)
                // ->where('statut', '!=', 'Accordé')
                ->latest()
                ->paginate(1000);

            return new PostResource(true, 'Liste des mouvements', $mouvements);
        }

        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Stock".', []);
    }





    // store sortie Article
    public function storeSortieStock(Request $request)
    {
        // Définir les règles de validation
        $validator = Validator::make($request->all(), [
            "id_Article" => 'required|exists:articles,id',
            "description" => 'required|string|max:255',
            "qteDemande" => 'required|integer|min:1',
            // "date_mouvement" => 'required|date',
            "dateDemande" => 'required|date',
            "id_bureau" => 'nullable|exists:bureaus,id',
            "id_employe" => 'nullable|exists:employes,id',
        ]);

        // Vérifier si la validation échoue
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Récupérer le type de mouvement "Sortie de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();

        if (!$type_mouvement) {
            return response()->json(['error' => "Le type de mouvement 'Sortie de Stock' n'existe pas."], 404);
        }

        // Vérifier la quantité disponible en stock
        $stock = Stock::where('id_Article', $request->id_Article)->latest()->first();

        if (!$stock || $stock->Qte_actuel < $request->qteDemande) {
            return response()->json(['error' => "Quantité insuffisante en stock."], 400);
        }

        // Enregistrer le mouvement de sortie dans la table `MouvementStock`
        $mouvement = MouvementStock::create([
            "id_Article" => $request->id_Article,
            "description" => $request->description,
            "id_type_mouvement" => $type_mouvement->id,
            "qte" => 0,
            "qteDemande" => $request->qteDemande,
            // "date_mouvement" => $request->date_mouvement,
            "dateDemande" => $request->dateDemande,
            "bureau_id" => $request->id_bureau,
            "id_employe" => $request->id_employe,
            "statut" => 'En attente',
        ]);
        return new PostResource(true, 'La sortie de stock a été enregistrée avec succès !', $mouvement);
    }

    public function updateSortieStock(Request $request, $id)
    {
        // Définir les règles de validation
        $validator = Validator::make($request->all(), [
            "id_Article" => 'required|exists:articles,id',
            "description" => 'required|string|max:255',
            "qte" => 0,
            "qteDemande" => 'required|integer|min:1',
            "date_mouvement" => 'required|date',
            "id_bureau" => 'nullable|exists:bureaus,id',
            "id_employe" => 'nullable|exists:employes,id',
        ]);

        // Vérifier si la validation échoue
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Trouver le mouvement existant
        $mouvement = MouvementStock::find($id);
        if (!$mouvement) {
            return response()->json(['error' => 'Mouvement introuvable.'], 404);
        }

        // Vérifier la quantité disponible en stock
        $stock = Stock::where('id_Article', $request->id_Article)->latest()->first();

        if (!$stock || $stock->Qte_actuel < $request->qteDemande) {
            return response()->json(['error' => "Quantité insuffisante en stock."], 400);
        }

        $mouvement->update([
            "id_Article" => $request->id_Article,
            "description" => $request->description,
            "qteDemande" => $request->qte,
            "date_mouvement" => $request->date_mouvement,
            "statut" => $request->statut ?? $mouvement->statut,
        ]);

        // Vérifier s'il existe une affectation liée à ce mouvement
        $affectation = AffectationArticle::where('id_mouvement', $mouvement->id)
            ->latest()
            ->first();

        if ($affectation) {
            if (!$request->filled(['id_bureau', 'id_employe'])) {
                // Si l'affectation existait mais que l'utilisateur ne veut plus affecter l'article, on la supprime
                $affectation->delete();
            } else {
                // Sinon, on met à jour l'affectation
                $affectation->update([
                    'id_bureau' => $request->id_bureau,
                    'id_employe' => $request->id_employe,
                ]);
            }
        } else {
            // Si aucune affectation n'existait mais que l'utilisateur en fournit une, on la crée
            if ($request->filled(['id_bureau', 'id_employe'])) {
                $type_affectation = TypeAffectation::where('libelle_type_affectation', "Affectation d'Article")->latest()->first();
                AffectationArticle::create([
                    'description' => $request->description,
                    'id_article' => $request->id_Article,
                    'id_type_affectation' => $type_affectation->id,
                    'id_bureau' => $request->id_bureau,
                    'id_employe' => $request->id_employe,
                ]);
            }
        }

        return new PostResource(true, 'Sortie de stock mise à jour avec succès !', $mouvement);
    }

    //

    public function updateDemandeStock(Request $request, $id)
    {
        $mouvementStock = MouvementStock::findOrFail($id);

        $date_mouvement = $request->input('date_mouvement');
        $statut = $request->input('statut');
        $mouvementStock->statut = $statut;

        // Si le statut est "Accordé"
        if (strtolower($statut) === 'accordé') {

            // Vérifier si la quantité est fournie
            if (!$request->has('qte')) {
                return response()->json(['error' => 'La quantité (qte) est requise lorsque le statut est "Accordé".'], 422);
            }

            $qte = $request->input('qte');

            // Vérifier la quantité disponible en stock
            $stock = Stock::where('id_Article', $mouvementStock->id_Article)->latest()->first();

            if (!$stock || $stock->Qte_actuel < $qte) {
                return response()->json(['error' => 'Quantité insuffisante en stock.'], 400);
            }

            // Mettre à jour la quantité du mouvement et du stock
            $mouvementStock->qte = $qte;
            $mouvementStock->date_mouvement = $date_mouvement;
            $stock->Qte_actuel -= $qte;
            $stock->save();

            // Créer l'affectation si employé et bureau sont présents
            if (!empty($mouvementStock->id_employe) && !empty($mouvementStock->bureau_id)) {
                $type_affectation = TypeAffectation::where('libelle_type_affectation', "Affectation d'Article")->latest()->first();

                if ($type_affectation) {
                    AffectationArticle::create([
                        'description' => $mouvementStock->description,
                        'id_article' => $mouvementStock->id_Article,
                        'id_type_affectation' => $type_affectation->id,
                        'id_bureau' => $mouvementStock->bureau_id,
                        'id_employe' => $mouvementStock->id_employe,
                        'id_mouvement' => $mouvementStock->id,
                    ]);
                }
            }
        }

        $mouvementStock->save();

        return response()->json([
            'message' => 'Demande mise à jour avec succès.',
            'data' => $mouvementStock
        ]);
    }


    // ... autres méthodes ...

    public function deleteSortieStock($id)
    {
        // Trouver le mouvement
        $mouvement = MouvementStock::find($id);

        if (!$mouvement) {
            return response()->json([
                'success' => false,
                'message' => 'Mouvement introuvable.'
            ], 404);
        }
        $mouvement->delete();

        return new PostResource(true, 'Sortie de stock supprimée avec succès !', null);
    }




    // get qte disponible
    public function getQuantiteDisponible($idArticle)
    {
        $stock = Stock::where('id_Article', $idArticle)->first();
        $quantite = $stock ? $stock->Qte_actuel : 0;

        return new PostResource(true, 'Quantité trouvée !', $quantite);
    }
}
