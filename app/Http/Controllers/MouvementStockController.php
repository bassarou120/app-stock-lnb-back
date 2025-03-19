<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MouvementStock;
use App\Models\PieceJointeMouvement;
use App\Models\Stock;
use App\Models\Parametrage\TypeMouvement;
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
            $mouvements = MouvementStock::with(['article', 'fournisseur'])->where('id_type_mouvement', $type_mouvement->id)->latest()->paginate(1000);

            return new PostResource(true, 'Liste des mouvements', $mouvements);
        }

        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Entrée de Stock".', []);
    }


    public function storeEntreeStock(Request $request)
    {

        //define validation rules
        $validator = Validator::make($request->all(), [
            "id_Article" => 'required|exists:articles,id',
            "id_fournisseur" => 'required|exists:fournisseurs,id',
            "description" => 'required|string|max:255',
            "qte" => 'required|integer',
            "date_mouvement" => 'required',
            // "id_type_mouvement" => 'required|exists:type_mouvements,id',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Entrée de Stock")->latest()->first();

        $b = MouvementStock::create([
            "id_Article" => $request->id_Article,
            "id_fournisseur" => $request->id_fournisseur,
            "description" => $request->description,
            "id_type_mouvement" => $type_mouvement->id,
            "qte" => $request->qte,
            "date_mouvement" => $request->date_mouvement,
        ]);


        $stock = Stock::where('id_Article', $request->id_Article)->latest()->first();

        if ($stock == null) {
            $stock = Stock::create([
                'id_Article' => $request->id_Article,
                'Qte_actuel' => 0
            ]);
        }

        $stock->Qte_actuel = $stock->Qte_actuel + $request->qte;
        $stock->save();

        //return response
        return new PostResource(true, 'le mouvement d\'entrer de stock a été bien enrégistré !', $b);
    }

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
            "description" => 'required|string|max:255',
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
        "piece_jointe_mouvement.*" => 'file|mimes:pdf,jpg,jpeg,png|max:2048', // Ajustez selon vos besoins
        "articles" => 'required|array|min:1',
        "articles.*.id_Article" => 'required|exists:articles,id',
        "articles.*.description" => 'required|string|max:255',
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
}
