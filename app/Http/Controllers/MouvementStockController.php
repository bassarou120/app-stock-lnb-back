<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MouvementStock;
use App\Models\AffectationArticle;
use App\Models\PieceJointeMouvement;
use App\Models\Stock;
use App\Models\Article;
use App\Models\Parametrage\TypeMouvement;
use App\Models\Parametrage\TypeAffectation;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;



class MouvementStockController extends Controller
{
    // Afficher la liste des mouvements
    public function indexEntreeStock()
    {
        // Récupérer l'ID du type de mouvement "Entrée de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Entrée de Stock')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            $mouvements = MouvementStock::with(['article', 'fournisseur', 'piecesJointes', 'unite_de_mesure'])->where('id_type_mouvement', $type_mouvement->id)->latest()->paginate(1000);

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
            "id_unite_de_mesure" => 'required|exists:unite_de_mesures,id',
            "description" => 'nullable|string|max:255',
            "qte" => 'required|integer',
            "prixUnitaire" => 'required|integer',
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
            "id_unite_de_mesure" => $request->id_unite_de_mesure,
            "description" => $request->description,
            "id_type_mouvement" => $type_mouvement->id,
            "qte" => $request->qte,
            "prixUnitaire" => $request->prixUnitaire,
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
            "id_unite_de_mesure" => 'required|exists:unite_de_mesures,id',
            "description" => 'nullable|string|max:255',
            "qte" => 'required|integer',
            "prixUnitaire" => 'required|integer',
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
            "id_unite_de_mesure" => $request->id_unite_de_mesure,
            "description" => $request->description,
            "qte" => $request->qte,
            "prixUnitaire" => $request->prixUnitaire,
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
            "articles.*.id_unite_de_mesure" => 'required|exists:unite_de_mesures,id',
            "articles.*.description" => 'nullable|string|max:255',
            "articles.*.qte" => 'required|integer|min:1',
            "articles.*.prixUnitaire" => 'required|integer',
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
                    "id_unite_de_mesure" => $article['id_unite_de_mesure'],
                    "id_fournisseur" => $request->id_fournisseur,
                    "numero_borderau" => $request->numero_borderau,
                    "description" => $article['description'],
                    "id_type_mouvement" => $type_mouvement->id,
                    "qte" => $article['qte'],
                    "prixUnitaire" => $article['prixUnitaire'],
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

    // Méthode pour l'impression des mouvements d'entrée
    public function imprimerEntrees()
    {
        $mouvements = MouvementStock::with(['article', 'fournisseur', 'piecesJointes', 'unite_de_mesure'])
                                    ->where('id_type_mouvement', 1)
                                    ->latest()
                                    ->get();


        $pdf = \Pdf::loadView('pdf.mouvements_entrees', compact('mouvements'));

        return $pdf->download('liste_mouvements_entrees.pdf');
    }





    // index sortieStock regroupé par code_mouvement
    public function indexSortieStockGrouped()
    {
        // Récupérer l'ID du type de mouvement "Sortie de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Stock')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            // Récupérer tous les codes_mouvement distincts
            $codesMouvements = MouvementStock::where('id_type_mouvement', $type_mouvement->id)
                ->orderBy('created_at', 'desc')
                ->select('code_mouvement', 'created_at')
                ->distinct()
                ->get()
                ->pluck('code_mouvement');

            $result = [];

            foreach ($codesMouvements as $code) {
                // Récupérer le premier mouvement pour les informations générales
                $firstMouvement = MouvementStock::with(['bureau', 'employe'])
                    ->where('code_mouvement', $code)
                    ->first();

                // Récupérer tous les articles associés à ce code_mouvement avec leurs relations
                $details = MouvementStock::with(['article', 'bureau', 'employe'])
                    ->where('code_mouvement', $code)
                    ->get();

                $totalArticles = $details->count();
                $dateCreation = $firstMouvement->created_at;
                $dateDemande = $firstMouvement->dateDemande;
                $statut = $firstMouvement->statut;

                $result[] = [
                    'code_mouvement' => $code,
                    'personnel' => $firstMouvement->employe ? $firstMouvement->employe->nom . ' ' . $firstMouvement->employe->prenom : 'Non défini',
                    'bureau' => $firstMouvement->bureau ? $firstMouvement->bureau->libelle_bureau : 'Non défini',
                    'dateDemande' => $dateDemande,
                    'dateCreation' => $dateCreation,
                    'statut' => $statut,
                    'totalArticles' => $totalArticles,
                    'details' => $details
                ];
            }

            return new PostResource(true, 'Liste des mouvements groupés', $result);
        }

        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Stock".', []);
    }










    // index sortieStock
    public function indexSortieStock()
    {
        // Récupérer l'ID du type de mouvement "Sortie de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Stock')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            $mouvements = MouvementStock::with(['bureau', 'employe', 'article', 'affectation.bureau', 'affectation.employe' => function ($query) {
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

    public function storeSortieStockMultiple1(Request $request)
{
    // Validation de base
    $validator = Validator::make($request->all(), [
        "articles" => "required|array|min:1",
        "articles.*.code_article" => "required|string|exists:articles,code_article",
        "articles.*.description" => "required|string|max:255",
        "articles.*.qteDemande" => "required|integer|min:1",
        "dateDemande" => "required|date",
        "id_bureau" => "nullable|exists:bureaus,id",
        "id_personnel" => "nullable|exists:employes,id",
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Vérifier les doublons d'articles
    $codeArticles = array_column($request->articles, 'code_article');
    if (count($codeArticles) !== count(array_unique($codeArticles))) {
        return response()->json([
            'error' => "Un même article ne peut pas être ajouté plusieurs fois dans la demande."
        ], 422);
    }

    // Vérifier les quantités disponibles pour tous les articles
    $articlesInsuffisants = [];

    foreach ($request->articles as $article) {
        $articleModel = Article::where('code_article', $article['code_article'])->first();
        $stock = Stock::where('id_Article', $articleModel->id)->latest()->first();

        if (!$stock || $stock->Qte_actuel < $article['qteDemande']) {
            $articlesInsuffisants[] = [
                'code_article' => $article['code_article'],
                'qte_disponible' => $stock ? $stock->Qte_actuel : 0,
                'qte_demandee' => $article['qteDemande'],
            ];
        }
    }

    if (!empty($articlesInsuffisants)) {
        return response()->json([
            'error' => "Quantité insuffisante pour les articles suivants.",
            'details' => $articlesInsuffisants
        ], 400);
    }

    // Type de mouvement
    $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();
    if (!$type_mouvement) {
        return response()->json(['error' => "Le type de mouvement 'Sortie de Stock' n'existe pas."], 404);
    }

    // Génération du code_mouvement
    $code_mouvement = 'SORT-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
    $mouvements = [];

    // Création des mouvements
    foreach ($request->articles as $article) {
        $articleModel = Article::where('code_article', $article['code_article'])->first();

        $mouvement = MouvementStock::create([
            "id_Article" => $articleModel->id,
            "description" => $article['description'],
            "id_type_mouvement" => $type_mouvement->id,
            "qte" => 0,
            "qteDemande" => $article['qteDemande'],
            "dateDemande" => $request->dateDemande,
            "bureau_id" => $request->id_bureau,
            "id_employe" => $request->id_personnel,
            "statut" => 'En attente',
            "code_mouvement" => $code_mouvement,
        ]);

        $mouvements[] = $mouvement;
    }

    return new PostResource(true, 'Les sorties de stock ont été enregistrées avec succès !', $mouvements);
}

public function storeSortieStockMultiple(Request $request)
{
    // Validation de base
    $validator = Validator::make($request->all(), [
        "articles" => "required|array|min:1",
        "articles.*.code_article" => "required|string|exists:articles,code_article",
        "articles.*.description" => "required|string|max:255",
        "articles.*.qteDemande" => "required|integer|min:1",
        "dateDemande" => "required|date",
        "id_bureau" => "nullable|exists:bureaus,id",
        "id_personnel" => "nullable|exists:employes,id",
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Vérifier les doublons d'articles
    $codeArticles = array_column($request->articles, 'code_article');
    if (count($codeArticles) !== count(array_unique($codeArticles))) {
        return response()->json([
            'error' => "Un même article ne peut pas être ajouté plusieurs fois dans la demande."
        ], 422);
    }

    // Séparer les articles selon leur disponibilité
    $articlesDisponibles = [];
    $articlesInsuffisants = [];
    $articlesAEnregistrer = [];

    foreach ($request->articles as $article) {
        $articleModel = Article::where('code_article', $article['code_article'])->first();
        $stock = Stock::where('id_Article', $articleModel->id)->latest()->first();
        $qteDisponible = $stock ? $stock->Qte_actuel : 0;

        if ($qteDisponible >= $article['qteDemande']) {
            // Quantité suffisante
            $articlesDisponibles[] = $article;
            $articlesAEnregistrer[] = $article;
        } else {
            // Quantité insuffisante
            $articlesInsuffisants[] = [
                'code_article' => $article['code_article'],
                'description' => $article['description'],
                'qte_disponible' => $qteDisponible,
                'qte_demandee' => $article['qteDemande'],
            ];
        }
    }

    // Si aucun article n'a de quantité suffisante, retourner une erreur
    if (empty($articlesAEnregistrer)) {
        return response()->json([
            'error' => "Aucun article n'a une quantité suffisante pour être traité.",
            'details' => $articlesInsuffisants
        ], 400);
    }

    // Type de mouvement
    $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();
    if (!$type_mouvement) {
        return response()->json(['error' => "Le type de mouvement 'Sortie de Stock' n'existe pas."], 404);
    }

    // Génération du code_mouvement
    $code_mouvement = 'SORT-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
    $mouvements = [];

    // Création des mouvements pour les articles avec quantité suffisante
    foreach ($articlesAEnregistrer as $article) {
        $articleModel = Article::where('code_article', $article['code_article'])->first();
        $mouvement = MouvementStock::create([
            "id_Article" => $articleModel->id,
            "description" => $article['description'],
            "id_type_mouvement" => $type_mouvement->id,
            "qte" => 0,
            "qteDemande" => $article['qteDemande'],
            "dateDemande" => $request->dateDemande,
            "bureau_id" => $request->id_bureau,
            "id_employe" => $request->id_personnel,
            "statut" => 'En attente',
            "code_mouvement" => $code_mouvement,
        ]);
        $mouvements[] = $mouvement;
    }

    // Préparer la réponse
    $response = [
        'mouvements_crees' => $mouvements,
        'articles_traites' => count($articlesAEnregistrer),
        'total_articles' => count($request->articles)
    ];

    // Ajouter les informations sur les articles insuffisants s'il y en a
    if (!empty($articlesInsuffisants)) {
        $response['articles_insuffisants'] = $articlesInsuffisants;
        $response['nb_articles_insuffisants'] = count($articlesInsuffisants);

        $message = count($articlesAEnregistrer) . ' article(s) sur ' . count($request->articles) .
                  ' ont été enregistrés avec succès. ' . count($articlesInsuffisants) .
                  ' article(s) ont été ignorés pour quantité insuffisante.';
    } else {
        $message = 'Tous les articles ont été enregistrés avec succès !';
    }

    return new PostResource(true, $message, $response);
}

    // public function storeSortieStockMultiple(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         "articles" => "required|array|min:1",
    //         "articles.*.code_article" => "required|string|exists:articles,code_article",
    //         "articles.*.description" => "required|string|max:255",
    //         "articles.*.qteDemande" => "required|integer|min:1",
    //         "dateDemande" => "required|date",
    //         "id_bureau" => "nullable|exists:bureaus,id",
    //         "id_personnel" => "nullable|exists:employes,id",
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();
    //     if (!$type_mouvement) {
    //         return response()->json(['error' => "Le type de mouvement 'Sortie de Stock' n'existe pas."], 404);
    //     }

    //     $code_mouvement = 'SORT-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));

    //     $mouvements = [];

    //     foreach ($request->articles as $article) {
    //         $articleModel = Article::where('code_article', $article['code_article'])->first();

    //         if (!$articleModel) {
    //             return response()->json([
    //                 'error' => "L'article avec le code " . $article['code_article'] . " est introuvable."
    //             ], 404);
    //         }

    //         $stock = Stock::where('id_Article', $articleModel->id)->latest()->first();

    //         if (!$stock || $stock->Qte_actuel < $article['qteDemande']) {
    //             return response()->json([
    //                 'error' => "Quantité insuffisante pour l'article " . $article['code_article']
    //             ], 400);
    //         }

    //         $mouvement = MouvementStock::create([
    //             "id_Article" => $articleModel->id,
    //             "description" => $article['description'],
    //             "id_type_mouvement" => $type_mouvement->id,
    //             "qte" => 0,
    //             "qteDemande" => $article['qteDemande'],
    //             "dateDemande" => $request->dateDemande,
    //             "bureau_id" => $request->id_bureau,
    //             "id_employe" => $request->id_personnel,
    //             "statut" => 'En attente',
    //             "code_mouvement" => $code_mouvement,
    //         ]);

    //         $mouvements[] = $mouvement;
    //     }

    //     return new PostResource(true, 'Les sorties de stock ont été enregistrées avec succès !', $mouvements);
    // }






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
            "id_personnel" => 'nullable|exists:employes,id',
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
            "id_employe" => $request->id_personnel,
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

    public function validerDemandeGroupee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code_mouvement' => 'required|string|exists:mouvement_stocks,code_mouvement',
            'date_mouvement' => 'required|date',
            'statut' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $code = $request->input('code_mouvement');
        $dateMouvement = $request->input('date_mouvement');
        $statut = $request->input('statut');

        $mouvements = MouvementStock::where('code_mouvement', $code)->get();

        foreach ($mouvements as $mouvement) {
            $mouvement->statut = $statut;
            $mouvement->date_mouvement = $dateMouvement;

            if (strtolower($statut) === 'accordé') {
                $qte = $mouvement->qteDemande;
                $article = $mouvement->article;
                $articleCode = $article ? $article->code_article : "inconnu";

                // Vérifier la quantité disponible en stock
                $stock = Stock::where('id_Article', $mouvement->id_Article)->latest()->first();

                if (!$stock || $stock->Qte_actuel < $qte) {
                    return response()->json([
                        'error' => "Quantité insuffisante pour l'article {$articleCode}."
                    ], 400);
                }

                // Mise à jour des quantités
                $mouvement->qte = $qte;
                $stock->Qte_actuel -= $qte;
                $stock->save();

                // Création de l'affectation
                if (!empty($mouvement->id_employe) && !empty($mouvement->bureau_id)) {
                    $type_affectation = TypeAffectation::where('libelle_type_affectation', "Affectation d'Article")->latest()->first();

                    if ($type_affectation) {
                        AffectationArticle::create([
                            'description' => $mouvement->description,
                            'id_article' => $mouvement->id_Article,
                            'id_type_affectation' => $type_affectation->id,
                            'id_bureau' => $mouvement->bureau_id,
                            'id_employe' => $mouvement->id_employe,
                            'id_mouvement' => $mouvement->id,
                        ]);
                    }
                }
            }

            // Sauvegarde du mouvement (dans tous les cas)
            $mouvement->save();
        }

        return response()->json([
            'message' => "Toutes les demandes pour le code {$code} ont été traitées avec succès.",
            'code_mouvement' => $code,
            'nombre_demandes' => $mouvements->count()
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
