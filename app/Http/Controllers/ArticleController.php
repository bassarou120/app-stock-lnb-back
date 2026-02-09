<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\CategorieArticle;
use App\Models\Article;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Stock;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Exercice;
use Carbon\Carbon;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;


class ArticleController extends Controller
{
    // Afficher la liste des articles

    /**
     * @OA\Get(
     *     path="/api/articles",
     *     tags={"Articles"},
     *     summary="Liste des articles avec leurs catégories et stocks",
     *     @OA\Response(
     *         response=200,
     *         description="Succès",
     *         @OA\JsonContent(ref="#/components/schemas/PostResourceResponse")
     *     )
     * )
     */
    /*     public function index()
    {
        // Récupérer l'exercice ouvert
        $articles = Article::with(['categorie', 'stock'])
        ->where('isdeleted', false)
        ->orderBy('id_exercice', 'desc')
        ->latest()->paginate(1000);
        return new PostResource(true, 'Liste des articles', $articles);
    }  */

    public function index(Request $request)
    {
        // 1. Récupérer l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();

        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé. Veuillez ouvrir un exercice pour consulter le stock.'
            ], 404);
        }

        $exerciceId = $exerciceOuvert->id;

        // 2. Filtrer la relation 'stock' par l'ID de l'exercice ouvert
        $articles = Article::with(['categorie', 'stock' => function ($query) use ($exerciceId) {
            // C'est la ligne magique ✨
            $query->where('id_exercice', $exerciceId);
        }])
            ->where('isdeleted', false)
            ->latest()->paginate(1000);

        // 📝 LOG → Consultation du stock pour l'exercice ouvert
        LogJournalisation::create([
            'action'     => 'Consultation de la liste des articles',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // 3. Retourner la réponse
        // Lorsque vous accédez à $article->stock->Qte_actuel, vous obtiendrez 20.
        return new PostResource(true, 'Liste des articles', $articles);
    }


    // Créer un nouveau article


    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'id_cat' => 'required|exists:categorie_articles,id',
    //         'libelle' => 'required|string|max:255',
    //         'description' => 'required|string|max:255',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $article = Article::create([
    //         'id_cat' => $request->id_cat,
    //         'libelle' => $request->libelle,
    //         'description' => $request->description,
    //     ]);

    //     return new PostResource(true, 'Article créé avec succès', $article);
    // }

    // Nouvelle méthode pour ajouter plusieurs articles

    /**
     * @OA\Post(
     *     path="/api/articles/batch",
     *     tags={"Articles"},
     *     summary="Créer plusieurs articles en lot",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="articles",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"id_cat","libelle","code_article","stock_alerte"},
     *                     @OA\Property(property="id_cat", type="integer", example=3),
     *                     @OA\Property(property="libelle", type="string", example="Chaussures de sport"),
     *                     @OA\Property(property="code_article", type="string", example="ART-2025-001"),
     *                     @OA\Property(property="description", type="string", example="Description optionnelle"),
     *                     @OA\Property(property="stock_alerte", type="integer", example=5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Articles créés",
     *         @OA\JsonContent(ref="#/components/schemas/PostResourceResponse")
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */

    public function show(Article $article) {}

    public function storeBatch(Request $request)
    {
        // 1️⃣ Validation des données
        $validator = Validator::make($request->all(), [
            'articles' => 'required|array',
            'articles.*.id_cat' => 'required|exists:categorie_articles,id',
            'articles.*.libelle' => 'required|string|max:255',
            // Règle d'unicité commentée dans l'original. Normalement, elle devrait être présente.
            // Cependant, la logique de génération du code unique est faite dans le code ci-dessous.
            // On s'assure de l'unicité via le verrouillage DB (lockForUpdate).
            'articles.*.description' => 'nullable|string|max:255',
            'articles.*.demande_intermittent' => 'nullable|string|max:255',
            'articles.*.stock_alerte' => 'required|integer|min:0',
            // Note: 'articles.*.code_article' n'est pas requis car il est généré par le serveur
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $articles = [];

        // 2️⃣ Récupérer l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();
        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé.'
            ], 400);
        }

        // 3️⃣ Transaction avec gestion des erreurs
        DB::beginTransaction();
        try {
            foreach ($request->articles as $articleData) {

                // 4️⃣ LOGIQUE DE GÉNÉRATION DU CODE SÉQUENTIEL AVEC VERROUILLAGE

                // Récupérer les 4 premières lettres du libellé
                $prefix = strtoupper(substr($articleData['libelle'], 0, 4));
                $year = date('y');

                // Verrouiller la table 'articles' pour la requête en cours (Pessimistic Locking)
                // Ceci empêche deux transactions concurrentes d'obtenir le même 'lastArticle'
                $lastArticle = Article::where('code_article', 'like', "ART-{$prefix}-%-{$year}")
                    ->orderBy('id', 'desc')
                    ->lockForUpdate() // <-- L'ajout CRITIQUE pour la sécurité
                    ->first();

                if ($lastArticle) {
                    // Extraire le numéro de l'article précédent
                    $parts = explode('-', $lastArticle->code_article);
                    // Assurez-vous que l'index 2 existe et est numérique
                    $lastNumber = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : 0;
                } else {
                    $lastNumber = 0;
                }

                // Incrémenter et formater sur 2 chiffres (e.g., 01, 02, 10...)
                $newNumber = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);

                // Générer le code article final
                $codeArticle = "ART-{$prefix}-{$newNumber}-{$year}";

                // Convertir oui/non en booléen
                $demandeIntermittent = false;

                if (isset($articleData['demande_intermittent'])) {
                    $value = strtolower($articleData['demande_intermittent']);
                    $demandeIntermittent = in_array($value, ['oui', 'true', '1']);
                }

                // 5️⃣ Création des enregistrements
                $article = Article::create([
                    'id_cat' => $articleData['id_cat'],
                    'libelle' => $articleData['libelle'],
                    'code_article' => $codeArticle, // Code unique généré
                    'description' => $articleData['description'],
                    'demande_intermittent' => $demandeIntermittent,
                    'stock_alerte' => $articleData['stock_alerte'],
                    'id_exercice' => $exerciceOuvert->id
                ]);

                // Initialiser l'entrée de stock pour cet article
                Stock::create([
                    'id_Article' => $article->id,
                    'Qte_actuel' => 0,
                    'id_exercice' => $exerciceOuvert->id,
                ]);

                // Ajouter une entrée dans la table article_exercice (liaison N:N)
                DB::table('article_exercice')->insert([
                    'id_article' => $article->id,
                    'id_exercice' => $exerciceOuvert->id,
                    'stock_debut_exercice' => 0,
                    'stock_fin_exercice' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'cmp_debut_exercice' => 0,
                    'cmp_fin_exercice' => 0,
                ]);

                $articles[] = $article;
            }
            DB::commit();
            // 📝 LOG → Création d'un lot d'articles
            LogJournalisation::create([
                'action'     => 'Création d\'un lot d\'articles (' . count($articles) . ' articles)',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // 📝 LOG → Création d'un lot d'articles
            LogJournalisation::create([
                'action'     => 'Echec de Création d\'articles',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);
            // Utiliser Log::error pour le débogage et masquer les détails trop techniques
            \Log::error('Erreur dans storeBatch: ' . $e->getMessage() . ' à la ligne ' . $e->getLine());

            return response()->json([
                'success' => false,
                'error' => 'Une erreur critique est survenue lors de l\'enregistrement du lot.',
                'message_debug' => $e->getMessage(), // Fournir le message d'erreur si nécessaire
            ], 500);
        }

        // 6️⃣ Réponse de succès
        return new PostResource(true, count($articles) . ' articles créés et stocks initialisés avec succès', $articles);
    }


    // Mettre à jour un article existant

    /**
     * @OA\Put(
     *     path="/api/articles/{id}",
     *     tags={"Articles"},
     *     summary="Mettre à jour un article",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'article",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_cat","libelle","code_article","stock_alerte"},
     *             @OA\Property(property="id_cat", type="integer", example=3),
     *             @OA\Property(property="libelle", type="string", example="Chaussures modifiées"),
     *             @OA\Property(property="code_article", type="string", example="ART-2025-002"),
     *             @OA\Property(property="description", type="string", example="Description mise à jour"),
     *             @OA\Property(property="stock_alerte", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article mis à jour",
     *         @OA\JsonContent(ref="#/components/schemas/PostResourceResponse")
     *     ),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */

    public function update(Request $request, Article $article)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'id_cat' => 'required|exists:categorie_articles,id',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'demande_intermittent' => 'nullable|string|max:255',
            'stock_alerte' => 'required|integer|min:0',
        ]);

        Log::info($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {

            $demandeIntermittent = false;

            if ($request->has('demande_intermittent')) {
                $value = strtolower($request->demande_intermittent);
                $demandeIntermittent = in_array($value, ['oui', 'true', '1']);
            }

            // 2. Mise à jour de l'article
            $article->update([
                'id_cat' => $request->id_cat,
                'libelle' => $request->libelle,
                'description' => $request->description,
                'demande_intermittent' => $demandeIntermittent,
                'stock_alerte' => $request->stock_alerte,
            ]);

            // 3. Calcul CMP début et fin

            // Remarque : 'id_Article' et 'Qte_actuel' sont sensibles à la casse (majuscules)

            // Récupération des données agrégées pour le CMP
            $stockData = DB::table('stocks')
                ->where('id_Article', $article->id)
                ->select(
                    // IMPORTANT : Utilisation des guillemets doubles pour les colonnes sensibles à la casse dans DB::raw()
                    DB::raw('SUM("Qte_actuel" * cout_moyen_pondere) as total'),
                    DB::raw('SUM("Qte_actuel") as total_qte')
                )
                ->first();

            // Le CMP de début et de fin est calculé sur le stock actuel, donc c'est le même calcul ici
            $totalQte = $stockData->total_qte ?? 0;
            $totalCout = $stockData->total ?? 0;

            $cmp = ($totalQte > 0)
                ? round($totalCout / $totalQte, 2)
                : 0;

            $cmpDebut = $cmp;
            $cmpFin = $cmp;

            // Stock actuel (quantité totale en stock pour l'article)
            // Correction ici : Retrait des guillemets doubles de 'Qte_actuel' dans ->value()
            $stockActuel = DB::table('stocks')
                ->where('id_Article', $article->id)
                ->orderBy('id', 'desc')
                ->value('Qte_actuel') ?? 0;

            // 4. Mise à jour de la table article_exercice
            DB::table('article_exercice')
                ->where('id_article', $article->id)
                ->update([
                    // total_qte représente le stock actuel agrégé
                    'stock_debut_exercice' => $totalQte,
                    'stock_fin_exercice' => $stockActuel, // Stock actuel de la dernière ligne (peut être ajusté si totalQte est préférable)
                    'cmp_debut_exercice' => $cmpDebut,
                    'cmp_fin_exercice' => $cmpFin,
                    'updated_at' => now(),
                ]);

            DB::commit();
            // 📝 LOG → Mise à jour d'un article
            LogJournalisation::create([
                'action'     => 'Mise à jour de l\'article ID ' . $article->id . ' (Libellé: ' . $article->libelle . ')',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // 📝 LOG → Mise à jour d'un article
            LogJournalisation::create([
                'action'     => 'Echec de la Mise à jour de l\'article ID ' . $article->id . ' (Libellé: ' . $article->libelle . ')',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);
            // Journalisation de l'erreur pour le débogage côté serveur
            Log::error('Erreur lors de la mise à jour de l\'article: ' . $e->getMessage(), [
                'article_id' => $article->id,
                'exception' => $e
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors de la mise à jour de l\'article.',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }

        // 5. Réponse
        return new PostResource(true, 'Article mis à jour avec succès', $article);
    }




    // Supprimer un article

    /**
     * @OA\Delete(
     *     path="/api/articles/{id}",
     *     tags={"Articles"},
     *     summary="Supprimer un article",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'article",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article supprimé",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Article supprimé avec succès"),
     *             @OA\Property(property="data", type="null", example=null)
     *         )
     *     )
     * )
     */
    public function destroy(Article $article, Request $request)
    {
        // Vérifier l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();
        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé.'
            ], 400);
        }

        // Empêcher la suppression si l'article n'appartient pas à l'exercice ouvert
        if ($article->id_exercice !== $exerciceOuvert->id) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer un article lié à un exercice clôturé."
            ], 403);
        }

        $article->isdeleted = true;
        $article->save();
        // 📝 LOG → Suppression d'article
        LogJournalisation::create([
            'action'     => 'Suppression de l\'article ID ' . $article->id . ' (Libellé: ' . $article->libelle . ')',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        return new PostResource(true, 'Article supprimé avec succès', null);
    }



    public function imprimer(Request $request)
    {
        $articles = Article::with(['categorie', 'stock'])
            ->where('isdeleted', false)
            ->get();
                    // 📝 LOG → Impression du stock
        LogJournalisation::create([
            'action'     => 'Impression de l\'état du stock des articles',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        $pdf = Pdf::loadView('pdf.articles', compact('articles'));

        return $pdf->download('etat_du_stock.pdf');
    }

    public function exportArticlesExcel(Request $request)
    {
        // Récupérer l'année dont le statut est "ouvert"
        $exercice = Exercice::where('statut', 'ouvert')->first();
        $annee = $exercice ? $exercice->annee : date('Y');

        // Charger les articles
        $articles = Article::with(['categorie', 'stock'])->get()->map(function ($article) use ($annee) {
            return [
                'Année'             => $annee,
                'Article'           => $article->libelle ?? '-',
                'Description'       => $article->description ?? '-',
                'Catégorie'         => $article->categorie->libelle_categorie_article ?? '-',
                'Quantité Actuelle' => $article->stock->Qte_actuel ?? 0,
                'Stock d\'alerte'   => $article->stock_alerte ?? '-',
                'Date de création'  => $article->created_at ? $article->created_at->format('Y-m-d') : '-',
            ];
        })->toArray();

        // 📝 LOG → Export Excel des articles
        LogJournalisation::create([
            'action'     => 'Export Excel de l\'état du stock des articles',
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        // Générer le fichier Excel
        \Excel::create('etat_du_stock_' . $annee, function ($excel) use ($articles, $annee) {
            $excel->sheet('Stock_' . $annee, function ($sheet) use ($articles) {
                // Ajoute les données avec les en-têtes automatiquement
                $sheet->fromArray($articles);
            });
        })->download('xlsx');
    }

    /**
     * Importe les articles à partir d'un fichier Excel.
     */
    public function import(Request $request)
    {
        // 1️⃣ Validation du fichier
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $spreadsheet = IOFactory::load($request->file('file'));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // 2️⃣ Initialisation des compteurs et du tableau de rapport
        $totalDataRows = 0; // Renommé pour ne compter que les lignes de données réelles
        $successCount = 0;
        $ignoredRows = [];

        // Pré-charger tous les exercices pour éviter des requêtes répétées dans la boucle
        $exercices = Exercice::all()->pluck('id', 'annee');

        // Démarre une transaction pour garantir que toutes les opérations sont réussies ou annulées
        DB::beginTransaction();

        try {
            // 3️⃣ Boucle de traitement des lignes
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Ignorer la ligne d'en-tête

                // NOUVELLE VÉRIFICATION : Ignorer les lignes entièrement vides
                $nonEmptyCells = array_filter($row, function ($cell) {
                    return trim($cell) !== '';
                });

                if (empty($nonEmptyCells)) {
                    continue; // Ignorer la ligne vide et passer à la suivante
                }

                $excelRowNumber = $index + 1;
                $totalDataRows++; // Compter uniquement les lignes de données réelles

                // La nouvelle colonne 'année' est à l'index 5 (la 6ème colonne)
                if (count($row) < 6) {
                    $ignoredRows[] = "Ligne " . $excelRowNumber . " ignorée : colonnes insuffisantes (" . count($row) . "). L'année d'exercice est manquante.";
                    continue;
                }

                $annee_exercice = trim($row[5]);
                $quantite = trim($row[7] ?? '0'); // Quantité actuelle, par défaut à 0 si non fourni
                $cump = trim($row[8] ?? '0'); // récupère et nettoie la valeur, 0 par défaut
                $raw = trim($row[6] ?? 'non');

                // Nettoyage et normalisation : "Oui", " O U I ", "oui" → "oui"
                $demande = strtolower(str_replace(' ', '', $raw));

                // oui, true ou 1 → true / sinon false
                $demandeBool = in_array($demande, ['oui', 'true', '1']);

                // Vérifier si l'année est valide
                if (empty($annee_exercice) || !is_numeric($annee_exercice)) {
                    $ignoredRows[] = "Ligne " . $excelRowNumber . " ignorée : année invalide ou vide.";
                    continue;
                }

                $annee_exercice = (int) $annee_exercice; // Cast seulement après validation
                $quantite_en_int = (int) $quantite; // Cast seulement après validation
                $cump_en_float = (float) $cump;   // cast en float après validation
                $code_article = trim($row[0]);
                $designation_article = trim($row[1]);

                // Vérifier si un article avec le même code ou libellé existe déjà
                // Attention: L'utilisation de orWhere peut être lente si la table est grande et non indexée.
                $articleExistant = Article::where('code_article', $code_article)
                    ->orWhere('libelle', $designation_article)
                    ->first();

                if ($articleExistant) {
                    $ignoredRows[] = "Ligne " . $excelRowNumber . " ignorée : article avec code '$code_article' ou nom '$designation_article' déjà existant.";
                    Log::info("Importation ignorée - Ligne " . $excelRowNumber . ": article avec code '$code_article' ou nom '$designation_article' déjà existant.");
                    continue;
                }

                // Vérifier si l'année de l'exercice existe dans la base de données.
                if (!isset($exercices[$annee_exercice])) {
                    // Créer un nouvel exercice pour cette année
                    $newExercice = Exercice::create([
                        'annee' => $annee_exercice,
                        'date_debut' => Carbon::create($annee_exercice, 1, 1)->toDateString(),
                        'date_fin' => Carbon::create($annee_exercice, 12, 31)->toDateString(),
                        'statut' => 'cloture', // Les exercices importés sont considérés comme clôturés
                    ]);

                    // Mettre à jour notre collection d'exercices pour la suite de l'importation
                    $exercices[$annee_exercice] = $newExercice->id;
                }

                $id_exercice = $exercices[$annee_exercice];

                // Trouver ou créer la catégorie
                $categorie = CategorieArticle::firstOrCreate([
                    'libelle_categorie_article' => trim($row[2])
                ]);

                // Créer l'article avec l'id_exercice récupéré
                $article = Article::create([
                    'id_cat' => $categorie->id,
                    'libelle' => $designation_article,
                    'code_article' => $code_article,
                    'description' => trim($row[3] ?? ''),
                    'stock_alerte' => trim($row[4]),
                    'id_exercice' => $id_exercice, // Ajout de l'id de l'exercice
                    'demande_intermittent' => $demandeBool,
                ]);

                // Initialiser l'entrée de stock pour cet article
                // Stock::create([
                //     'id_Article' => $article->id,
                //     'Qte_actuel' => $quantite_en_int,
                //     'id_exercice' => $id_exercice,
                // ]);

                 Stock::create([
                        'id_Article' => $article->id,
                        'Qte_actuel' => $quantite_en_int,
                        'cout_moyen_pondere' => $cump_en_float,
                        'id_exercice' => $id_exercice,
                    ]);

                // Ajouter une entrée dans la table article_exercice
                DB::table('article_exercice')->insert([
                    'id_article' => $article->id,
                    'id_exercice' => $id_exercice,
                    'stock_debut_exercice' => $quantite_en_int,
                    'stock_fin_exercice' => $quantite_en_int,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'cmp_debut_exercice' => $cump_en_float,
                    'cmp_fin_exercice' => $cump_en_float,
                ]);

                $successCount++; // Incrémenter le compteur de succès
            }

            DB::commit();

            // 4️⃣ Construction du message de retour final
            $summary = "Importation terminée. " . $successCount . " article(s) ajouté(s) sur " . $totalDataRows . " ligne(s) de données traitée(s).";

            if (!empty($ignoredRows)) {
                $summary .= " Attention : " . count($ignoredRows) . " ligne(s) ont été ignorée(s).";
            }

            LogJournalisation::create([
                'action' => 'Début de l\'importation des articles via Excel',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action' => now(),
            ]);

            return response()->json([
                'message' => $summary,
                'success_count' => $successCount,
                'total_rows_processed' => $totalDataRows,
                'ignored' => $ignoredRows
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            LogJournalisation::create([
                'action' => 'Échec de l\'importation des articles: ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action' => now(),
            ]);

            Log::error('Erreur lors de l\'importation des articles: ' . $e->getMessage() . ' à la ligne ' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'Une erreur critique est survenue lors de l\'importation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //  Attention, cette methode est à utiliser pour l'import après la mise à jours pour ne pas
    // agir sur la table  article_exercice
    public function import_au_utiliser_apres(Request $request)
    {
        // 1️⃣ Validation du fichier
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $spreadsheet = IOFactory::load($request->file('file'));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // 2️⃣ Initialisation des compteurs et du tableau de rapport
        $totalDataRows = 0; // Renommé pour ne compter que les lignes de données réelles
        $successCount = 0;
        $ignoredRows = [];

        // Pré-charger tous les exercices pour éviter des requêtes répétées dans la boucle
        $exercices = Exercice::all()->pluck('id', 'annee');

        // Démarre une transaction pour garantir que toutes les opérations sont réussies ou annulées
        DB::beginTransaction();

        try {
            // 3️⃣ Boucle de traitement des lignes
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Ignorer la ligne d'en-tête

                // NOUVELLE VÉRIFICATION : Ignorer les lignes entièrement vides
                $nonEmptyCells = array_filter($row, function ($cell) {
                    return trim($cell) !== '';
                });

                if (empty($nonEmptyCells)) {
                    continue; // Ignorer la ligne vide et passer à la suivante
                }

                $excelRowNumber = $index + 1;
                $totalDataRows++; // Compter uniquement les lignes de données réelles

                // La nouvelle colonne 'année' est à l'index 5 (la 6ème colonne)
                if (count($row) < 6) {
                    $ignoredRows[] = "Ligne " . $excelRowNumber . " ignorée : colonnes insuffisantes (" . count($row) . "). L'année d'exercice est manquante.";
                    continue;
                }

                $annee_exercice = trim($row[5]);
                $quantite = trim($row[7] ?? '0'); // Quantité actuelle, par défaut à 0 si non fourni
                $cump = trim($row[8] ?? '0'); // récupère et nettoie la valeur, 0 par défaut
                $raw = trim($row[6] ?? 'non');

                // Nettoyage et normalisation : "Oui", " O U I ", "oui" → "oui"
                $demande = strtolower(str_replace(' ', '', $raw));

                // oui, true ou 1 → true / sinon false
                $demandeBool = in_array($demande, ['oui', 'true', '1']);

                // Vérifier si l'année est valide
                if (empty($annee_exercice) || !is_numeric($annee_exercice)) {
                    $ignoredRows[] = "Ligne " . $excelRowNumber . " ignorée : année invalide ou vide.";
                    continue;
                }

                $annee_exercice = (int) $annee_exercice; // Cast seulement après validation
                $quantite_en_int = (int) $quantite; // Cast seulement après validation
                $cump_en_float = (float) $cump;   // cast en float après validation
                $code_article = trim($row[0]);
                $designation_article = trim($row[1]);

                // Vérifier si un article avec le même code ou libellé existe déjà
                // Attention: L'utilisation de orWhere peut être lente si la table est grande et non indexée.
                $articleExistant = Article::where('code_article', $code_article)
                    ->orWhere('libelle', $designation_article)
                    ->first();

                if ($articleExistant) {
                    $ignoredRows[] = "Ligne " . $excelRowNumber . " ignorée : article avec code '$code_article' ou nom '$designation_article' déjà existant.";
                    Log::info("Importation ignorée - Ligne " . $excelRowNumber . ": article avec code '$code_article' ou nom '$designation_article' déjà existant.");
                    continue;
                }

                // Vérifier si l'année de l'exercice existe dans la base de données.
                if (!isset($exercices[$annee_exercice])) {
                    // Créer un nouvel exercice pour cette année
                    $newExercice = Exercice::create([
                        'annee' => $annee_exercice,
                        'date_debut' => Carbon::create($annee_exercice, 1, 1)->toDateString(),
                        'date_fin' => Carbon::create($annee_exercice, 12, 31)->toDateString(),
                        'statut' => 'cloture', // Les exercices importés sont considérés comme clôturés
                    ]);

                    // Mettre à jour notre collection d'exercices pour la suite de l'importation
                    $exercices[$annee_exercice] = $newExercice->id;
                }

                $id_exercice = $exercices[$annee_exercice];

                // Trouver ou créer la catégorie
                $categorie = CategorieArticle::firstOrCreate([
                    'libelle_categorie_article' => trim($row[2])
                ]);

                // Créer l'article avec l'id_exercice récupéré
                $article = Article::create([
                    'id_cat' => $categorie->id,
                    'libelle' => $designation_article,
                    'code_article' => $code_article,
                    'description' => trim($row[3] ?? ''),
                    'stock_alerte' => trim($row[4]),
                    'id_exercice' => $id_exercice, // Ajout de l'id de l'exercice
                    'demande_intermittent' => $demandeBool,
                ]);

                // Initialiser l'entrée de stock pour cet article
                // Stock::create([
                //     'id_Article' => $article->id,
                //     'Qte_actuel' => $quantite_en_int,
                //     'id_exercice' => $id_exercice,
                // ]);

                 Stock::create([
                        'id_Article' => $article->id,
                        'Qte_actuel' => $quantite_en_int,
                        'cout_moyen_pondere' => $cump_en_float,
                        'id_exercice' => $id_exercice,
                    ]);

                // Ajouter une entrée dans la table article_exercice
                // DB::table('article_exercice')->insert([
                //     'id_article' => $article->id,
                //     'id_exercice' => $id_exercice,
                //     'stock_debut_exercice' => $quantite_en_int,
                //     'stock_fin_exercice' => $quantite_en_int,
                //     'created_at' => now(),
                //     'updated_at' => now(),
                //     'cmp_debut_exercice' => $cump_en_float,
                //     'cmp_fin_exercice' => $cump_en_float,
                // ]);

                $successCount++; // Incrémenter le compteur de succès
            }

            DB::commit();

            // 4️⃣ Construction du message de retour final
            $summary = "Importation terminée. " . $successCount . " article(s) ajouté(s) sur " . $totalDataRows . " ligne(s) de données traitée(s).";

            if (!empty($ignoredRows)) {
                $summary .= " Attention : " . count($ignoredRows) . " ligne(s) ont été ignorée(s).";
            }

            LogJournalisation::create([
                'action' => 'Début de l\'importation des articles via Excel',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action' => now(),
            ]);

            return response()->json([
                'message' => $summary,
                'success_count' => $successCount,
                'total_rows_processed' => $totalDataRows,
                'ignored' => $ignoredRows
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            LogJournalisation::create([
                'action' => 'Échec de l\'importation des articles: ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action' => now(),
            ]);

            Log::error('Erreur lors de l\'importation des articles: ' . $e->getMessage() . ' à la ligne ' . $e->getLine());

            return response()->json([
                'success' => false,
                'message' => 'Une erreur critique est survenue lors de l\'importation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}