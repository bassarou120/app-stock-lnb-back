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


/**
 * @OA\Info(
 *     title="APP-STOCK-LNB API",
 *     version="1.0.0",
 *     description="Description de votre API"
 * )
 *
 * @OA\Tag(
 *     name="Les Mouvement de stock",
 *     description="Gestion des Mouvement de stock"
 * )
 */


class MouvementStockController extends Controller
{
    // Afficher la liste des mouvements
    public function indexEntreeStock()
    {
        // Récupérer l'ID du type de mouvement "Entrée de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Entrée de Stock')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            $mouvements = MouvementStock::with(['article', 'fournisseur', 'piecesJointes', 'unite_de_mesure'])
            ->where('id_type_mouvement', $type_mouvement->id)
            ->where('isdeleted', false)
            ->latest()
            ->paginate(1000);


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
            "piece_jointe_mouvement" => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Entrée de Stock")->latest()->first();

        // Gestion du stock et calcul du CMP AVANT la création du mouvement
        // On verifie si l'article existe deja en stock ou non
        $stock = Stock::where('id_Article', $request->id_Article)->latest()->first();

        // Initialisation des variables pour le calcul du CMP
        $ancienne_quantite = 0;
        $ancien_cmp = 0;
        $nouveau_cmp = 0;

        // si non, on utilise des valeures à 0
        if ($stock == null) {
            // PREMIÈRE ENTRÉE : Création du stock initial
            $stock = Stock::create([
                'id_Article' => $request->id_Article,
                'Qte_actuel' => 0,
                'cout_moyen_pondere' => 0
            ]);

            // Variables restent à 0 pour la première entrée
            $ancienne_quantite = 0;
            $ancien_cmp = 0;
        } else {
            // RÉAPPROVISIONNEMENT : Récupération des valeurs existantes
            $ancienne_quantite = $stock->Qte_actuel;
            $ancien_cmp = $stock->cout_moyen_pondere ?? 0;
        }

        // Calcul du nouveau CMP
        if ($ancienne_quantite == 0) {
            // Premier stock ou stock épuisé : CMP = prix d'achat actuel
            $nouveau_cmp = $request->prixUnitaire;
        } else {
            // Réapprovisionnement : CMP pondéré
            // CMP = (Valeur stock existant + Valeur nouvelle entrée) / (Quantité existante + Nouvelle quantité)
            $valeur_stock_existant = $ancienne_quantite * $ancien_cmp;
            $valeur_nouvelle_entree = $request->qte * $request->prixUnitaire;
            $quantite_totale = $ancienne_quantite + $request->qte;

            $nouveau_cmp = ($valeur_stock_existant + $valeur_nouvelle_entree) / $quantite_totale;
        }

        // Création du mouvement avec le CMP calculé
        $mouvement = MouvementStock::create([
            "id_Article" => $request->id_Article,
            "id_fournisseur" => $request->id_fournisseur,
            "id_unite_de_mesure" => $request->id_unite_de_mesure,
            "description" => $request->description,
            "id_type_mouvement" => $type_mouvement->id,
            "qte" => $request->qte,
            "prixUnitaire" => $request->prixUnitaire,
            "cout_moyen_pondere" => round($nouveau_cmp, 2),
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

        // Mise à jour du stock avec la nouvelle quantité et le nouveau CMP
        $stock->Qte_actuel += $request->qte;
        $stock->cout_moyen_pondere = round($nouveau_cmp, 2);
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

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Récupérer les anciennes valeurs du mouvement
        $ancienne_qte = $mouvement->qte;
        $ancien_prix_unitaire = $mouvement->prixUnitaire;
        $ancien_cmp = $mouvement->cout_moyen_pondere ?? 0;

        // Récupérer le stock actuel
        $stock = Stock::where('id_Article', $request->id_Article)->latest()->first();
        if (!$stock) {
            return response()->json(['message' => 'Stock introuvable'], 404);
        }

        // ÉTAPE 1: Annuler l'impact de l'ancienne entrée
        $quantite_avant_ancienne_entree = $stock->Qte_actuel - $ancienne_qte;

        // Calculer le CMP avant l'ancienne entrée (reconstitution)
        $cmp_avant_ancienne_entree = 0;
        if ($quantite_avant_ancienne_entree > 0) {
            // Reconstituer le CMP avant l'ancienne entrée
            // Formule inverse : CMP_avant = (Valeur_totale_actuelle - Valeur_ancienne_entrée) / Qte_avant
            $valeur_totale_actuelle = $stock->Qte_actuel * $stock->cout_moyen_pondere;
            $valeur_ancienne_entree = $ancienne_qte * $ancien_prix_unitaire;
            $cmp_avant_ancienne_entree = ($valeur_totale_actuelle - $valeur_ancienne_entree) / $quantite_avant_ancienne_entree;
        }

        // ÉTAPE 2: Calculer le nouveau CMP avec les nouvelles valeurs
        $nouveau_cmp = 0;
        if ($quantite_avant_ancienne_entree == 0) {
            // Si c'était la seule entrée, le nouveau CMP = nouveau prix
            $nouveau_cmp = $request->prixUnitaire;
        } else {
            // Calculer le nouveau CMP avec les nouvelles valeurs
            $valeur_stock_avant = $quantite_avant_ancienne_entree * $cmp_avant_ancienne_entree;
            $valeur_nouvelle_entree = $request->qte * $request->prixUnitaire;
            $quantite_totale_nouvelle = $quantite_avant_ancienne_entree + $request->qte;

            $nouveau_cmp = ($valeur_stock_avant + $valeur_nouvelle_entree) / $quantite_totale_nouvelle;
        }

        // ÉTAPE 3: Mise à jour du mouvement avec le nouveau CMP
        $mouvement->update([
            "id_Article" => $request->id_Article,
            "id_fournisseur" => $request->id_fournisseur,
            "id_unite_de_mesure" => $request->id_unite_de_mesure,
            "description" => $request->description,
            "qte" => $request->qte,
            "prixUnitaire" => $request->prixUnitaire,
            "cout_moyen_pondere" => round($nouveau_cmp, 2),
            "date_mouvement" => $request->date_mouvement,
        ]);

        // ÉTAPE 4: Mise à jour du stock
        $stock->Qte_actuel = $quantite_avant_ancienne_entree + $request->qte;
        $stock->cout_moyen_pondere = round($nouveau_cmp, 2);
        $stock->save();

        // ÉTAPE 5: Recalculer le CMP pour tous les mouvements postérieurs (optionnel mais recommandé)
        $this->recalculerCMPPosterieur($request->id_Article, $mouvement->date_mouvement);

        return new PostResource(true, 'Le mouvement d\'entrée de stock a été mis à jour avec succès !', $mouvement);
    }

    /**
     * Recalcule le CMP pour tous les mouvements postérieurs à une date donnée
     * Cette méthode est importante pour maintenir la cohérence des CMP
     */
    private function recalculerCMPPosterieur($id_article, $date_limite)
    {
        // Récupérer tous les mouvements d'entrée postérieurs à la date limite
        $mouvements_posterieurs = MouvementStock::where('id_Article', $id_article)
            ->where('date_mouvement', '>', $date_limite)
            ->whereHas('typeMouvement', function($query) {
                $query->where('libelle_type_mouvement', 'Entrée de Stock');
            })
            ->orderBy('date_mouvement', 'asc')
            ->get();

        // Récupérer le stock à la date limite
        $stock = Stock::where('id_Article', $id_article)->first();
        $quantite_courante = $stock->Qte_actuel;
        $cmp_courant = $stock->cout_moyen_pondere;

        // Soustraire les quantités des mouvements postérieurs pour avoir l'état à la date limite
        foreach ($mouvements_posterieurs as $mouvement) {
            $quantite_courante -= $mouvement->qte;
        }

        // Recalculer le CMP pour chaque mouvement postérieur
        foreach ($mouvements_posterieurs as $mouvement) {
            if ($quantite_courante == 0) {
                $nouveau_cmp = $mouvement->prixUnitaire;
            } else {
                $valeur_stock_existant = $quantite_courante * $cmp_courant;
                $valeur_nouvelle_entree = $mouvement->qte * $mouvement->prixUnitaire;
                $quantite_totale = $quantite_courante + $mouvement->qte;

                $nouveau_cmp = ($valeur_stock_existant + $valeur_nouvelle_entree) / $quantite_totale;
            }

            // Mettre à jour le mouvement
            $mouvement->cout_moyen_pondere = round($nouveau_cmp, 2);
            $mouvement->save();

            // Mettre à jour les variables pour le prochain mouvement
            $quantite_courante += $mouvement->qte;
            $cmp_courant = $nouveau_cmp;
        }

        // Mettre à jour le stock final
        $stock->cout_moyen_pondere = round($cmp_courant, 2);
        $stock->save();
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
        if (!$stock) {
            return response()->json([
                'success' => false,
                'message' => 'Stock introuvable pour cet article.'
            ], 404);
        }

        // Vérifier si la suppression est possible (quantité suffisante)
        if ($stock->Qte_actuel < $mouvement->qte) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette entrée : quantité en stock insuffisante. Stock actuel: ' . $stock->Qte_actuel . ', Quantité à supprimer: ' . $mouvement->qte
            ], 400);
        }

        // Vérifier s'il y a eu des sorties après cette entrée
        $sorties_posterieures = MouvementStock::where('id_Article', $mouvement->id_Article)
            ->where('date_mouvement', '>', $mouvement->date_mouvement)
            ->whereHas('typeMouvement', function($query) {
                $query->where('libelle_type_mouvement', '!=', 'Entrée de Stock');
            })
            ->sum('qte');

        $quantite_apres_suppression = $stock->Qte_actuel - $mouvement->qte;
        if ($quantite_apres_suppression < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Suppression impossible : cela rendrait le stock négatif.'
            ], 400);
        }

        // CALCUL DU NOUVEAU CMP après suppression
        $nouveau_cmp = 0;
        $nouvelle_quantite = $stock->Qte_actuel - $mouvement->qte;

        if ($nouvelle_quantite == 0) {
            // Si le stock devient vide, CMP = 0
            $nouveau_cmp = 0;
        } else {
            // Reconstituer le CMP sans cette entrée
            // Formule inverse : CMP_sans_entree = (Valeur_totale - Valeur_entree_supprimee) / (Qte_totale - Qte_supprimee)
            $valeur_totale_actuelle = $stock->Qte_actuel * $stock->cout_moyen_pondere;
            $valeur_entree_supprimee = $mouvement->qte * $mouvement->prixUnitaire;

            // Vérifier si cette entrée était la seule
            if ($stock->Qte_actuel == $mouvement->qte) {
                $nouveau_cmp = 0;
            } else {
                $nouveau_cmp = ($valeur_totale_actuelle - $valeur_entree_supprimee) / $nouvelle_quantite;
            }
        }

        // Mise à jour du stock
        $stock->Qte_actuel = $nouvelle_quantite;
        $stock->cout_moyen_pondere = round($nouveau_cmp, 2);
        $stock->save();

        // Recalculer le CMP pour tous les mouvements postérieurs
        $this->recalculerCMPPosterieur($mouvement->id_Article, $mouvement->date_mouvement);

        // Supprimer les pièces jointes associées
        $pieces_jointes = PieceJointeMouvement::where('id_mouvement_stock', $mouvement->id)->get();
        foreach ($pieces_jointes as $piece) {
            // Supprimer le fichier physique
            if (file_exists(public_path($piece->url))) {
                unlink(public_path($piece->url));
            }
            // Supprimer l'enregistrement
            $piece->isdeleted = true;
            $piece->save();
        }

        // Supprimer le mouvement
        $mouvement->isdeleted = true;
        $mouvement->save();

        return new PostResource(true, 'Mouvement supprimé avec succès ! CMP recalculé.', [
            'nouveau_stock' => $nouvelle_quantite,
            'nouveau_cmp' => round($nouveau_cmp, 2)
        ]);
    }

    /**
     * Méthode utilitaire pour vérifier la cohérence du stock avant suppression
     */
    private function verifierCoherenceStock($id_article, $mouvement_a_supprimer)
    {
        // Récupérer tous les mouvements chronologiquement
        $mouvements = MouvementStock::where('id_Article', $id_article)
            ->with('typeMouvement')
            ->orderBy('date_mouvement', 'asc')
            ->get();

        $stock_simule = 0;

        foreach ($mouvements as $mouvement) {
            if ($mouvement->id == $mouvement_a_supprimer->id) {
                continue; // Ignorer le mouvement à supprimer
            }

            if ($mouvement->typeMouvement->libelle_type_mouvement == 'Entrée de Stock') {
                $stock_simule += $mouvement->qte;
            } else {
                $stock_simule -= $mouvement->qte;

                if ($stock_simule < 0) {
                    return [
                        'valide' => false,
                        'message' => 'La suppression de cette entrée rendrait le stock négatif à la date du ' . $mouvement->date_mouvement
                    ];
                }
            }
        }

        return ['valide' => true];
    }



    // Ajout multiple de mouvement de stock entree



    /**
     * @OA\Post(
     *     path="/api/demande-de-sortie",
     *     summary="Créer une demande de sortie",
     *     description="Permet de créer une nouvelle demande de sortie avec la liste des articles demandés.",
     *     tags={"Demande de Sortie"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Les données nécessaires pour créer une demande de sortie",
     *         @OA\JsonContent(
     *             required={"dateDemande", "id_bureau", "id_personnel", "articles"},
     *
     *             @OA\Property(
     *                 property="dateDemande",
     *                 type="string",
     *                 format="date",
     *                 example="2025-05-27",
     *                 description="Date à laquelle la demande de sortie est effectuée"
     *             ),
     *
     *             @OA\Property(
     *                 property="id_bureau",
     *                 type="integer",
     *                 example=3,
     *                 description="Identifiant du bureau effectuant la demande (référence à la table bureaux)"
     *             ),
     *
     *             @OA\Property(
     *                 property="id_personnel",
     *                 type="integer",
     *                 example=5,
     *                 description="Identifiant du personnel responsable de la demande (référence à la table personnels)"
     *             ),
     *
     *             @OA\Property(
     *                 property="articles",
     *                 type="array",
     *                 minItems=1,
     *                 @OA\Items(
     *                     type="object",
     *                     required={"code_article", "qteDemande"},
     *
     *                     @OA\Property(
     *                         property="code_article",
     *                         type="string",
     *                         example="AAA67",
     *                         description="Code unique de l'article concerné (référence à la table articles)"
     *                     ),
     *
     *                     @OA\Property(
     *                         property="description",
     *                         type="string",
     *                         example="Consommable de bureau",
     *                         description="Description complémentaire de l'article demandé"
     *                     ),
     *
     *                     @OA\Property(
     *                         property="qteDemande",
     *                         type="integer",
     *                         example=10,
     *                         description="Quantité d'article demandée pour la sortie (minimum 1)"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Demande de sortie créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Demande de sortie créée avec succès"),
     *             @OA\Property(property="demande", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Requête invalide - Erreur de validation"
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Erreur interne du serveur"
     *     )
     * )
     */

    public function storeMultipleEntreeStock(Request $request)
    {
        // Validation des données communes
        $validator = Validator::make($request->all(), [
            "id_fournisseur" => 'required|exists:fournisseurs,id',
            "numero_borderau" => 'required|string|max:255',
            "date_mouvement" => 'required|date',
            "piece_jointe_mouvement" => 'nullable|array',
            "piece_jointe_mouvement.*" => 'file|mimes:pdf,jpg,jpeg,png',
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
            $resultats_cmp = []; // Pour stocker les résultats de calcul CMP

            // Traitement de chaque article
            foreach ($request->articles as $article) {

                // ÉTAPE 1: Gestion du stock et calcul du CMP AVANT la création du mouvement
                $stock = Stock::where('id_Article', $article['id_Article'])->latest()->first();

                // Initialisation des variables pour le calcul du CMP
                $ancienne_quantite = 0;
                $ancien_cmp = 0;
                $nouveau_cmp = 0;

                if ($stock == null) {
                    // PREMIÈRE ENTRÉE : Création du stock initial
                    $stock = Stock::create([
                        'id_Article' => $article['id_Article'],
                        'Qte_actuel' => 0,
                        'cout_moyen_pondere' => 0
                    ]);

                    // Variables restent à 0 pour la première entrée
                    $ancienne_quantite = 0;
                    $ancien_cmp = 0;
                } else {
                    // RÉAPPROVISIONNEMENT : Récupération des valeurs existantes
                    $ancienne_quantite = $stock->Qte_actuel;
                    $ancien_cmp = $stock->cout_moyen_pondere ?? 0;
                }

                // ÉTAPE 2: Calcul du nouveau CMP
                if ($ancienne_quantite == 0) {
                    // Premier stock ou stock épuisé : CMP = prix d'achat actuel
                    $nouveau_cmp = $article['prixUnitaire'];
                } else {
                    // Réapprovisionnement : CMP pondéré
                    // CMP = (Valeur stock existant + Valeur nouvelle entrée) / (Quantité existante + Nouvelle quantité)
                    $valeur_stock_existant = $ancienne_quantite * $ancien_cmp;
                    $valeur_nouvelle_entree = $article['qte'] * $article['prixUnitaire'];
                    $quantite_totale = $ancienne_quantite + $article['qte'];

                    $nouveau_cmp = ($valeur_stock_existant + $valeur_nouvelle_entree) / $quantite_totale;
                }

                // ÉTAPE 3: Création du mouvement de stock avec le CMP calculé
                $mouvement = MouvementStock::create([
                    "id_Article" => $article['id_Article'],
                    "id_unite_de_mesure" => $article['id_unite_de_mesure'],
                    "id_fournisseur" => $request->id_fournisseur,
                    "numero_borderau" => $request->numero_borderau,
                    "description" => $article['description'],
                    "id_type_mouvement" => $type_mouvement->id,
                    "qte" => $article['qte'],
                    "prixUnitaire" => $article['prixUnitaire'],
                    "cout_moyen_pondere" => round($nouveau_cmp, 2), // Ajout du CMP calculé
                    "date_mouvement" => $request->date_mouvement,
                ]);

                // ÉTAPE 4: Mise à jour du stock avec la nouvelle quantité et le nouveau CMP
                $stock->Qte_actuel += $article['qte'];
                $stock->cout_moyen_pondere = round($nouveau_cmp, 2);
                $stock->save();

                // Stocker les informations pour le retour
                $mouvements[] = $mouvement;
                $resultats_cmp[] = [
                    'id_article' => $article['id_Article'],
                    'ancienne_quantite' => $ancienne_quantite,
                    'ancien_cmp' => round($ancien_cmp, 2),
                    'nouvelle_quantite' => $stock->Qte_actuel,
                    'nouveau_cmp' => round($nouveau_cmp, 2),
                    'qte_ajoutee' => $article['qte'],
                    'prix_unitaire' => $article['prixUnitaire']
                ];
            }

            // ÉTAPE 5: Traitement des pièces jointes si présentes
            if ($request->hasFile('piece_jointe_mouvement')) {
                foreach ($request->file('piece_jointe_mouvement') as $index => $file) {
                    // Générer un nom unique pour le fichier
                    $fileName = time() . '_' . $index . '_' . $file->getClientOriginalName();

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

            return new PostResource(true, 'Les mouvements d\'entrée de stock ont été bien enregistrés avec calcul du CMP !', [
                'mouvements' => $mouvements,
                'calculs_cmp' => $resultats_cmp,
                'resume' => [
                    'nombre_articles' => count($mouvements),
                    'fournisseur_id' => $request->id_fournisseur,
                    'numero_borderau' => $request->numero_borderau,
                    'date_mouvement' => $request->date_mouvement
                ]
            ]);

        } catch (\Exception $e) {
            // Annuler la transaction en cas d'erreur
            DB::rollBack();

            return response()->json([
                'success' => false,
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
                                    ->where('isdeleted', false)
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
                ->where('isdeleted', false)
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
                ->where('isdeleted', false)
                ->latest()
                ->paginate(1000);

            return new PostResource(true, 'Liste des mouvements', $mouvements);
        }

        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Stock".', []);
    }



    /**
     * Imprimer la liste des sorties de stock en PDF
     */
    public function imprimerSortiesStock()
    {
        try {
            // Récupérer l'ID du type de mouvement "Sortie de Stock"
            $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Stock')->first();

            if (!$type_mouvement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Type de mouvement "Sortie de Stock" non trouvé.'
                ], 404);
            }

            // Récupérer tous les mouvements de sortie
            $mouvements = MouvementStock::with([
                'bureau',
                'employe',
                'article',
                'unite_de_mesure',
                'fournisseur',
                'affectation.bureau',
                'affectation.employe' => function ($query) {
                    $query->select('id', 'nom', 'prenom')
                        ->selectRaw("CONCAT(nom, ' ', prenom) as full_name");
                }
            ])
            ->where('id_type_mouvement', 2)
            ->where('isdeleted', false)
            ->latest()
            ->get();

            echo "ici 1";

            // Données pour le PDF
            $data = [
                'titre' => 'LISTE DES SORTIES DE STOCK',
                'mouvements' => $mouvements,
                'date_impression' => now()->format('d/m/Y à H:i')
            ];
            echo "ici2";
            // Générer le PDF
            $pdf = \PDF::loadView('pdf.mouvement_sortie', compact('mouvements'));
            echo "ici3";
            return $pdf->download('liste_mouvements_sorties.pdf');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF.'
            ], 500);
        }
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

        // Type de mouvement
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();
        if (!$type_mouvement) {
            return response()->json(['error' => "Le type de mouvement 'Sortie de Stock' n'existe pas."], 404);
        }

        // Génération du code_mouvement
        $code_mouvement = 'SORT-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
        $mouvements = [];
        $articlesInsuffisants = [];

        // Création des mouvements pour tous les articles demandés
        foreach ($request->articles as $article) {
            $articleModel = Article::where('code_article', $article['code_article'])->first();

            $stock = Stock::where('id_Article', $articleModel->id)->latest()->first();
            $qteDisponible = $stock ? $stock->Qte_actuel : 0;

            // Vérifier s'il y a une quantité insuffisante
            if ($qteDisponible < $article['qteDemande']) {
                $articlesInsuffisants[] = [
                    'code_article' => $article['code_article'],
                    'description' => $article['description'],
                    'qte_disponible' => $qteDisponible,
                    'qte_demandee' => $article['qteDemande'],
                ];
            }

            // Créer le mouvement stock même si quantité insuffisante
            $mouvement = MouvementStock::create([
                "id_Article" => $articleModel->id,
                "description" => $article['description'],
                "id_type_mouvement" => $type_mouvement->id,
                "qte" => 0, // quantité réellement sortie à ajuster plus tard
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
            'articles_traites' => count($request->articles),
            'total_articles' => count($request->articles)
        ];

        if (!empty($articlesInsuffisants)) {
            $response['articles_insuffisants'] = $articlesInsuffisants;
            $response['nb_articles_insuffisants'] = count($articlesInsuffisants);

            $message = 'Tous les articles ont été enregistrés. ' . count($articlesInsuffisants) . ' article(s) ont une quantité insuffisante.';
        } else {
            $message = 'Tous les articles ont été enregistrés avec succès !';
        }

        return new PostResource(true, $message, $response);
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
        $mouvement->isdeleted = true;
        $mouvement->save();

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
