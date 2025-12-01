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
use App\Models\Exercice;
use App\Models\Parametrage\Bureau;
use App\Models\Parametrage\Employe;

use PDF;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

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
    public function indexEntreeStock(Request $request)
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

            LogJournalisation::create([
                'action'     => 'Consultation des mouvements "Entrée de Stock"',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => auth()->id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Liste des mouvements', $mouvements);
        }

        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Entrée de Stock".', []);
    }



    // store entrée simple

/*     public function storeEntreeStock(Request $request)
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
    } */

    public function store(Request $request)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            "id_type_mouvement" => 'required|exists:type_mouvements,id',
            "id_article" => 'required|exists:articles,id',
            "employe_id" => 'nullable|exists:employes,id',
            "vehicule_id" => 'nullable|exists:vehicules,id',
            "bureau_id" => 'nullable|exists:bureaux,id',
            "qte_mouvement" => 'required|numeric|min:1',
            "date_mouvement" => 'required|date',
            "observation" => 'nullable|string',
            "demandevalidesigne" => 'nullable',
            // Valider la date
            'date_mouvement' => 'required|date|before_or_equal:' . now()->format('Y-m-d'),
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return new PostResource(false, 'Échec de la validation', $validator->errors());
        }

        try {
            DB::beginTransaction();

            $id_type_mouvement = $request->id_type_mouvement;
            $id_article = $request->id_article;
            $qte_mouvement = $request->qte_mouvement;
            $date_mouvement = $request->date_mouvement;

            // 1. Récupérer l'article et le stock associé à l'exercice en cours
            $article = Article::find($id_article);
            $exercice = Exercice::where('etat_exercice', true)->first();
            $stock = Stock::where('id_article', $id_article)
                            ->where('id_exercice', optional($exercice)->id)
                            ->first();

            if (!$article || !$stock) {
                DB::rollBack();
                return new PostResource(false, 'Article ou Stock non trouvé pour l\'exercice en cours.', null);
            }

            // 2. Vérifier si l'article est actif
            if (!$article->isactif) {
                DB::rollBack();
                return new PostResource(false, 'L\'article n\'est pas actif et ne peut pas faire l\'objet d\'un mouvement.', null);
            }

            // 3. Récupérer le type de mouvement
            $typeMouvement = TypeMouvement::find($id_type_mouvement);
            if (!$typeMouvement) {
                DB::rollBack();
                return new PostResource(false, 'Type de mouvement non trouvé.', null);
            }

            // 4. Traitement des entrées (Entrée de Stock)
            if ($typeMouvement->libelle_type_mouvement === 'Entrée de Stock') {

                // Mettre à jour le stock
                $stock->stock_fin_exercice += $qte_mouvement;

            }
            // 5. Traitement des sorties (Sortie de Stock)
            elseif ($typeMouvement->libelle_type_mouvement === 'Sortie de Stock') {

                // Vérifier la disponibilité du stock
                if ($stock->stock_fin_exercice < $qte_mouvement) {
                    DB::rollBack();
                    return new PostResource(false, 'Stock insuffisant pour cette sortie.', [
                        'stock_disponible' => $stock->stock_fin_exercice,
                        'quantite_demandee' => $qte_mouvement
                    ]);
                }

                // Mettre à jour le stock
                $stock->stock_fin_exercice -= $qte_mouvement;

            } else {
                DB::rollBack();
                return new PostResource(false, 'Type de mouvement non géré (doit être "Entrée de Stock" ou "Sortie de Stock").', null);
            }


            // --- LOGIQUE AJOUTÉE POUR LE CODE DE MOUVEMENT (MVT-00001-25) ---

            // 6. Récupérer le dernier ID du mouvement pour obtenir le numéro d'ordre
            $lastMouvement = MouvementStock::latest('id')->first();
            $nextId = $lastMouvement ? $lastMouvement->id + 1 : 1;

            // Formater le numéro d'ordre sur 5 chiffres (ex: 1 -> 00001)
            $numeroOrdre = str_pad($nextId, 5, '0', STR_PAD_LEFT);

            // Définir l'année de l'exercice (ou utiliser '25' comme dans l'exemple si c'est une constante)
            $anneeExercice = $exercice ? substr($exercice->libelle_exercice, -2) : '25'; // Ex: '2025' -> '25'

            // Construire le code de mouvement final
            $codeMouvement = "MVT-{$numeroOrdre}-{$anneeExercice}";

            // -----------------------------------------------------------------


            // 7. Créer l'enregistrement du mouvement
            $mouvementStock = MouvementStock::create([
                "code_mouvement" => $codeMouvement, // Utilisation du nouveau code généré
                "id_type_mouvement" => $id_type_mouvement,
                "id_article" => $id_article,
                "employe_id" => $request->employe_id,
                "vehicule_id" => $request->vehicule_id,
                "bureau_id" => $request->bureau_id,
                "qte_mouvement" => $qte_mouvement,
                "date_mouvement" => $date_mouvement,
                "observation" => $request->observation,
                "demandevalidesigne" => $request->demandevalidesigne,
                "isdeleted" => false,
                "id_exercice" => optional($exercice)->id,
                "user_id" => Auth::id(), // Enregistrement de l'utilisateur
            ]);

            // 8. Enregistrer le fichier si existant (Pièce Jointe)
            if ($request->hasFile('demandevalidesigne')) {
                $file = $request->file('demandevalidesigne');
                $extension = $file->getClientOriginalExtension();
                // Utiliser le code mouvement comme nom de fichier pour le groupe
                $fileName = Str::slug($codeMouvement) . '.' . $extension;
                $path = $file->storeAs('public/mouvements_stock_files', $fileName); // 'storage/mouvements_stock_files/MVT-00001-25.pdf'

                // Mettre à jour le mouvement avec le chemin du fichier (version nettoyée)
                $mouvementStock->demandevalidesigne = str_replace('public/', '', $path); // stocke 'mouvements_stock_files/MVT-00001-25.pdf'
                $mouvementStock->save();
            }

            // 9. Sauvegarder la mise à jour du stock
            $stock->save();

            LogJournalisation::create([
                'action'     => 'Création du mouvement de stock ' . $codeMouvement . 
                ' (' . $typeMouvement->libelle_type_mouvement . ', Article: ' . $article->libelle . ', Qté: ' . $qte_mouvement . ')',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            // 10. Commit de la transaction
            DB::commit();

            return new PostResource(true, 'Mouvement de stock créé avec succès.', $mouvementStock);

        } catch (\Exception $e) {
            DB::rollBack();
            LogJournalisation::create([
                'action'     => 'Erreur création mouvement de stock : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
            // Log de l'erreur
            Log::error('Erreur lors de la création du mouvement de stock: ' . $e->getMessage(), ['exception' => $e]);
            return new PostResource(false, 'Une erreur est survenue lors de la création du mouvement de stock.', null);
        }
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

        LogJournalisation::create([
            'action' => "Mise à jour du mouvement de stock ID: {$mouvement->id} réussie",
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id' => Auth::id(),
            'date_action' => now(),
        ]);

        return new PostResource(true, 'Le mouvement d\'entrée de stock a été mis à jour avec succès !', $mouvement);
    }

    /**
     * Recalcule le CMP pour tous les mouvements postérieurs à une date donnée
     * Cette méthode est importante pour maintenir la cohérence des CMP
     */
    private function recalculerCMPPosterieur($id_article, $date_limite)
    {

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
    public function deleteEntreeStock($id, Request $request)
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

        LogJournalisation::create([
            'action' => "Suppression du mouvement de stock ID: {$mouvement->id} réussie",
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id' => Auth::id(),
            'date_action' => now(),
        ]);
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
     * path="/api/demande-de-sortie",
     * summary="Créer une demande de fourniture",
     * description="Permet de créer une nouvelle demande de sortie avec la liste des articles demandés.",
     * tags={"Demande de fourniture"},
     *
     * @OA\RequestBody(
     * required=true,
     * description="Les données nécessaires pour créer une demande de fournitures",
     * @OA\JsonContent(
     * required={"dateDemande", "articles"},
     *
     * @OA\Property(
     * property="dateDemande",
     * type="string",
     * format="date",
     * example="2025-05-27",
     * description="Date à laquelle la demande de fourniture est effectuée"
     * ),
     *
     * @OA\Property(
     * property="id_bureau",
     * type="integer",
     * example=3,
     * description="Identifiant du bureau effectuant la demande (référence à la table bureaux)"
     * ),
     *
     * @OA\Property(
     * property="email_personnel",
     * type="string",
     * format="email",
     * example="employe@example.com",
     * description="Email du personnel responsable de la demande (référence à la table employes)"
     * ),
     *
     * @OA\Property(
     * property="articles",
     * type="array",
     * minItems=1,
     * @OA\Items(
     * type="object",
     * required={"code_article", "qteDemande"},
     *
     * @OA\Property(
     * property="code_article",
     * type="string",
     * example="ART-20240526-1",
     * description="Code unique de l'article concerné (référence à la table articles)"
     * ),
     *
     * @OA\Property(
     * property="description",
     * type="string",
     * example="Consommable de bureau",
     * description="Description complémentaire de l'article demandé"
     * ),
     *
     * @OA\Property(
     * property="qteDemande",
     * type="integer",
     * example=10,
     * description="Quantité d'article demandée pour la sortie (minimum 1)"
     * )
     * )
     * )
     * )
     * ),
     *
     * @OA\Response(
     * response=201,
     * description="Demande de fourniture créée avec succès",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="message", type="string", example="Tous les articles ont été enregistrés avec succès !"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     *
     * @OA\Response(
     * response=422,
     * description="Requête invalide - Erreur de validation"
     * ),
     *
     * @OA\Response(
     * response=404,
     * description="Ressource non trouvée"
     * )
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

                $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();
                // ÉTAPE 1: Gestion du stock et calcul du CMP AVANT la création du mouvement
                $stock = Stock::where('id_Article', $article['id_Article'])
                            ->where('id_exercice', $exerciceOuvert->id)
                            ->latest()
                            ->first();

                // Initialisation des variables pour le calcul du CMP
                $ancienne_quantite = 0;
                $ancien_cmp = 0;
                $nouveau_cmp = 0;

                if ($stock == null) {
                    // PREMIÈRE ENTRÉE : Création du stock initial
                    $stock = Stock::create([
                        'id_Article' => $article['id_Article'],
                        'Qte_actuel' => 0,
                        'cout_moyen_pondere' => 0,
                        'id_exercice' => $exerciceOuvert->id
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
                    "id_exercice" => $exerciceOuvert->id,
                ]);

                // ÉTAPE 4: Mise à jour du stock avec la nouvelle quantité et le nouveau CMP
                $stock->Qte_actuel += $article['qte'];
                $stock->cout_moyen_pondere = round($nouveau_cmp, 2);
                $stock->save();

                LogJournalisation::create([
                    'action' => "Création du mouvement de stock ID: {$mouvement->id} pour l'article {$article['id_Article']}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id' => Auth::id(),
                    'date_action' => now(),
                ]);

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
            LogJournalisation::create([
                'action' => 'Erreur création multiple mouvements stock: ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id' => Auth::id(),
                'date_action' => now(),
            ]);

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

            LogJournalisation::create([
                'action' => 'Impression de la liste des entrées de stock (PDF)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id' => Auth::id(),
                'date_action' => now(),
            ]);

        $pdf = \Pdf::loadView('pdf.mouvements_entrees', compact('mouvements'));

        return $pdf->download('liste_mouvements_entrees.pdf');
    }

    // index sortieStock regroupé par code_mouvement
/*     public function indexSortieStockGrouped()
    {
        // Récupérer l'ID du type de mouvement "Sortie de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Stock')->first();

        // Si le type de mouvement existe, récupérer les mouvements correspondants
        if ($type_mouvement) {
            // Récupérer tous les codes_mouvement distincts

        $codesMouvements = MouvementStock::where('id_type_mouvement', $type_mouvement->id)
            ->where('isdeleted', false)
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
    } */

    public function indexSortieStockGrouped(Request $request)
    {
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', 'Sortie de Stock')->first();

        if (!$type_mouvement) {
            return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Stock".', []);
        }

        // Récupérer les données agrégées pour chaque groupe.
        $groupedMouvements = MouvementStock::select(
            'code_mouvement',
            DB::raw('COUNT(*) as totalArticles'),
            DB::raw('MAX(created_at) as created_at'),
            DB::raw('MAX("date_mouvement") as date_mouvement'),
            DB::raw('MAX(statut) as statut'),
            DB::raw('MAX(id_employe) as id_employe'),
            DB::raw('MAX(bureau_id) as bureau_id'),
            DB::raw('MAX(demandevalidesigne) as demandevalidesigne'),
            DB::raw('MAX(CASE WHEN demandevalidesigne IS NOT NULL THEN 1 ELSE 0 END) as has_file')
        )
            ->where('id_type_mouvement', $type_mouvement->id)
            ->where('isdeleted', false)
            ->groupBy('code_mouvement')
            ->orderBy('created_at', 'desc')
            ->get();

        // Mapper les résultats pour inclure les détails et formater la réponse.
        $formattedResult = $groupedMouvements->map(function ($group) {
            // Charger les relations `employe` et `bureau` manuellement à partir des IDs agrégés.
            $employe = Employe::find($group->id_employe);
            $bureau = Bureau::find($group->bureau_id);

            // Récupérer les détails complets pour ce code_mouvement.
            $details = MouvementStock::with(['article', 'bureau', 'employe'])
                ->where('code_mouvement', $group->code_mouvement)
                ->get();

            return [
                'code_mouvement' => $group->code_mouvement,
                'personnel' => $employe ? $employe->nom . ' ' . $employe->prenom : 'Non défini',
                'bureau' => $bureau ? $bureau->libelle_bureau : 'Non défini',
                'dateDemande' => $group->date_mouvement,
                'dateCreation' => $group->created_at,
                'statut' => $group->statut,
                'totalArticles' => $details->count(), // Calcule le total à partir du nombre de détails récupérés.
                'has_file' => (bool) $group->has_file,
                'details' => $details,
                'file_path' => $group->demandevalidesigne
            ];
        });

        // 📝 JOURNALISATION : Enregistrement de l'action de consultation
        LogJournalisation::create([
            'action'     => 'Consultation de la liste des sorties de stock groupées',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);

        return new PostResource(true, 'Liste des mouvements groupés', $formattedResult);
    }


    // index sortieStock
    public function indexSortieStock(Request $request)
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
            ->where('isdeleted', false)
            ->latest()
            ->paginate(1000);

            // 📝 JOURNALISATION : Consultation de la liste des sorties de stock
            LogJournalisation::create([
                'action'     => 'Consultation de la liste des sorties de stock (liste simple)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Liste des mouvements', $mouvements);
        }

        // Si le type de mouvement n'existe pas, retourner une réponse vide ou un message d'erreur
        return new PostResource(false, 'Aucun mouvement trouvé pour "Sortie de Stock".', []);
    }



    /**
     * Imprimer la liste des sorties de stock en PDF
     */
    public function imprimerSortiesStock(Request $request)
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
            // 📝 JOURNALISATION : Enregistrement de l'action d'impression
            LogJournalisation::create([
                'action'     => 'Impression de la liste des sorties de stock (PDF)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            // Données pour le PDF
            $data = [
                'titre' => 'LISTE DES SORTIES DE STOCK',
                'mouvements' => $mouvements,
                'date_impression' => now()->format('d/m/Y à H:i')
            ];

            // Générer le PDF
            $pdf = \PDF::loadView('pdf.mouvement_sortie', compact('mouvements'));

            return $pdf->download('liste_mouvements_sorties.pdf');

        } catch (\Exception $e) {

            LogJournalisation::create([
                'action'     => 'Erreur: Échec de l\'impression des sorties de stock',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(), // ID de l'utilisateur qui a tenté l'impression
                'date_action'=> now(),
            ]);

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
            "articles.*.description" => "nullable|string|max:255",
            "articles.*.qteDemande" => "required|integer|min:1",
            "dateDemande" => "required|date",
            "id_bureau" => "nullable|exists:bureaus,id",
            // "id_personnel" => "nullable|exists:employes,id",
            "email_personnel" => "nullable|email|exists:employes,email",
        ]);

        if ($validator->fails()) {
            LogJournalisation::create([
                'action'     => 'Échec: Tentative de création de demande de Sortie de Stock Multiple (Validation échouée)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(), // Utilisateur connecté qui a fait la tentative
                'date_action'=> now(),
            ]);
            return response()->json($validator->errors(), 422);
        }

        // Après la validation, on récupère l'employé via son email pour obtenir son ID.
        $employeId = null;
        if ($request->filled('email_personnel')) {
            $personnel = Employe::where('email', $request->email_personnel)->first();
            // La règle 'exists' garantit que nous trouverons un personnel si l'email est fourni.
            $employeId = $personnel->id;
        }

        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();

        if (!$exerciceOuvert) {
            LogJournalisation::create([
                'action'     => 'Échec: Création de demande de Sortie de Stock Multiple (Aucun exercice ouvert)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
            return response()->json(['error' => 'Aucun exercice ouvert trouvé.'], 422);
        }

        // Vérifier les doublons d'articles
        $codeArticles = array_column($request->articles, 'code_article');
        if (count($codeArticles) !== count(array_unique($codeArticles))) {
            LogJournalisation::create([
                'action'     => 'Échec: Création de demande de Sortie de Stock Multiple (Articles en double)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
            return response()->json([
                'error' => "Un même article ne peut pas être ajouté plusieurs fois dans la demande."
            ], 422);
        }

        // Type de mouvement
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();
        if (!$type_mouvement) {
            LogJournalisation::create([
                'action'     => 'Échec: Création de demande de Sortie de Stock Multiple (Type de mouvement manquant)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
            return response()->json(['error' => "Le type de mouvement 'Sortie de Stock' n'existe pas."], 404);
        }

        // Génération du code_mouvement
        $code_mouvement = 'SORT-' . now()->format('Ymd-His') . '-' . strtoupper(Str::random(4));
        $mouvements = [];
        $articlesInsuffisants = [];

        // Création des mouvements pour tous les articles demandés
        foreach ($request->articles as $article) {
            $articleModel = Article::where('code_article', $article['code_article'])->first();

            $stock = Stock::where('id_Article', $articleModel->id)
              ->where('id_exercice', $exerciceOuvert->id)
              ->latest()
              ->first();

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
                "id_employe" => $employeId,
                "statut" => 'En attente',
                "code_mouvement" => $code_mouvement,
                'id_exercice' => $exerciceOuvert->id,
                "ref_m_request" => null,
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
            LogJournalisation::create([
                'action'     => 'Échec: Tentative de création de demande de Sortie de Stock (Validation échouée)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
            return response()->json($validator->errors(), 422);
        }

        // Récupérer le type de mouvement "Sortie de Stock"
        $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();

        if (!$type_mouvement) {
            return response()->json(['error' => "Le type de mouvement 'Sortie de Stock' n'existe pas."], 404);
        }

        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();
        // Vérifier la quantité disponible en stock
        $stock = Stock::where('id_Article', $request->id_Article)
              ->where('id_exercice', $exerciceOuvert->id)
              ->latest()
              ->first();

        if (!$stock || $stock->Qte_actuel < $request->qteDemande) {
            // ❌ LOG → Quantité insuffisante
            LogJournalisation::create([
                'action'     => 'Échec: Création de demande de Sortie de Stock (Stock insuffisant)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
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
            "id_exercice" => $exerciceOuvert->id
        ]);

        // ✅ LOG → Succès de la création
        LogJournalisation::create([
            'action'     => "Création de demande de Sortie de Stock (Article ID: {$request->id_Article}, Qté: {$request->qteDemande})",
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
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

        // Vérifier la quantité disponible en stock
        $stock = Stock::where('id_Article', $request->id_Article)
              ->latest()
              ->first();

        // Trouver le mouvement existant
        $mouvement = MouvementStock::find($id);
        if (!$mouvement) {
            return response()->json(['error' => 'Mouvement introuvable.'], 404);
        }

        if (!$stock || $stock->Qte_actuel < $request->qteDemande) {
            LogJournalisation::create([
                'action'     => "Échec: Modification du mouvement de stock ID {$id} (Stock insuffisant)",
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);
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

        LogJournalisation::create([
            'action'     => "Mise à jour du mouvement de stock ID {$id} [{$mouvement->code_mouvement}]{$affectationAction}. Détails affectation: " . implode('; ', $logDetails),
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);
        return new PostResource(true, 'Sortie de stock mise à jour avec succès !', $mouvement);
    }


//1
    public function updateDemandeStock(Request $request, $id)
    {
        $mouvementStock = MouvementStock::findOrFail($id);

        $date_mouvement = $request->input('date_mouvement');
        $statut = $request->input('statut');
        $mouvementStock->statut = $statut;

        // Vérifier la quantité disponible en stock
        $stock = Stock::where('id_Article', $mouvementStock->id_Article)
                    ->latest()
                    ->first();

        // Si le statut est "Accordé"
        if (strtolower($statut) === 'accordé') {

            // Vérifier si la quantité est fournie
            if (!$request->has('qte')) {
                return response()->json(['error' => 'La quantité (qte) est requise lorsque le statut est "Accordé".'], 422);
            }

            $qte = $request->input('qte');



            if (!$stock || $stock->Qte_actuel < $qte) {
                LogJournalisation::create([
                    'action'     => "Échec: {$logActionBase} (Stock insuffisant - Qté demandée: {$qte})",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                    'user_id'    => Auth::id(),
                    'date_action'=> now(),
                ]);

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

        LogJournalisation::create([
            'action'     => $logAction,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);

        return response()->json([
            'message' => 'Demande mise à jour avec succès.',
            'data' => $mouvementStock
        ]);
    }

    //2
    public function validerDemandeGroupee(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code_mouvement' => 'required|string|exists:mouvement_stocks,code_mouvement',
            'date_mouvement' => 'required|date',
            'statut' => 'required|string|in:Accordé,Refusé,Cloturé',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $code = $request->input('code_mouvement');
        $dateMouvement = $request->input('date_mouvement');
        $statut = $request->input('statut');

        // Récupérer tous les mouvements pour le code donné, y compris ceux qui ne sont pas en attente
        $tousLesMouvements = MouvementStock::where('code_mouvement', $code)
            ->get();

        // Filtrer les mouvements à traiter (ceux qui sont en attente)
        $mouvementsATraiter = $tousLesMouvements->where('statut', 'En attente');

        if ($mouvementsATraiter->isEmpty()) {
            return response()->json([
                'message' => "Aucune demande en attente pour le code {$code} n'a pu être traitée.",
                'code_mouvement' => $code,
            ], 200);
        }

        $errors = [];
        $nombreTraites = 0;

        // Boucle sur les mouvements à traiter
        foreach ($mouvementsATraiter as $mouvement) {
            // Appliquer le statut et la date à chaque mouvement en attente
            $mouvement->statut = $statut;
            $mouvement->date_mouvement = $dateMouvement;

            // Si le statut est "Accordé", on procède à la déduction du stock et à l'affectation
            if (strtolower($statut) === 'accordé') {
                $qte = $mouvement->qteDemande;
                $article = $mouvement->article;
                $articleCode = $article ? $article->code_article : "inconnu";

                $stock = Stock::where('id_Article', $mouvement->id_Article)->latest()->first();

                if (!$stock || $stock->Qte_actuel < $qte) {
                    $errors[] = "Quantité insuffisante en stock pour l'article {$articleCode}.";
                    $mouvement->statut = 'Refusé';
                    $mouvement->save();
                    continue;
                }

                $mouvement->qte = $qte;
                $stock->Qte_actuel -= $qte;
                $stock->save();

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
            $mouvement->save();
            $nombreTraites++;
        }

        // On vérifie si toutes les demandes en attente ont été traitées.
        // Si le nombre de demandes traitées (avec succès ou refusées pour manque de stock)
        // est égal au nombre initial de demandes en attente,
        // on met à jour les autres lignes à "Accordé".

        // Récupérer le nombre total de lignes dans le groupe.
        $nombreTotalLignes = MouvementStock::where('code_mouvement', $code)->count();
        // Construction du message de journalisation
        $logAction = "Validation groupée du code {$code} par l'utilisateur " . Auth::id() . ". ";
        $logAction .= "Statut appliqué: {$statut}. ";
        $logAction .= "Lignes traitées: {$nombreTraites}. ";

        // Si le statut est "Accordé" et qu'il n'y a pas d'erreurs, on met à jour
        // les lignes non encore traitées (celles qui ont été ignorées par la boucle `continue`).
        if ($statut === 'Accordé' && empty($errors)) {
            MouvementStock::where('code_mouvement', $code)
                          ->where('statut', 'En attente') // Pour le cas où le statut serait différent de 'Accordé'
                          ->update(['statut' => 'Accordé']);
        }

        if (!empty($errors)) {
            $logAction .= "ATTENTION: Succès partiel/Échec. {$nombreRefusesParStock} ligne(s) refusée(s) pour stock insuffisant.";
            LogJournalisation::create([
                'action'     => $logAction,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return response()->json([
                'message' => "Certaines demandes n'ont pas pu être traitées.",
                'errors' => $errors,
                'code_mouvement' => $code,
                'nombre_demandes_traitees' => $nombreTraites,
            ], 400);
        }

        // ✅ LOG → Succès total
        $logAction .= ($statut_lower === 'accordé') 
        ? "Succès total. {$nombreAffectationsCrees} affectation(s) créée(s)." 
        : "Succès total de la mise à jour du statut.";

        LogJournalisation::create([
            'action'     => $logAction,
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);

        return response()->json([
            'message' => "Toutes les demandes en attente pour le code {$code} ont été traitées avec succès.",
            'code_mouvement' => $code,
            'nombre_demandes_traitees' => $nombreTraites,
            'nombre_total_demandes' => $nombreTotalLignes,
        ]);
    }



    public function deleteSortieStock($id, Request $request)
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

        LogJournalisation::create([
            'action'     => "Suppression logique (isdeleted=true) du mouvement de stock ID {$id} [Code: {$codeMouvement}]",
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);

        return new PostResource(true, 'Sortie de stock supprimée avec succès !', null);
    }

    // get qte disponible
    public function getQuantiteDisponible($idArticle)
    {

        $stock = Stock::where('id_Article', $idArticle)
            ->latest()
            ->first();

        $quantite = $stock ? $stock->Qte_actuel : 0;

        return new PostResource(true, 'Quantité trouvée !', $quantite);
    }

    private function generateNewFicheNumber()
    {
        $lastFiche = MouvementStock::whereNotNull('numero_fiche_demande')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastFiche && $lastFiche->numero_fiche_demande) {
            // Extrait le numéro après "FD-"
            $lastNumber = (int) substr($lastFiche->numero_fiche_demande, 3);
        }

        $newNumber = $lastNumber + 1;
        // Formatage en "FD-0001", "FD-0002", etc.
        return 'FD-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function checkStatusAccorde($codeMouvement)
    {
        // Check if any line in the group is not 'Accordé'
        $allAccordees = MouvementStock::where('code_mouvement', $codeMouvement)
                                      ->where('statut', '!=', 'Accordé')
                                      ->doesntExist();

        if ($allAccordees) {
            // All lines are 'Accordé', so we can generate the file.
            try {
                return $this->genererFicheDemande($codeMouvement);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Demandes traitées, mais une erreur est survenue lors de la génération du fichier.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        // If not all lines are 'Accordé', return a JSON error message.
        return response()->json([
            'message' => "La fiche de demande ne peut être générée. Certaines lignes ne sont pas encore 'Accordé'."
        ], 400);
    }

    /**
     * Your existing function to generate the PDF file.
     * Note: Make sure your existing function is marked as `public` to be callable.
     * @param string $codeMouvement
     * @return \Illuminate\Http\Response
     */
    public function genererFicheDemande($codeMouvement, Request $request)
    {

        // Existing logic from your request
        $mouvements = MouvementStock::with('article', 'employe', 'bureau')
            ->where('code_mouvement', $codeMouvement)
            ->get();

        if ($mouvements->isEmpty()) {
            abort(404, 'La demande de mouvement de stock spécifiée n\'existe pas.');
        }

        // Génère le numéro de fiche unique et le sauvegarde
        $numeroFiche = $this->generateNewFicheNumber();
        MouvementStock::where('code_mouvement', $codeMouvement)
            ->update(['numero_fiche_demande' => $numeroFiche]);

        // Rafraîchir les modèles pour obtenir le nouveau numéro
        $mouvements = $mouvements->map(function ($m) use ($numeroFiche) {
            $m->numero_fiche_demande = $numeroFiche;
            return $m;
        });

        $mouvementPrincipal = $mouvements->first();
        $authUser = Auth::user();

        $data = [
            'mouvement' => $mouvementPrincipal,
            'details' => $mouvements,
            'authUser' => $authUser,
            'numeroFiche' => $numeroFiche // Ajout du numéro à passer à la vue
        ];

        $pdf = PDF::loadView('pdf.demande_sortie', $data);

        LogJournalisation::create([
            'action'     => "Génération et impression de la fiche de demande (PDF) [Code: {$codeMouvement}, Fiche: {$numeroFiche}]",
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);

        return $pdf->download('Fiche_Demande_Sortie_' . $codeMouvement . '_' . $numeroFiche . '.pdf');
    }

    public function genererFicheIndividuelle($id, Request $request)
    {
        $mouvement = MouvementStock::with('article', 'employe', 'bureau')
            ->find($id);

        if (!$mouvement) {
            abort(404, 'Le mouvement de stock spécifié n\'existe pas.');
        }

        // Génère le numéro de fiche unique et le sauvegarde
        $numeroFiche = $this->generateNewFicheNumber();
        $mouvement->update(['numero_fiche_demande' => $numeroFiche]);

        // Rafraîchir le modèle pour obtenir le nouveau numéro
        $mouvement->refresh();

        $authUser = Auth::user();

        $data = [
            'mouvement' => $mouvement,
            'details' => collect([$mouvement]),
            'authUser' => $authUser,
            'numeroFiche' => $numeroFiche // Ajout du numéro à passer à la vue
        ];

        $pdf = PDF::loadView('pdf.demande_sortie', $data);

        LogJournalisation::create([
            'action'     => "Génération et impression de la fiche de demande individuelle (PDF) [ID: {$id}, Fiche: {$numeroFiche}]",
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
            'user_id'    => Auth::id(),
            'date_action'=> now(),
        ]);

        return $pdf->download('Fiche_Demande_Sortie_' . $mouvement->code_mouvement . '_' . $mouvement->id . '_' . $numeroFiche . '.pdf');
    }

    public function validAndUploadSigne(Request $request)
    {
        // 1. Validation de la requête
        $validator = Validator::make($request->all(), [
            'demandevalidesigne' => 'required|file|mimes:pdf|max:2048',
            'statut' => 'required|string',
            'id' => 'sometimes|required_without:code_mouvement|integer|exists:mouvement_stocks,id',
            'code_mouvement' => 'sometimes|required_without:id|string|exists:mouvement_stocks,code_mouvement',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2. Trouver l'enregistrement à mettre à jour
        $itemsToUpdate = null;
        if ($request->has('id')) {
            $itemsToUpdate = MouvementStock::where('id', $request->input('id'))->get();
        } elseif ($request->has('code_mouvement')) {
            $itemsToUpdate = MouvementStock::where('code_mouvement', $request->input('code_mouvement'))->get();
        }

        if (!$itemsToUpdate || $itemsToUpdate->isEmpty()) {
            return response()->json(['message' => 'Demande non trouvée.'], 404);
        }

        // 3. Vérification du statut (autoriser "Accordé" ou "Cloturé")
        foreach ($itemsToUpdate as $item) {
            if ($item->statut !== 'Accordé' && $item->statut !== 'Cloturé') {
                return response()->json([
                    'message' => 'Toutes les lignes de la demande groupée doivent être "Accordé" ou "Cloturé" pour pouvoir télécharger un document groupé.'
                ], 403);
            }
        }

        // 4. Stockage du fichier et mise à jour
        try {
            $path = 'demandes_signees';
            $fileName = time() . '_' . $request->file('demandevalidesigne')->getClientOriginalName();
            $request->file('demandevalidesigne')->storeAs($path, $fileName, 'public');
            $fullFilePath = $path . '/' . $fileName;

            // Mise à jour de tous les éléments du groupe ou de l'élément unique
            foreach ($itemsToUpdate as $item) {
                $item->demandevalidesigne = $fullFilePath;
                $item->statut = $request->input('statut');
                $item->save();
            }

            return response()->json([
                'message' => 'Demande Cloturée et fichier signé téléchargé avec succès.',
                'file_path' => Storage::url($fullFilePath),
                'new_statut' => $request->input('statut')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du téléchargement du fichier.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewFile(Request $request)
    {
        $idfichier = $request->query('idfichier');

        // Correction du chemin : on supprime le préfixe /storage
        $sanitizedPath = str_replace(['storage/'], '', $idfichier);

        // Vérifie si le fichier existe
        if (!Storage::disk('public')->exists($sanitizedPath)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        // Retourne le fichier
        return Response::file(
            Storage::disk('public')->path($sanitizedPath),
            ['Content-Type' => Storage::disk('public')->mimeType($sanitizedPath)]
        );
    }

    public function downloadGroupedFile($code_mouvement)
    {
        // 1. Trouver une ligne avec ce code de mouvement pour obtenir le chemin du fichier.
        $mouvement = MouvementStock::where('code_mouvement', $code_mouvement)
                                   ->whereNotNull('demandevalidesigne') // S'assurer qu'un fichier a été téléchargé
                                   ->first();

        // 2. Vérifier si un mouvement a été trouvé et si le chemin du fichier existe.
        if (!$mouvement || !Storage::disk('public')->exists($mouvement->demandevalidesigne)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        // 3. Renvoie le fichier en tant que téléchargement en utilisant le chemin absolu.
        $filePath = Storage::disk('public')->path($mouvement->demandevalidesigne);
        return response()->download($filePath);
    }


}
