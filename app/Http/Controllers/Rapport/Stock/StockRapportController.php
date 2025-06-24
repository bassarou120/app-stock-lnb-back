<?php

namespace App\Http\Controllers\Rapport\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MouvementStock;
use App\Models\Parametrage\TypeMouvement;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Article;
use App\Models\Parametrage\Fournisseur;
use App\Models\Employe;
use Carbon\Carbon;
use App\Models\Stock;



class StockRapportController extends Controller
{
    /**
     * Récupère les mouvements de stock filtrés pour le rapport en fonction du type de rapport.
     * Gère à la fois les rapports d'entrée et de sortie.
     */
    public function getRapportData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type_rapport' => 'required|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'id_Article' => 'nullable|exists:articles,id',
            'id_fournisseur' => 'nullable|exists:fournisseurs,id',
            'id_employe' => 'nullable|exists:employes,id',
            'id_type_mouvement' => 'nullable|exists:type_mouvements,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = MouvementStock::query();

        $query->with([
            'article',
            'piecesJointes',
            'article.categorie',
            'article.stock',
            'bureau',
            'typeMouvement',
            'unite_de_mesure'
        ]);

        $query->whereBetween('date_mouvement', [$request->date_debut, $request->date_fin]);

        if ($request->filled('id_type_mouvement')) {
            $query->where('id_type_mouvement', $request->id_type_mouvement);
        }

        if ($request->id_type_rapport === 'entree') {
            $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Entrée de Stock")->latest()->first();
            if ($type_mouvement) {
                $query->where('id_type_mouvement', $type_mouvement->id);
            }
            $query->with('fournisseur');
            if ($request->filled('id_fournisseur')) {
                $query->where('id_fournisseur', $request->id_fournisseur);
            }
            if ($request->filled('id_Article')) {
                $query->where('id_Article', $request->id_Article);
            }
        } elseif ($request->id_type_rapport === 'sortie') {
            $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();
            if ($type_mouvement) {
                $query->where('id_type_mouvement', $type_mouvement->id);
            }
            $query->with('employe')->where('statut', '=', 'Accordé');
            if ($request->filled('id_employe')) {
                $query->where('id_employe', $request->id_employe);
            }
            if ($request->filled('id_Article')) {
                $query->where('id_Article', $request->id_Article);
            }
        }

        $resultats = $query->latest()->paginate(1000);

        return new PostResource(true, 'Rapport de stock généré avec succès.', $resultats);
    }

    /**
     * Génère un PDF du rapport des mouvements de stock.
     */
    public function imprimerRapportStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type_rapport' => 'required|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'id_Article' => 'nullable|exists:articles,id',
            'id_fournisseur' => 'nullable|exists:fournisseurs,id',
            'id_employe' => 'nullable|exists:employes,id',
            'id_type_mouvement' => 'nullable|exists:type_mouvements,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $query = MouvementStock::query();

        $query->with([
            'article',
            'piecesJointes',
            'article.categorie',
            'article.stock',
            'typeMouvement',
            'fournisseur',
            'employe',
            'unite_de_mesure'
        ]);

        $query->whereBetween('date_mouvement', [$request->date_debut, $request->date_fin]);

        if ($request->filled('id_type_mouvement')) {
            $query->where('id_type_mouvement', $request->id_type_mouvement);
        }

        if ($request->id_type_rapport === 'entree') {
            $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Entrée de Stock")->latest()->first();
            if ($type_mouvement) {
                $query->where('id_type_mouvement', $type_mouvement->id);
            }
            if ($request->filled('id_fournisseur')) {
                $query->where('id_fournisseur', $request->id_fournisseur);
            }
            if ($request->filled('id_Article')) {
                $query->where('id_Article', $request->id_Article);
            }
        } elseif ($request->id_type_rapport === 'sortie') {
            $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();
            if ($type_mouvement) {
                $query->where('id_type_mouvement', $type_mouvement->id);
            }
            $query->where('statut', '=', 'Accordé');
            if ($request->filled('id_employe')) {
                $query->where('id_employe', $request->id_employe);
            }
            if ($request->filled('id_Article')) {
                $query->where('id_Article', $request->id_Article);
            }
        }

        $mouvements = $query->latest()->get();

        $reportTypeLabel = '';
        switch ($request->id_type_rapport) {
            case 'entree':
                $reportTypeLabel = 'd\'Entrée de Stock';
                break;
            case 'sortie':
                $reportTypeLabel = 'de Sortie de Stock';
                break;
            default:
                $reportTypeLabel = 'de Stock';
                break;
        }

        $filterLabels = [
            'date_debut' => Carbon::parse($request->date_debut)->format('d/m/Y'),
            'date_fin' => Carbon::parse($request->date_fin)->format('d/m/Y'),
            'article' => 'Tous',
            'fournisseur' => 'Tous',
            'employe' => 'Tous',
        ];

        if ($request->filled('id_Article')) {
            $article = Article::find($request->id_Article);
            $filterLabels['article'] = $article ? ($article->code_article . ' - ' . $article->libelle) : 'Non trouvé';
        }

        if ($request->id_type_rapport === 'entree' && $request->filled('id_fournisseur')) {
            $fournisseur = Fournisseur::find($request->id_fournisseur);
            $filterLabels['fournisseur'] = $fournisseur ? $fournisseur->nom : 'Non trouvé';
        }

        if ($request->id_type_rapport === 'sortie' && $request->filled('id_employe')) {
            $employe = Employe::find($request->id_employe);
            $filterLabels['employe'] = $employe ? ($employe->nom . ' ' . $employe->prenom) : 'Non trouvé';
        }

        $pdf = Pdf::loadView('pdf.rapport.rapport_stock', compact('mouvements', 'reportTypeLabel', 'filterLabels'));

        $filename = 'rapport_stock_' . $request->id_type_rapport . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * NOUVEAU : Génère un PDF du rapport de l'état de stock par article.
     */
    public function imprimerRapportEtatStock(Request $request)
    {
        // ✅ VALIDATION
        $validator = Validator::make($request->all(), [
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'id_article' => 'nullable|exists:articles,id',
            'qte_min' => 'nullable|numeric|min:0',
            'qte_max' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ PARAMÈTRES DE FILTRAGE
        $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
        $dateFin = Carbon::parse($request->date_fin)->endOfDay();
        $idArticle = $request->id_article;
        $qteMin = $request->qte_min;
        $qteMax = $request->qte_max;

        // ✅ CONSTRUCTION DE LA REQUÊTE
        $query = Article::with(['categorie', 'stock', 'uniteDeMesure']); // 'unite_de_mesure' -> 'uniteDeMesure' pour respecter la convention si la relation est ainsi.
                                                                        // Assurez-vous que cette relation existe dans votre modèle Article.

        if ($request->filled('id_article')) {
            $query->where('id', $idArticle);
        } else {
            $query->whereHas('mouvementStocks', function($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('date_mouvement', [$dateDebut, $dateFin]);
            });
        }

        if ($qteMin !== null || $qteMax !== null) {
            $query->whereHas('stock', function($q) use ($qteMin, $qteMax) {
                if ($qteMin !== null) {
                    $q->where('Qte_actuel', '>=', $qteMin);
                }
                if ($qteMax !== null) {
                    $q->where('Qte_actuel', '<=', $qteMax);
                }
            });
        }

        $articles = $query->get();
        $rapportArticles = [];

        foreach ($articles as $article) {
            if ($article->stock) {
                $rapportArticle = $this->genererRapportCompletArticle($article, $dateDebut, $dateFin);
                $rapportArticles[] = $rapportArticle;
            } else {
                \Log::warning("Article ID {$article->id} - {$article->libelle} n'a pas d'enregistrement de stock associé pour l'impression du rapport d'état.");
            }
        }

        // ✅ PRÉPARER LES LIBELLÉS DES FILTRES POUR LA VUE PDF
        $filterLabels = [
            'date_debut' => $dateDebut->format('d/m/Y'),
            'date_fin' => $dateFin->format('d/m/Y'),
            'article' => 'Tous',
            'qte_min' => $qteMin ?? 'Non spécifié',
            'qte_max' => $qteMax ?? 'Non spécifié',
        ];

        if ($request->filled('id_article')) {
            $article = Article::find($idArticle);
            $filterLabels['article'] = $article ? ($article->code_article . ' - ' . $article->libelle) : 'Non trouvé';
        }

        // Préparer les statistiques pour le PDF
        $statistiques = [
            'nombre_articles' => count($rapportArticles),
            'periode_analysee' => $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y')
        ];


        // ✅ GÉNÉRER LE PDF
        $pdf = Pdf::loadView('pdf.rapport.rapport_etat_stock', compact('rapportArticles', 'filterLabels', 'statistiques'));

        // Ajoutez cette ligne pour définir l'orientation en paysage
        $pdf->setPaper('A4', 'landscape'); // Ou 'letter', 'legal', etc.

        return $pdf->download('rapport_etat_stock.pdf');
        // Ou return $pdf->stream('rapport_etat_stock.pdf'); pour l'afficher dans le navigateur
    }


    // Fonction pour le filtrage d'etat du stock (JSON)
    public function getRapportFicheStock(Request $request)
    {
        // ✅ VALIDATION
        $validator = Validator::make($request->all(), [
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'id_article' => 'nullable|exists:articles,id',
            'qte_min' => 'nullable|numeric|min:0',
            'qte_max' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ PARAMÈTRES DE FILTRAGE
        $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
        $dateFin = Carbon::parse($request->date_fin)->endOfDay();
        $idArticle = $request->id_article;
        $qteMin = $request->qte_min;
        $qteMax = $request->qte_max;

        // ✅ CONSTRUCTION DE LA REQUÊTE
        $query = Article::with(['categorie', 'stock']);

        // CRITÈRE 1: Articles qui ont des mouvements dans la période OU article spécifique
        if ($request->filled('id_article')) {
            $query->where('id', $idArticle);
        } else {
            // Uniquement les articles qui ont eu des mouvements dans la période
            $query->whereHas('mouvementStocks', function($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('date_mouvement', [$dateDebut, $dateFin]);
            });
        }

        // CRITÈRE 2: Filtrer par quantité en stock si spécifié
        if ($qteMin !== null || $qteMax !== null) {
            $query->whereHas('stock', function($q) use ($qteMin, $qteMax) {
                if ($qteMin !== null) {
                    $q->where('Qte_actuel', '>=', $qteMin);
                }
                if ($qteMax !== null) {
                    $q->where('Qte_actuel', '<=', $qteMax);
                }
            });
        }

        // ✅ EXÉCUTER LA REQUÊTE
        $articles = $query->get();
        $rapportArticles = [];

        foreach ($articles as $article) {
            if ($article->stock) {
                $rapportArticle = $this->genererRapportCompletArticle($article, $dateDebut, $dateFin);
                $rapportArticles[] = $rapportArticle;
            } else {
                \Log::warning("Article ID {$article->id} - {$article->libelle} n'a pas d'enregistrement de stock associé.");
            }
        }


        // ✅ PRÉPARER LES LIBELLÉS DES FILTRES
        $filterLabels = [
            'date_debut' => $dateDebut->format('d/m/Y'),
            'date_fin' => $dateFin->format('d/m/Y'),
            'article' => 'Tous',
            'qte_min' => $qteMin ?? 'Non spécifié',
            'qte_max' => $qteMax ?? 'Non spécifié',
        ];

        if ($request->filled('id_article')) {
            $article = Article::find($idArticle);
            $filterLabels['article'] = $article ? ($article->code_article . ' - ' . $article->libelle) : 'Non trouvé';
        }

        return new PostResource(true,
            "Rapport complet des articles avec entrées/sorties/stock",
            [
                'articles' => $rapportArticles,
                'filtres_appliques' => $filterLabels,
                'statistiques' => [
                    'nombre_articles' => count($rapportArticles),
                    'periode_analysee' => $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y')
                ]
            ]
        );
    }

    private function genererRapportCompletArticle($article, $dateDebut, $dateFin)
    {
        // ✅ 1. DEBUG - VÉRIFIER LES TYPES DE MOUVEMENTS
        $typeEntree = TypeMouvement::where(function($query) {
            $query->where('libelle_type_mouvement', 'like', '%entrée%')
                ->orWhere('libelle_type_mouvement', 'like', '%entree%')
                ->orWhere('libelle_type_mouvement', 'like', '%Entrée%')
                ->orWhere('libelle_type_mouvement', 'like', '%Entree%')
                ->orWhere('libelle_type_mouvement', 'like', '%ENTREE%')
                ->orWhere('libelle_type_mouvement', 'like', '%ENTRÉE%');
        })->first();

        $typeSortie = TypeMouvement::where(function($query) {
            $query->where('libelle_type_mouvement', 'like', '%sortie%')
                ->orWhere('libelle_type_mouvement', 'like', '%Sortie%')
                ->orWhere('libelle_type_mouvement', 'like', '%SORTIE%');
        })->first();

        // ✅ 2. RÉCUPÉRER TOUS LES MOUVEMENTS DE L'ARTICLE (pour debug)
        $tousLesMouvements = MouvementStock::with(['typeMouvement', 'fournisseur', 'employe', 'bureau'])
            ->where('id_Article', $article->id)
            ->orderBy('date_mouvement', 'desc')
            ->get();

        // ✅ 3. STOCK ACTUEL (chercher le prix avec le bon nom de champ)
        $prixUnitaireArticle = 0;

        // Chercher le prix dans l'article (avec le bon nom de champ)
        // CORRECTION: Utilisation de 'prixUnitaire' au lieu de 'prix_unitaire'
        if (isset($article->prixUnitaire) && $article->prixUnitaire > 0) { 
            $prixUnitaireArticle = $article->prixUnitaire;
        } else {
            // Chercher le prix dans le dernier mouvement (avec le bon nom de champ)
            $dernierMouvementAvecPrix = MouvementStock::where('id_Article', $article->id)
                ->whereNotNull('prixUnitaire') // CORRECTION: Utilisation de 'prixUnitaire'
                ->where('prixUnitaire', '>', 0) // CORRECTION: Utilisation de 'prixUnitaire'
                ->orderBy('date_mouvement', 'desc')
                ->first();

            if ($dernierMouvementAvecPrix) {
                $prixUnitaireArticle = $dernierMouvementAvecPrix->prixUnitaire; // CORRECTION: Utilisation de 'prixUnitaire'
            }
        }

        $stockActuel = [
            'quantite' => $article->stock->Qte_actuel ?? 0,
            'prix_unitaire' => $prixUnitaireArticle, // Garder 'prix_unitaire' ici car c'est le nom dans le tableau de sortie
            'montant_total' => ($article->stock->Qte_actuel ?? 0) * $prixUnitaireArticle,
            'date_maj' => $article->stock->updated_at ?? null
        ];

        // ✅ 4. DERNIÈRE ENTRÉE
        $derniereEntree = null;

        if ($typeEntree) {
            $mouvementEntree = MouvementStock::with(['fournisseur', 'typeMouvement'])
                ->where('id_Article', $article->id)
                ->where('id_type_mouvement', $typeEntree->id)
                ->orderBy('date_mouvement', 'desc')
                ->first();
        } else {
            $mouvementEntree = MouvementStock::with(['fournisseur', 'typeMouvement'])
                ->where('id_Article', $article->id)
                ->whereHas('typeMouvement', function($query) {
                    $query->where('libelle_type_mouvement', 'like', '%entrée%')
                        ->orWhere('libelle_type_mouvement', 'like', '%entree%');
                })
                ->orderBy('date_mouvement', 'desc')
                ->first();
        }

        if ($mouvementEntree) {
            $prixUnitaire = $mouvementEntree->prixUnitaire ?? $prixUnitaireArticle; // CORRECTION: Utilisation de 'prixUnitaire'
            $quantite = $mouvementEntree->qte ?? 0;

            $derniereEntree = [
                'date' => Carbon::parse($mouvementEntree->date_mouvement)->format('d/m/Y H:i'),
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire, // Garder 'prix_unitaire' ici car c'est le nom dans le tableau de sortie
                'montant' => $quantite * $prixUnitaire,
                'fournisseur' => $mouvementEntree->fournisseur->nom ?? 'N/A',
                'description' => $mouvementEntree->description ?? '',
                'type_mouvement' => $mouvementEntree->typeMouvement->libelle_type_mouvement ?? 'N/A',
                'dans_periode' => $mouvementEntree->date_mouvement >= $dateDebut &&
                                  $mouvementEntree->date_mouvement <= $dateFin
            ];
        }

        // ✅ 5. DERNIÈRE SORTIE
        $derniereSortie = null;

        if ($typeSortie) {
            $mouvementSortie = MouvementStock::with(['employe', 'bureau', 'typeMouvement'])
                ->where('id_Article', $article->id)
                ->where('id_type_mouvement', $typeSortie->id)
                ->orderBy('date_mouvement', 'desc')
                ->first();
        } else {
            $mouvementSortie = MouvementStock::with(['employe', 'bureau', 'typeMouvement'])
                ->where('id_Article', $article->id)
                ->whereHas('typeMouvement', function($query) {
                    $query->where('libelle_type_mouvement', 'like', '%sortie%');
                })
                ->orderBy('date_mouvement', 'desc')
                ->first();
        }

        if ($mouvementSortie) {
            $prixUnitaire = $mouvementSortie->prixUnitaire ?? $prixUnitaireArticle; // CORRECTION: Utilisation de 'prixUnitaire'
            $quantite = $mouvementSortie->qte ?? $mouvementSortie->qteDemande ?? 0;

            $derniereSortie = [
                'date' => Carbon::parse($mouvementSortie->date_mouvement)->format('d/m/Y H:i'),
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire, // Garder 'prix_unitaire' ici car c'est le nom dans le tableau de sortie
                'montant' => $quantite * $prixUnitaire,
                'employe' => $mouvementSortie->employe ?
                                    ($mouvementSortie->employe->nom . ' ' . $mouvementSortie->employe->prenom) : 'N/A',
                'bureau' => $mouvementSortie->bureau->libelle_bureau ?? 'N/A',
                'description' => $mouvementSortie->description ?? '',
                'type_mouvement' => $mouvementSortie->typeMouvement->libelle_type_mouvement ?? 'N/A',
                'statut' => $mouvementSortie->statut ?? 'N/A',
                'dans_periode' => $mouvementSortie->date_mouvement >= $dateDebut &&
                                  $mouvementSortie->date_mouvement <= $dateFin
            ];
        }

        // ✅ 6. CALCULS DE SYNTHÈSE
        $totalEntreesPeriode = 0;
        $totalSortiesPeriode = 0;

        if ($typeEntree) {
            $totalEntreesPeriode = MouvementStock::where('id_Article', $article->id)
                ->where('id_type_mouvement', $typeEntree->id)
                ->whereBetween('date_mouvement', [$dateDebut, $dateFin])
                ->sum('qte') ?? 0;
        }

        if ($typeSortie) {
            $totalSortiesPeriode = MouvementStock::where('id_Article', $article->id)
                ->where('id_type_mouvement', $typeSortie->id)
                ->whereBetween('date_mouvement', [$dateDebut, $dateFin])
                ->sum('qte') ?? 0;
        }

        return [
            'article' => [
                'id' => $article->id,
                'libelle' => $article->libelle,
                'code_article' => $article->code_article,
                'description' => $article->description,
                'categorie' => $article->categorie->libelle_categorie_article ?? 'N/A',
                'stock_alerte' => $article->stock_alerte ?? 0,
                // 'unite_de_mesure' => $article->unite_de_mesure->libelle_unite ?? 'N/A' // Remplacé par l'accès direct si la relation est `uniteDeMesure`
                'unite_de_mesure' => $article->uniteDeMesure->libelle_unite ?? 'N/A' // Utilisation de uniteDeMesure
            ],
            'stock_actuel' => $stockActuel,
            'derniere_entree' => $derniereEntree,
            'derniere_sortie' => $derniereSortie,
            'synthese_periode' => [
                'total_entrees' => $totalEntreesPeriode,
                'total_sorties' => $totalSortiesPeriode,
                'mouvement_net' => $totalEntreesPeriode - $totalSortiesPeriode,
                'periode' => $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y')
            ],
            // ✅ DEBUG INFO (gardé pour le développement)
            'debug' => [
                'type_entree_trouve' => $typeEntree ? $typeEntree->libelle_type_mouvement : 'NON TROUVÉ',
                'type_sortie_trouve' => $typeSortie ? $typeSortie->libelle_type_mouvement : 'NON TROUVÉ',
                'nombre_mouvements_total' => $tousLesMouvements->count(),
                'mouvements_dans_periode' => $tousLesMouvements->whereBetween('date_mouvement', [$dateDebut, $dateFin])->count(),
                'prix_unitaire_source' => $prixUnitaireArticle > 0 ? 'trouvé' : 'non trouvé',
                'tous_les_mouvements_bruts' => $tousLesMouvements->map(function($mouvement) {
                    return [
                        'id' => $mouvement->id,
                        'date' => $mouvement->date_mouvement,
                        'type' => $mouvement->typeMouvement->libelle_type_mouvement ?? 'N/A',
                        'quantite' => $mouvement->qte,
                        'prix' => $mouvement->prixUnitaire // CORRECTION: Utilisation de 'prixUnitaire'
                    ];
                })
            ]
        ];
    }
}
