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
    public function index()
    {

        // Récupérer l'exercice ouvert
        /*         $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();
        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé.'
            ], 404);
        } */

        $articles = Article::with(['categorie', 'stock'])
            ->where('isdeleted', false)
            ->orderBy('id_exercice', 'desc')
            ->latest()->paginate(1000);
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
        $validator = Validator::make($request->all(), [
            'articles' => 'required|array',
            'articles.*.id_cat' => 'required|exists:categorie_articles,id',
            'articles.*.libelle' => 'required|string|max:255',
            // 'articles.*.code_article' => 'required|string|max:255|unique:articles,code_article',
            'articles.*.description' => 'string|max:255',
            'articles.*.stock_alerte' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $articles = [];

        // Récupérer l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->latest()->first();
        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé.'
            ], 400);
        }

        // Utilisation d'une transaction pour garantir l'intégrité des données
        DB::beginTransaction();
        try {
            foreach ($request->articles as $articleData) {

                // Récupérer le dernier article créé
                $lastArticle = Article::orderBy('id', 'desc')->first();
                $lastNumber = $lastArticle ? (int) substr($lastArticle->code_article, 4, 5) : 0;

                // Incrémenter
                $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);

                // Année (2 derniers chiffres)
                $year = date('y');

                // Générer code article
                $codeArticle = "ART-{$newNumber}-{$year}";

                $article = Article::create([
                    'id_cat' => $articleData['id_cat'],
                    'libelle' => $articleData['libelle'],
                    'code_article' => $codeArticle,
                    'description' => $articleData['description'],
                    'stock_alerte' => $articleData['stock_alerte'],
                    'id_exercice' => $exerciceOuvert->id
                ]);

                // Initialiser l'entrée de stock pour cet article
                Stock::create([
                    'id_Article' => $article->id,
                    'Qte_actuel' => 0,
                    'id_exercice' => $exerciceOuvert->id, // lien stock → exercice
                    //'prix_unitaire' => 0 // pour calcul CMP plus tard
                ]);

                // Ajouter une entrée dans la table article_exercice
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
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Une erreur est survenue',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }

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
        $validator = Validator::make($request->all(), [
            'id_cat' => 'required|exists:categorie_articles,id',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'stock_alerte' => 'required|integer|min:0',
        ]);

        Log::info($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            // Mise à jour de l'article
            $article->update([
                'id_cat' => $request->id_cat,
                'libelle' => $request->libelle,
                'description' => $request->description,
                'stock_alerte' => $request->stock_alerte,
            ]);

            // ======= Calcul CMP début et fin =======
            // CMP début = basé sur le stock initial de l'exercice
            $stockDebut = DB::table('stocks')
                ->where('id_article', $article->id)
                ->where('type', 'entrée') // uniquement les entrées (achats)
                ->select(DB::raw('SUM(qte * prix_unitaire) as total'), DB::raw('SUM(qte) as total_qte'))
                ->first();

            $cmpDebut = ($stockDebut && $stockDebut->total_qte > 0)
                ? round($stockDebut->total / $stockDebut->total_qte, 2)
                : 0;

            // CMP fin = basé sur toutes les entrées pendant l'exercice
            $stockFin = DB::table('stocks')
                ->where('id_article', $article->id)
                ->where('type', 'entrée') // uniquement les entrées
                ->select(DB::raw('SUM(qte * prix_unitaire) as total'), DB::raw('SUM(qte) as total_qte'))
                ->first();

            $cmpFin = ($stockFin && $stockFin->total_qte > 0)
                ? round($stockFin->total / $stockFin->total_qte, 2)
                : 0;

            // Stock actuel
            $stockActuel = DB::table('stocks')
                ->where('id_article', $article->id)
                ->orderBy('id', 'desc')
                ->value('Qte_actuel') ?? 0;

            // Mise à jour de la table article_exercice
            DB::table('article_exercice')
                ->where('id_article', $article->id)
                ->update([
                    'stock_debut_exercice' => $stockDebut->total_qte ?? 0,
                    'stock_fin_exercice' => $stockActuel,
                    'cmp_debut_exercice' => $cmpDebut,
                    'cmp_fin_exercice' => $cmpFin,
                    'updated_at' => now(),
                ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Une erreur est survenue lors de la mise à jour de l\'article.',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }

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
    public function destroy(Article $article)
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

        return new PostResource(true, 'Article supprimé avec succès', null);
    }



    public function imprimer()
    {
        $articles = Article::with(['categorie', 'stock'])
            ->where('isdeleted', false)
            ->get();

        $pdf = Pdf::loadView('pdf.articles', compact('articles'));

        return $pdf->download('etat_du_stock.pdf');
    }

    public function exportArticlesExcel()
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
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $spreadsheet = IOFactory::load($request->file('file'));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $ignoredRows = [];

        // Pré-charger tous les exercices pour éviter des requêtes répétées dans la boucle
        $exercices = Exercice::all()->pluck('id', 'annee');

        // Démarre une transaction pour garantir que toutes les opérations sont réussies ou annulées
        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Ignorer la ligne d'en-tête

                // La nouvelle colonne 'année' est à l'index 5 (la 6ème colonne)
                if (count($row) < 6) {
                    $ignoredRows[] = "Ligne " . ($index + 1) . " ignorée : colonnes insuffisantes (" . count($row) . "). L'année d'exercice est manquante.";
                    continue;
                }

                $annee_exercice = trim($row[5]);

                // Vérifier si l'année est valide
                if (empty($annee_exercice) || !is_numeric($annee_exercice)) {
                    $ignoredRows[] = "Ligne " . ($index + 1) . " ignorée : année invalide ou vide.";
                    continue;
                }

                $annee_exercice = (int) $annee_exercice; // Cast seulement après validation
                $code_article = trim($row[0]);
                $designation_article = trim($row[1]);

                // Vérifier si un article avec le même code ou libellé existe déjà
                $articleExistant = Article::where('code_article', $code_article)
                    ->orWhere('libelle', $designation_article)
                    ->first();

                if ($articleExistant) {
                    $ignoredRows[] = "Ligne " . ($index + 1) . " ignorée : article avec code '$code_article' ou nom '$designation_article' déjà existant.";
                    continue;
                }

                // Vérifier si l'année de l'exercice existe dans la base de données.
                // Si elle n'existe pas, la créer.
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

                $categorie = CategorieArticle::firstOrCreate([
                    'libelle_categorie_article' => trim($row[2])
                ]);

                // Créer l'article avec l'id_exercice récupéré
                $article = Article::create([
                    'id_cat' => $categorie->id,
                    'libelle' => $designation_article,
                    'code_article' => $code_article,
                    'description' => trim($row[3]),
                    'stock_alerte' => trim($row[4]),
                    'id_exercice' => $id_exercice // Ajout de l'id de l'exercice
                ]);

                // Initialiser l'entrée de stock pour cet article
                Stock::create([
                    'id_Article' => $article->id,
                    'Qte_actuel' => 0,
                    'id_exercice' => $id_exercice,
                ]);

                // Ajouter une entrée dans la table article_exercice
                DB::table('article_exercice')->insert([
                    'id_article' => $article->id,
                    'id_exercice' => $id_exercice,
                    'stock_debut_exercice' => 0,
                    'stock_fin_exercice' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'cmp_debut_exercice' => 0,
                    'cmp_fin_exercice' => 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Import terminé avec succès !',
                'ignored' => $ignoredRows
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'importation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
