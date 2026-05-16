<?php

namespace App\Http\Controllers\Rapport\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MouvementStock;
use App\Models\Parametrage\TypeMouvement;
use App\Models\Parametrage\CategorieArticle;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Article;
use App\Models\Parametrage\Fournisseur;
use App\Models\Employe;
use Carbon\Carbon;
use App\Models\Stock;
use App\Models\Exercice;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;


class StockRapportController extends Controller
{

    // =========================================================================
    // MÉTHODES PRIVÉES UTILITAIRES
    // =========================================================================

    /**
     * Retourne l'exercice correspondant à la période sélectionnée.
     * Si la période est entièrement couverte par un exercice, on le retourne.
     * Sinon, on retourne l'exercice ouvert par défaut.
     */
    private function getExercicePourPeriode($dateDebut, $dateFin)
    {
        // Chercher l'exercice qui contient toute la période sélectionnée
        $exercice = Exercice::where('date_debut', '<=', $dateDebut)
                            ->where('date_fin', '>=', $dateFin)
                            ->first();

        // Si aucun exercice ne correspond, prendre l'exercice ouvert
        if (!$exercice) {
            $exercice = Exercice::where('statut', 'ouvert')->first();
        }

        return $exercice;
    }

    /**
     * Retourne l'enregistrement Stock d'un article pour la période donnée.
     */
    private function getStockPourExercice($article, $dateDebut, $dateFin)
    {
        $exercice = $this->getExercicePourPeriode($dateDebut, $dateFin);

        if (!$exercice) return null;

        return $article->stockPourExercice($exercice->id);
    }


    // =========================================================================
    // MÉTHODES PUBLIQUES
    // =========================================================================

    /**
     * Récupère les mouvements de stock filtrés pour le rapport en fonction du type de rapport.
     * Gère à la fois les rapports d'entrée et de sortie.
     */
    public function getRapportData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type_rapport' => 'required|string|in:entree,sortie,individuel',
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

        if ($request->id_type_rapport === 'individuel') {
            if (!$request->filled('id_Article')) {
                return response()->json(['message' => 'L\'ID de l\'article est requis pour un rapport individuel.'], 422);
            }

            $idArticle = $request->id_Article;
            $dateDebut = $request->date_debut;
            $dateFin = $request->date_fin;

            // Calculer le stock initial avant la période
            // $stockInitialEntrees = MouvementStock::where('id_Article', $idArticle)
            //     ->where('date_mouvement', '<', $dateDebut)
            //     ->whereHas('typeMouvement', function ($query) {
            //         $query->where('libelle_type_mouvement', 'Entrée de Stock');
            //     })
            //     ->sum('qte');

            // $stockInitialSorties = MouvementStock::where('id_Article', $idArticle)
            //     ->where('date_mouvement', '<', $dateDebut)
            //     ->whereHas('typeMouvement', function ($query) {
            //         $query->where('libelle_type_mouvement', 'Sortie de Stock');
            //     })
            //     ->sum('qte');

            // $stockInitial = $stockInitialEntrees - $stockInitialSorties;

            // Déterminer l'exercice correspondant à la période
$exercice = $this->getExercicePourPeriode($dateDebut, $dateFin);

// Récupérer le stock_debut_exercice depuis la table pivot article_exercice
$stockInitial = 0;

if ($exercice) {
    $article = Article::find($idArticle);
    $pivotExercice = $article->exercices()
        ->wherePivot('id_exercice', $exercice->id)
        ->first();

    if ($pivotExercice) {
        $stockInitial = $pivotExercice->pivot->stock_debut_exercice ?? 0;
    }
}

            // Récupérer les mouvements de la période
            $mouvements = MouvementStock::where('id_Article', $idArticle)
                ->whereBetween('date_mouvement', [$dateDebut, $dateFin])
                ->when($request->filled('id_categorie_article'), function ($query) use ($request) {
                    $query->whereHas('article', function ($q) use ($request) {
                        $q->where('id_cat', $request->id_categorie_article);
                    });
                })
                // ->orderBy('date_mouvement', 'asc')
                // ->orderBy('created_at', 'asc')
                ->orderBy('date_mouvement', 'asc')
->orderByRaw("CASE WHEN id_type_mouvement = (SELECT id FROM type_mouvements WHERE libelle_type_mouvement = 'Entrée de Stock' LIMIT 1) THEN 0 ELSE 1 END ASC")
->orderBy('created_at', 'asc')
                ->with([
                    'typeMouvement',
                    'fournisseur',
                    'employe',
                    'bureau',
                    'unite_de_mesure',
                    'article.categorie'
                ])
                ->get();

            $rapportData = collect();
            $stockActuel = $stockInitial;

            foreach ($mouvements as $mouvement) {
                $entree = 0;
                $sortie = 0;
                $libelleTypeMouvement = $mouvement->typeMouvement->libelle_type_mouvement;

                if ($libelleTypeMouvement === 'Entrée de Stock') {
                    $entree = $mouvement->qte;
                } elseif ($libelleTypeMouvement === 'Sortie de Stock') {
                    $sortie = $mouvement->qte;
                }

                $rapportData->push([
                    'date_mouvement' => $mouvement->date_mouvement,
                    'numero_bordereau' => $mouvement->numero_bordereau,
                    'stock_initial_ligne' => $stockActuel,
                    'entrees' => $entree,
                    'sorties' => $sortie,
                    'stock_final' => $stockActuel + $entree - $sortie,
                    'pu' => $mouvement->prixUnitaire,
                    'observations' => ($libelleTypeMouvement === 'Entrée de Stock') ? ($mouvement->fournisseur->nom ?? '') : ($mouvement->employe->fullnameEmploye ?? $mouvement->bureau->libelle_bureau ?? ''),
                    'article' => $mouvement->article,
                ]);

                $stockActuel = $stockActuel + $entree - $sortie;
            }

            LogJournalisation::create([
                "action"      => "Consultation du rapport des stock individuel",
                "ip_address"  => request()->ip(),
                "user_agent"  => request()->userAgent(),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                "date_action" => now()
            ]);

            return new PostResource(true, 'Rapport individuel généré avec succès.', $rapportData);
        }

        $query = MouvementStock::query();

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
            if ($request->filled('id_categorie_article')) {
                $query->whereHas('article', function ($q) use ($request) {
                    $q->where('id_cat', $request->id_categorie_article);
                });
            }

            LogJournalisation::create([
                "action"      => "Consultation du rapport des stock d'entrée",
                "ip_address"  => request()->ip(),
                "user_agent"  => request()->userAgent(),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                "date_action" => now()
            ]);

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
            LogJournalisation::create([
                "action"      => "Consultation du rapport des stocks de sortie",
                "ip_address"  => request()->ip(),
                "user_agent"  => request()->userAgent(),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                "date_action" => now()
            ]);
        }

        $query->with([
            'article',
            'piecesJointes',
            'article.categorie',
            'article.stock',
            'bureau',
            'typeMouvement',
            'unite_de_mesure'
        ]);

        $resultats = $query->latest()->paginate(1000);

        return new PostResource(true, 'Rapport de stock généré avec succès.', $resultats);
    }

    /**
     * Génère un PDF du rapport des mouvements de stock.
     */
    public function imprimerRapportStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_type_rapport' => 'required|string|in:entree,sortie,individuel',
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

        LogJournalisation::create([
            "action"      => "Demande d'impression du rapport stock (" . $request->id_type_rapport . ") du " .
                            Carbon::parse($request->date_debut)->format('d/m/Y') . " au " .
                            Carbon::parse($request->date_fin)->format('d/m/Y'),
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        if ($request->id_type_rapport === 'individuel') {
            $idArticle = $request->id_Article;
            if (!$request->filled('id_Article')) {
                return response()->json(['message' => 'L\'ID de l\'article est requis pour un rapport individuel.'], 422);
            }

            LogJournalisation::create([
                "action"      => "Téléchargement du rapport individuel stock article : " . $idArticle,
                "ip_address"  => request()->ip(),
                "user_agent"  => request()->userAgent(),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                "date_action" => now()
            ]);

            $dateDebut = $request->date_debut;
            $dateFin = $request->date_fin;

            // $stockInitialEntrees = MouvementStock::where('id_Article', $idArticle)
            //     ->where('date_mouvement', '<', $dateDebut)
            //     ->whereHas('typeMouvement', function ($query) {
            //         $query->where('libelle_type_mouvement', 'Entrée de Stock');
            //     })
            //     ->sum('qte');

            // $stockInitialSorties = MouvementStock::where('id_Article', $idArticle)
            //     ->where('date_mouvement', '<', $dateDebut)
            //     ->whereHas('typeMouvement', function ($query) {
            //         $query->where('libelle_type_mouvement', 'Sortie de Stock');
            //     })
            //     ->sum('qte');

            // $stockInitial = $stockInitialEntrees - $stockInitialSorties;

            // Déterminer l'exercice correspondant à la période
$exercice = $this->getExercicePourPeriode($dateDebut, $dateFin);

// Récupérer le stock_debut_exercice depuis la table pivot article_exercice
$stockInitial = 0;

if ($exercice) {
    $article = Article::find($idArticle);
    $pivotExercice = $article->exercices()
        ->wherePivot('id_exercice', $exercice->id)
        ->first();

    if ($pivotExercice) {
        $stockInitial = $pivotExercice->pivot->stock_debut_exercice ?? 0;
    }
}

            $mouvements = MouvementStock::where('id_Article', $idArticle)
                ->whereBetween('date_mouvement', [$dateDebut, $dateFin])
                // ->orderBy('date_mouvement', 'asc')
                // ->orderBy('created_at', 'asc')
                ->orderBy('date_mouvement', 'asc')
->orderByRaw("CASE WHEN id_type_mouvement = (SELECT id FROM type_mouvements WHERE libelle_type_mouvement = 'Entrée de Stock' LIMIT 1) THEN 0 ELSE 1 END ASC")
->orderBy('created_at', 'asc')
                ->with(['typeMouvement','fournisseur','employe','bureau','unite_de_mesure','article.categorie'])
                ->get();

            $rapportData = collect();
            $stockActuel = $stockInitial;
            $currentPU = 0;

            foreach ($mouvements as $mouvement) {
                $entree = 0;
                $sortie = 0;
                $libelleTypeMouvement = $mouvement->typeMouvement->libelle_type_mouvement;

                if ($libelleTypeMouvement === 'Entrée de Stock') {
                    $entree = $mouvement->qte;
                    $currentPU = $mouvement->prixUnitaire;
                } elseif ($libelleTypeMouvement === 'Sortie de Stock') {
                    $sortie = $mouvement->qte;
                    if (empty($mouvement->prixUnitaire)) {
                        $mouvement->prixUnitaire = $currentPU;
                    }
                }

                $observation = ($libelleTypeMouvement === 'Entrée de Stock')
                    ? ($mouvement->fournisseur->nom ?? '') . ' - ' . ($mouvement->observations ?? '')
                    : ($mouvement->employe->nom . ' ' . $mouvement->employe->prenom ?? $mouvement->bureau->libelle_bureau ?? '') . ' - ' . ($mouvement->observations ?? '');

                $rapportData->push([
                    'date_mouvement' => $mouvement->date_mouvement,
                    'numero_bordereau' => $mouvement->numero_bordereau,
                    'stock_initial_ligne' => $stockActuel,
                    'entrees' => $entree,
                    'sorties' => $sortie,
                    'stock_final' => $stockActuel + $entree - $sortie,
                    'pu' => $mouvement->prixUnitaire ?? $currentPU,
                    'observations' => $observation,
                    'article' => $mouvement->article,
                ]);

                $stockActuel = $stockActuel + $entree - $sortie;
            }

            $stockFinalPeriod = $stockActuel;
            $reportTypeLabel = 'Individuelle';
            $article = Article::find($idArticle);
            $filterLabels = [
                'date_debut' => Carbon::parse($dateDebut)->format('d/m/Y'),
                'date_fin' => Carbon::parse($dateFin)->format('d/m/Y'),
                'article' => $article ? ($article->code_article . ' - ' . $article->libelle) : 'Non trouvé',
            ];

            $pdf = Pdf::loadView('pdf.rapport.rapport_individuel', compact('rapportData', 'stockInitial', 'stockFinalPeriod', 'reportTypeLabel', 'filterLabels', 'article'));

            $filename = 'rapport_stock_individuel.pdf';

            return response($pdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="'.$filename.'"');
        }

        // =========================
        // Cas général (entrée/sortie)
        // =========================
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
            if ($request->filled('id_categorie_article')) {
                $query->whereHas('article', function ($q) use ($request) {
                    $q->where('id_cat', $request->id_categorie_article);
                });
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
            if ($request->filled('id_categorie_article')) {
                $query->whereHas('article', function ($q) use ($request) {
                    $q->where('id_cat', $request->id_categorie_article);
                });
            }
        }

        $mouvements = $query->latest()->get();

        switch ($request->id_type_rapport) {
            case 'entree':
                $reportTypeLabel = 'd\'Entrée de Stock';
                break;
            case 'sortie':
                $reportTypeLabel = 'de Sortie de Stock';
                break;
            case 'individuel':
                $reportTypeLabel = 'de rapport individuel';
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

        LogJournalisation::create([
            "action"      => "Téléchargement du rapport stock général : " . ucfirst($request->id_type_rapport),
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        $pdf = Pdf::loadView('pdf.rapport.rapport_stock', compact('mouvements', 'reportTypeLabel', 'filterLabels'));
        $filename = 'rapport_stock_' . $request->id_type_rapport . '.pdf';

        return $pdf->download($filename);
    }


    /**
     * Génère un PDF du rapport de l'état de stock par article.
     */
    public function imprimerRapportEtatStock(Request $request)
    {
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

        $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
        $dateFin = Carbon::parse($request->date_fin)->endOfDay();
        $idArticle = $request->id_article;
        $qteMin = $request->qte_min;
        $qteMax = $request->qte_max;

        // Déterminer l'exercice correspondant à la période
        $exercice = $this->getExercicePourPeriode($dateDebut, $dateFin);

        $query = Article::with(['categorie', 'stocks', 'uniteDeMesure']);

        if ($request->filled('id_article')) {
            $query->where('id', $idArticle);
        } else {
            $query->whereHas('mouvementStocks', function($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('date_mouvement', [$dateDebut, $dateFin]);
            });
        }

        if ($request->filled('id_categorie_article')) {
            $query->where('id_cat', $request->id_categorie_article);
        }

        // Filtrer par quantité sur le bon exercice
        if (($qteMin !== null || $qteMax !== null) && $exercice) {
            $query->whereHas('stocks', function($q) use ($qteMin, $qteMax, $exercice) {
                $q->where('id_exercice', $exercice->id);
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
            // Récupérer le stock pour le bon exercice
            $stock = $exercice ? $article->stockPourExercice($exercice->id) : null;

            if ($stock) {
                $rapportArticle = $this->genererRapportCompletArticle($article, $dateDebut, $dateFin, $request, $stock);
                $rapportArticles[] = $rapportArticle;
            } else {
                \Log::warning("Article ID {$article->id} - {$article->libelle} n'a pas d'enregistrement de stock pour l'exercice ID " . ($exercice->id ?? 'N/A'));
            }
        }

        $filterLabels = [
            'date_debut' => $dateDebut->format('d/m/Y'),
            'date_fin' => $dateFin->format('d/m/Y'),
            'article' => 'Tous',
            'qte_min' => $qteMin ?? 'Non spécifié',
            'qte_max' => $qteMax ?? 'Non spécifié',
            'exercice' => $exercice ? $exercice->annee : 'Non déterminé',
        ];

        if ($request->filled('id_article')) {
            $article = Article::find($idArticle);
            $filterLabels['article'] = $article ? ($article->code_article . ' - ' . $article->libelle) : 'Non trouvé';
        }

        $statistiques = [
            'nombre_articles' => count($rapportArticles),
            'periode_analysee' => $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y')
        ];

        LogJournalisation::create([
            "action"      => "Téléchargement du rapport d'état stock" .
                            ($idArticle ? " pour l'article ID {$idArticle}" : "") .
                            " du " . $dateDebut->format('d/m/Y') . " au " . $dateFin->format('d/m/Y'),
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        $pdf = Pdf::loadView('pdf.rapport.rapport_etat_stock', compact('rapportArticles', 'filterLabels', 'statistiques'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('rapport_etat_stock.pdf');
    }


    /**
     * Filtrage d'état du stock (JSON).
     */
    public function getRapportFicheStock(Request $request)
    {
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

        $dateDebut = Carbon::parse($request->date_debut)->startOfDay();
        $dateFin = Carbon::parse($request->date_fin)->endOfDay();
        $idArticle = $request->id_article;
        $qteMin = $request->qte_min;
        $qteMax = $request->qte_max;

        // Déterminer l'exercice correspondant à la période
        $exercice = $this->getExercicePourPeriode($dateDebut, $dateFin);

        $query = Article::with(['categorie', 'stocks']);

        if ($request->filled('id_article')) {
            $query->where('id', $idArticle);
        } else {
            $query->whereHas('mouvementStocks', function($q) use ($dateDebut, $dateFin) {
                $q->whereBetween('date_mouvement', [$dateDebut, $dateFin]);
            });
        }

        // Filtrer par quantité sur le bon exercice
        if (($qteMin !== null || $qteMax !== null) && $exercice) {
            $query->whereHas('stocks', function($q) use ($qteMin, $qteMax, $exercice) {
                $q->where('id_exercice', $exercice->id);
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
            // Récupérer le stock pour le bon exercice
            $stock = $exercice ? $article->stockPourExercice($exercice->id) : null;

            if ($stock) {
                $rapportArticle = $this->genererRapportCompletArticle($article, $dateDebut, $dateFin, $request, $stock);
                $rapportArticles[] = $rapportArticle;
            } else {
                \Log::warning("Article ID {$article->id} - {$article->libelle} n'a pas d'enregistrement de stock pour l'exercice ID " . ($exercice->id ?? 'N/A'));
            }
        }

        $filterLabels = [
            'date_debut' => $dateDebut->format('d/m/Y'),
            'date_fin' => $dateFin->format('d/m/Y'),
            'article' => 'Tous',
            'qte_min' => $qteMin ?? 'Non spécifié',
            'qte_max' => $qteMax ?? 'Non spécifié',
            'exercice' => $exercice ? $exercice->annee : 'Non déterminé',
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
                    'periode_analysee' => $dateDebut->format('d/m/Y') . ' au ' . $dateFin->format('d/m/Y'),
                    'exercice' => $exercice ? $exercice->annee : 'Non déterminé',
                ]
            ]
        );
    }


    /**
     * Génère le rapport complet d'un article.
     * Le paramètre $stock est maintenant passé directement (déjà filtré par exercice).
     */
    private function genererRapportCompletArticle($article, $dateDebut, $dateFin, Request $request, $stock)
    {
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

        LogJournalisation::create([
            "action"      => "Génération du rapport complet pour l'article ID {$article->id} ({$article->libelle}) du " .
                            Carbon::parse($dateDebut)->format('d/m/Y') . " au " .
                            Carbon::parse($dateFin)->format('d/m/Y'),
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        $tousLesMouvements = MouvementStock::with(['typeMouvement', 'fournisseur', 'employe', 'bureau'])
            ->where('id_Article', $article->id)
            ->orderBy('date_mouvement', 'desc')
            ->get();

        // Récupérer le prix unitaire
        $prixUnitaireArticle = 0;

        if (isset($article->prixUnitaire) && $article->prixUnitaire > 0) {
            $prixUnitaireArticle = $article->prixUnitaire;
        } else {
            $dernierMouvementAvecPrix = MouvementStock::where('id_Article', $article->id)
                ->whereNotNull('prixUnitaire')
                ->where('prixUnitaire', '>', 0)
                ->orderBy('date_mouvement', 'desc')
                ->first();

            if ($dernierMouvementAvecPrix) {
                $prixUnitaireArticle = $dernierMouvementAvecPrix->prixUnitaire;
            }
        }

        // Utiliser le $stock passé en paramètre (déjà filtré par exercice)
        $stockActuel = [
            'cmp' => $stock->cout_moyen_pondere ?? 0,
            'quantite' => $stock->Qte_actuel ?? 0,
            'prix_unitaire' => $prixUnitaireArticle,
            'montant_total' => ($stock->Qte_actuel ?? 0) * $prixUnitaireArticle,
            'date_maj' => $stock->updated_at ?? null
        ];

        // Dernière entrée
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
            $prixUnitaire = $mouvementEntree->prixUnitaire ?? $prixUnitaireArticle;
            $quantite = $mouvementEntree->qte ?? 0;

            $derniereEntree = [
                'date' => Carbon::parse($mouvementEntree->date_mouvement)->format('d/m/Y H:i'),
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire,
                'montant' => $quantite * $prixUnitaire,
                'fournisseur' => $mouvementEntree->fournisseur->nom ?? 'N/A',
                'description' => $mouvementEntree->description ?? '',
                'type_mouvement' => $mouvementEntree->typeMouvement->libelle_type_mouvement ?? 'N/A',
                'dans_periode' => $mouvementEntree->date_mouvement >= $dateDebut &&
                                  $mouvementEntree->date_mouvement <= $dateFin
            ];
        }

        // Dernière sortie
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
            $prixUnitaire = $mouvementSortie->prixUnitaire ?? $prixUnitaireArticle;
            $quantite = $mouvementSortie->qte ?? $mouvementSortie->qteDemande ?? 0;

            $derniereSortie = [
                'date' => Carbon::parse($mouvementSortie->date_mouvement)->format('d/m/Y H:i'),
                'quantite' => $quantite,
                'prix_unitaire' => $prixUnitaire,
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

        // Calculs de synthèse
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
                'categorie' => [
                    'libelle_categorie_article' => $article->categorie->libelle_categorie_article ?? 'N/A'
                ],
                'stock_alerte' => $article->stock_alerte ?? 0,
                'unite_de_mesure' => $article->uniteDeMesure->libelle_unite ?? 'N/A'
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
            'debug' => [
                'type_entree_trouve' => $typeEntree ? $typeEntree->libelle_type_mouvement : 'NON TROUVÉ',
                'type_sortie_trouve' => $typeSortie ? $typeSortie->libelle_type_mouvement : 'NON TROUVÉ',
                'nombre_mouvements_total' => $tousLesMouvements->count(),
                'mouvements_dans_periode' => $tousLesMouvements->whereBetween('date_mouvement', [$dateDebut, $dateFin])->count(),
                'prix_unitaire_source' => $prixUnitaireArticle > 0 ? 'trouvé' : 'non trouvé',
                'stock_exercice_id' => $stock->id_exercice ?? 'N/A',
                'tous_les_mouvements_bruts' => $tousLesMouvements->map(function($mouvement) {
                    return [
                        'id' => $mouvement->id,
                        'date' => $mouvement->date_mouvement,
                        'type' => $mouvement->typeMouvement->libelle_type_mouvement ?? 'N/A',
                        'quantite' => $mouvement->qte,
                        'prix' => $mouvement->prixUnitaire
                    ];
                })
            ]
        ];
    }
}