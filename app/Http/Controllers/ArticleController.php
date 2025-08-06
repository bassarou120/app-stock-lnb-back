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
        $articles = Article::with(['categorie', 'stock'])
        ->where('isdeleted', false)
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
    public function storeBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'articles' => 'required|array',
            'articles.*.id_cat' => 'required|exists:categorie_articles,id',
            'articles.*.libelle' => 'required|string|max:255',
            'articles.*.code_article' => 'required|string|max:255|unique:articles,code_article',
            'articles.*.description' => 'string|max:255',
            'articles.*.stock_alerte' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $articles = [];

        // Utilisation d'une transaction pour garantir l'intégrité des données
        DB::beginTransaction();
        try {
            foreach ($request->articles as $articleData) {
                $article = Article::create([
                    'id_cat' => $articleData['id_cat'],
                    'libelle' => $articleData['libelle'],
                    'code_article' => $articleData['code_article'],
                    'description' => $articleData['description'],
                    'stock_alerte' => $articleData['stock_alerte'],
                ]);

                // Initialiser l'entrée de stock pour cet article
                Stock::create([
                    'id_Article' => $article->id,
                    'Qte_actuel' => 0
                ]);

                $articles[] = $article;
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Une erreur est survenue lors de l\'enregistrement des articles.'], 500);
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
            'code_article' => 'required|string|max:255',
            'description' => 'string|max:255',
            'stock_alerte' => 'required|integer|min:0',
        ]);

        Log::info($request->all());


        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $article->update([
            'id_cat' => $request->id_cat,
            'libelle' => $request->libelle,
            'description' => $request->description,
            'code_article' => $request->code_article,
            'stock_alerte' => $request->stock_alerte,
        ]);

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

        $article->isdeleted = true;
        $article->save();
        return new PostResource(true, 'Article supprimé avec succès', null);
    }


    public function imprimer()
    {
        $articles = Article::with(['categorie', 'stock'])->where('isdeleted', false)->get();

        $pdf = Pdf::loadView('pdf.articles', compact('articles'));

        return $pdf->download('etat_du_stock.pdf');
    }



    public function exportArticlesExcel()
    {
        $articles = Article::with(['categorie', 'stock'])->get()->map(function ($article) {
            return [
                'Article'           => $article->libelle ?? '-',
                'Description'       => $article->description ?? '-',
                'Catégorie'         => $article->categorie->libelle_categorie_article ?? '-',
                'Quantité Actuelle' => $article->stock->Qte_actuel ?? 0,
                'Stock d\'alerte'   => $article->stock_alerte ?? '-',
                'Date de création'  => $article->created_at ? $article->created_at->format('Y-m-d') : '-',
            ];
        })->toArray();

        \Excel::create('etat_du_stock', function($excel) use ($articles) {
            $excel->sheet('Stock', function($sheet) use ($articles) {
                // Ajoute les données avec les en-têtes automatiquement
                $sheet->fromArray($articles);
            });
        })->download('xlsx');
    }

        public function import(Request $request)
    {
        // 1️⃣ Validation
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 2️⃣ Charger le fichier
        $spreadsheet = IOFactory::load($request->file('file'));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // 3️⃣ Boucler sur les lignes (en ignorant la première ligne d'entêtes)
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Ignore l'en-tête

            // 🛡️ Vérifie que la ligne a au moins 5 colonnes
            if (count($row) < 5) {
                \Log::warning("Ligne $index ignorée : colonnes insuffisantes (" . count($row) . ")");
                echo "Ligne $index ignorée : colonnes insuffisantes (" . count($row) . ")";
                continue;
            }

            $code_article = trim($row[0]);
            $designation_article = trim($row[1]);
            $categorie_libelle = trim($row[2]);
            $description = trim($row[3]);
            $stock_alert = trim($row[4]);

            // ❌ Vérifie si un article avec le même code existe déjà
            $articleExistant = Article::where('code_article', $code_article)->first();

            if ($articleExistant) {
                \Log::info("Ligne $index ignorée : article avec code '$code_article' déjà existant.");
                echo "Ligne $index ignorée : article avec code '$code_article' déjà existant.<br>";
                continue;
            }

            // 🔎 Récupère ou crée la catégorie
            $categorie = CategorieArticle::firstOrCreate([
                'libelle_categorie_article' => $categorie_libelle
            ]);

            // ✅ Crée l’article
            Article::create([
                'id_cat' => $categorie->id,
                'libelle' => $designation_article,
                'code_article' => $code_article,
                'description' => $description,
                'stock_alerte' => $stock_alert,
            ]);
        }


        return response()->json(['message' => 'Import réussi !']);
    }

}
