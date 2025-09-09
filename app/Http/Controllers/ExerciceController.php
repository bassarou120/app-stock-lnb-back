<?php

namespace App\Http\Controllers;

use App\Models\Exercice;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Carbon\Carbon;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use App\Models\MouvementStock;
use App\Models\ArticleExercice;

class ExerciceController extends Controller
{
    //  Lister tous les exercices
    public function index()
    {
        //$exercices = Exercice::latest()->paginate(100);
        $exercices = Exercice::orderBy('annee', 'desc')->paginate(100);

        return new PostResource(true, 'Liste des exercices', $exercices);
    }

public function articlesExercices()
{
    $exercices = Exercice::with(['articles' => function ($query) {
        $query->select('articles.id', 'libelle', 'code_article');
    }])->get();

    return new PostResource(true, 'Liste des exercices avec leurs articles', $exercices);
}

    //  Créer un exercice
    public function store(Request $request)
    {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after:date_debut',
        ]);

        // Déterminer l'année à partir des dates
        $anneeDebut = date('Y', strtotime($request->date_debut));
        $anneeFin   = date('Y', strtotime($request->date_fin));

        if ($anneeDebut !== $anneeFin) {
            return response()->json([
                'message' => "L'exercice doit être sur une seule année (ex: 01/01/2025 au 31/12/2025)"
            ], 422);
        }

        // 1. Déterminer l'année en cours pour la comparaison
        $anneeActuelle = Carbon::now()->year;

        // 2. Définir le statut par défaut
        // S'il s'agit de l'année en cours, le statut est 'ouvert', sinon il est 'cloture'.
        $statut = ($anneeDebut == $anneeActuelle) ? 'ouvert' : 'cloture';

        // 3. Si le nouvel exercice est "ouvert", fermer tous les autres exercices
        if ($statut === 'ouvert') {
            Exercice::where('statut', 'ouvert')->update(['statut' => 'cloture']);
        }

        // 4. Créer le nouvel exercice avec le statut déterminé
        $exercice = Exercice::create([
            'date_debut' => $request->date_debut,
            'date_fin'   => $request->date_fin,
            'annee'      => $anneeDebut,
            'statut'     => $statut
        ]);

        return new PostResource(true, 'Type exercice créé avec succès', $exercice);
    }

    //  Modifier un exercice
    public function update(Request $request, $id)
    {
        $exercice = Exercice::findOrFail($id);

        $request->validate([
            'date_debut' => 'sometimes|date',
            'date_fin'   => 'sometimes|date|after:date_debut',
            'statut'     => 'in:ouvert,cloture',
        ]);

        $data = $request->all();

        //  recalculer l'année si date_debut ou date_fin changent
        if ($request->has('date_debut') || $request->has('date_fin')) {
            $dateDebut = $request->date_debut ?? $exercice->date_debut;
            $dateFin   = $request->date_fin ?? $exercice->date_fin;

            $anneeDebut = date('Y', strtotime($dateDebut));
            $anneeFin   = date('Y', strtotime($dateFin));

            if ($anneeDebut !== $anneeFin) {
                return response()->json([
                    'message' => "L'exercice doit être sur une seule année"
                ], 422);
            }

            $data['annee'] = $anneeDebut;
        }

        $exercice->update($data);

        return new PostResource(true, 'exercice modifié avec succès', $exercice);
    }

    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:ouvert,cloture',
        ]);

        $exercice = Exercice::findOrFail($id);
        $nouvStatut = $request->input('statut');

        if ($nouvStatut === 'ouvert') {
            Exercice::where('id', '!=', $id)->update(['statut' => 'cloture']);
            $exercice->statut = 'ouvert';
            $exercice->save();
        }

        if ($nouvStatut === 'cloture') {
            // Clôture de l'exercice actuel
            $exercice->statut = 'cloture';
            $exercice->save();

            // Mettre à jour stock_fin_exercice et cmp_fin_exercice
            $articles = DB::table('article_exercice')
                ->where('id_exercice', $exercice->id)
                ->get();

            foreach ($articles as $article) {
                $stock = Stock::where('id_Article', $article->id_article)
                            ->where('id_exercice', $exercice->id)
                            ->first();

                $stock_fin = $stock ? $stock->Qte_actuel : 0;

                $dernierMouvement = MouvementStock::where('id_Article', $article->id_article)
                                    ->where('id_exercice', $exercice->id)
                                    ->latest('date_mouvement')
                                    ->first();

                $cmp_fin = $dernierMouvement ? $dernierMouvement->cout_moyen_pondere : 0;

                DB::table('article_exercice')
                    ->where('id_article', $article->id_article)
                    ->where('id_exercice', $exercice->id)
                    ->update([
                        'stock_fin_exercice' => $stock_fin,
                        'stock_debut_exercice' => $stock_fin,
                        'cmp_fin_exercice' => $cmp_fin,
                        'updated_at' => now(),
                    ]);
            }

            // Créer le nouvel exercice ouvert
            $nouvelExercice = Exercice::create([
                'annee' => $exercice->annee + 1,
                'statut' => 'ouvert',
                'date_debut' => Carbon::create($exercice->annee + 1, 1, 1),
                'date_fin' => Carbon::create($exercice->annee + 1, 12, 31),
            ]);

            // Initialiser article_exercice et stock pour le nouvel exercice
            foreach ($articles as $article) {
                DB::table('article_exercice')->insert([
                    'id_article' => $article->id_article,
                    'id_exercice' => $nouvelExercice->id,
                    'stock_debut_exercice' => $article->stock_fin_exercice,
                    'stock_fin_exercice' => 0,
                    'cmp_debut_exercice' => $article->cmp_fin_exercice,
                    'cmp_fin_exercice' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Stock::create([
                    'id_Article' => $article->id_article,
                    'Qte_actuel' => $article->stock_fin_exercice,
                    'id_exercice' => $nouvelExercice->id,
                ]);
            }

            $exerciceOuvert = $nouvelExercice;
        } else {
            // Cas ouverture manuelle
            $exerciceOuvert = $exercice;
        }

        return response()->json([
            'success' => true,
            'exercice' => $exerciceOuvert,
        ]);
    }



    public function getExerciceOuvert()
    {
        // Récupérer l'exercice ouvert
        $exerciceOuvert = Exercice::where('statut', 'ouvert')->first();

        if (!$exerciceOuvert) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun exercice ouvert trouvé.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'exercice' => $exerciceOuvert,
        ]);
    }

    //  Supprimer un exercice
    public function destroy($id)
    {
        $exercice = Exercice::findOrFail($id);
        $exercice->delete();

        return new PostResource(true, 'Exercice supprimé avec succès', null);
    }
}
