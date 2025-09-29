<?php

namespace App\Http\Controllers;

use App\Models\Exercice;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Carbon\Carbon;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use App\Models\MouvementStock;
use App\Models\MouvementTicket;
use App\Models\ArticleExercice;
use App\Models\Parametrage\CouponTicket;
use App\Models\Parametrage\CompagniePetrolier;
use App\Models\ExerciceMouvementTicket;

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
        }])
        ->orderBy('statut', 'desc') // 'ouvert' will come before 'cloture'
        ->orderBy('annee', 'desc')
        ->get();

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

/*     public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:ouvert,cloture',
        ]);

        $exercice = Exercice::findOrFail($id);
        $nouvStatut = $request->input('statut');

        DB::transaction(function () use ($exercice, $nouvStatut) {
            if ($nouvStatut === 'ouvert') {
                Exercice::where('id', '!=', $exercice->id)->update(['statut' => 'cloture']);
                $exercice->statut = 'ouvert';
                $exercice->save();
            }

            if ($nouvStatut === 'cloture') {
                // Clôturer l'exercice actuel
                $exercice->statut = 'cloture';
                $exercice->save();

                // Créer le nouvel exercice pour l'année suivante
                $nouvelExercice = Exercice::create([
                    'annee' => $exercice->annee + 1,
                    'statut' => 'ouvert',
                    'date_debut' => Carbon::create($exercice->annee + 1, 1, 1),
                    'date_fin' => Carbon::create($exercice->annee + 1, 12, 31),
                ]);

                // --- GESTION DES ARTICLES (inchangé) ---
                $articlesExercice = DB::table('article_exercice')
                    ->where('id_exercice', $exercice->id)
                    ->get();

                foreach ($articlesExercice as $article) {
                    // ... (Votre logique de clôture d'articles reste inchangée)
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
                            'cmp_fin_exercice' => $cmp_fin,
                            'updated_at' => now(),
                        ]);

                    DB::table('article_exercice')->insert([
                        'id_article' => $article->id_article,
                        'id_exercice' => $nouvelExercice->id,
                        'stock_debut_exercice' => $stock_fin,
                        'stock_fin_exercice' => 0,
                        'cmp_debut_exercice' => $cmp_fin,
                        'cmp_fin_exercice' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Stock::create([
                        'id_Article' => $article->id_article,
                        'Qte_actuel' => $stock_fin,
                        'id_exercice' => $nouvelExercice->id,
                    ]);
                }

                // --- GESTION DES MOUVEMENTS DE TICKETS (CORRIGÉE) ---
                // 1. Récupérer tous les coupons de tickets et toutes les compagnies pétrolières
                $allCouponTickets = CouponTicket::all();
                $allCompagnies = CompagniePetrolier::all();

                // 2. Boucler sur chaque combinaison pour créer les enregistrements
                foreach ($allCouponTickets as $couponTicket) {
                    foreach ($allCompagnies as $compagnie) {
                        // Calculer le stock final pour la combinaison (coupon, compagnie) pour l'exercice qui se clôture.
                        // Pour cela, on agrège les quantités des mouvements de tickets de cet exercice.
                        $stockFinal = MouvementTicket::where('exercice_id', $exercice->id)
                            ->where('coupon_ticket_id', $couponTicket->id)
                            ->where('compagnie_petrolier_id', $compagnie->id)
                            ->sum('qte');

                        // Créer l'enregistrement dans la table de jointure pour le nouvel exercice
                        ExerciceMouvementTicket::create([
                            'exercice_id' => $nouvelExercice->id,
                            'coupon_ticket_id' => $couponTicket->id,
                            'compagnie_petrolier_id' => $compagnie->id,
                            'qte_actuel' => $stockFinal,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'exercice' => Exercice::find($id),
        ]);
    } */


    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:ouvert,cloture',
        ]);

        $exercice = Exercice::findOrFail($id);
        $nouvStatut = $request->input('statut');

        DB::transaction(function () use ($exercice, $nouvStatut) {
            if ($nouvStatut === 'ouvert') {
                // Logique pour l'ouverture
                Exercice::where('id', '!=', $exercice->id)->update(['statut' => 'cloture']);
                $exercice->statut = 'ouvert';
                $exercice->save();
            }

            if ($nouvStatut === 'cloture') {
                // Clôturer l'exercice actuel
                $exercice->statut = 'cloture';
                $exercice->save();

                // Créer le nouvel exercice pour l'année suivante
                $nouvelExercice = Exercice::create([
                    'annee' => $exercice->annee + 1,
                    'statut' => 'ouvert',
                    'date_debut' => Carbon::create($exercice->annee + 1, 1, 1),
                    'date_fin' => Carbon::create($exercice->annee + 1, 12, 31),
                ]);

                // --- GESTION DES ARTICLES ---
                $articlesExercice = DB::table('article_exercice')
                    ->where('id_exercice', $exercice->id)
                    ->get();

                foreach ($articlesExercice as $article) {
                    // 1. Récupérer le STOCK et le CMP de FIN 2025 depuis la table Stock
                    $stock = Stock::where('id_Article', $article->id_article)
                        ->where('id_exercice', $exercice->id)
                        ->first();
                    
                    // Récupération de la quantité de fin d'exercice
                    $stock_fin = $stock ? $stock->Qte_actuel : 0;
                    
                    // CORRECTION CLÉ : Récupération du CMP de fin d'exercice
                    // On prend le CMP final enregistré dans la table Stock, car c'est la source de vérité après l'entrée.
                    $cmp_fin = $stock ? $stock->cout_moyen_pondere : 0; 


                    // Mettre à jour l'enregistrement d'article_exercice pour l'ancienne année
                    DB::table('article_exercice')
                        ->where('id_article', $article->id_article)
                        ->where('id_exercice', $exercice->id)
                        ->update([
                            'stock_fin_exercice' => $stock_fin,
                            'cmp_fin_exercice' => $cmp_fin,
                            'updated_at' => now(),
                        ]);

                    // Créer l'enregistrement d'article_exercice pour la nouvelle année
                    DB::table('article_exercice')->insert([
                        'id_article' => $article->id_article,
                        'id_exercice' => $nouvelExercice->id,
                        'stock_debut_exercice' => $stock_fin,
                        'stock_fin_exercice' => 0,
                        'cmp_debut_exercice' => $cmp_fin,
                        'cmp_fin_exercice' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Créer l'enregistrement Stock pour le nouvel exercice
                    Stock::create([
                        'id_Article' => $article->id_article,
                        'Qte_actuel' => $stock_fin,
                        'cout_moyen_pondere' => $cmp_fin, // Optionnel, mais bonne pratique de transférer le CMP ici aussi
                        'id_exercice' => $nouvelExercice->id,
                    ]);
                }

                // --- GESTION DES MOUVEMENTS DE TICKETS (inchangé) ---
                $allCouponTickets = CouponTicket::all();
                $allCompagnies = CompagniePetrolier::all();

                foreach ($allCouponTickets as $couponTicket) {
                    foreach ($allCompagnies as $compagnie) {
                        $stockFinal = MouvementTicket::where('exercice_id', $exercice->id)
                            ->where('coupon_ticket_id', $couponTicket->id)
                            ->where('compagnie_petrolier_id', $compagnie->id)
                            ->sum('qte');

                        ExerciceMouvementTicket::create([
                            'exercice_id' => $nouvelExercice->id,
                            'coupon_ticket_id' => $couponTicket->id,
                            'compagnie_petrolier_id' => $compagnie->id,
                            'qte_actuel' => $stockFinal,
                        ]);
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'exercice' => Exercice::find($id),
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
