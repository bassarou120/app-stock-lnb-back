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
use App\Models\LogJournalisation;
use Illuminate\Support\Facades\Auth;

class ExerciceController extends Controller
{
    // Liste des exercices
    public function index(Request $request)
    {
        try {
            $exercices = Exercice::orderBy('annee', 'desc')->paginate(100);

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Consultation liste des exercices',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Liste des exercices', $exercices);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur consultation exercices : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }


    public function articlesExercices(Request $request)
    {
        try {
            $exercices = Exercice::with(['articles' => function ($query) {
                $query->select('articles.id', 'libelle', 'code_article');
            }])
            ->orderBy('statut', 'desc')
            ->orderBy('annee', 'desc')
            ->get();

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Consultation exercices + articles',
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Liste des exercices avec articles', $exercices);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur consultation exercices articles : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }


    // Créer un exercice
    public function store(Request $request)
    {
        try {
            $request->validate([
                'date_debut' => 'required|date',
                'date_fin'   => 'required|date|after:date_debut',
            ]);

            $anneeDebut = date('Y', strtotime($request->date_debut));
            $anneeFin   = date('Y', strtotime($request->date_fin));

            if ($anneeDebut !== $anneeFin) {
                return response()->json([
                    'message' => "L'exercice doit être sur une seule année"
                ], 422);
            }

            $anneeActuelle = Carbon::now()->year;
            $statut = ($anneeDebut == $anneeActuelle) ? 'ouvert' : 'cloture';

            if ($statut === 'ouvert') {
                Exercice::where('statut', 'ouvert')->update(['statut' => 'cloture']);
            }

            $exercice = Exercice::create([
                'date_debut' => $request->date_debut,
                'date_fin'   => $request->date_fin,
                'annee'      => $anneeDebut,
                'statut'     => $statut
            ]);

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Création exercice ' . $anneeDebut,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Type exercice créé avec succès', $exercice);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur création exercice : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }


    // Modifier un exercice
    public function update(Request $request, $id)
    {
        try {
            $exercice = Exercice::findOrFail($id);

            $request->validate([
                'date_debut' => 'sometimes|date',
                'date_fin'   => 'sometimes|date|after:date_debut',
                'statut'     => 'in:ouvert,cloture',
            ]);

            $data = $request->all();

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

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Modification exercice ID=' . $id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Exercice modifié avec succès', $exercice);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur modification exercice : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }


    // Changer statut
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
        LogJournalisation::create([
            'action'     => "Changement de statut de l'exercice ID: {$exercice->id} vers '{$nouvStatut}'",
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            'date_action'=> now(),
        ]);

        return response()->json([
            'success' => true,
            'exercice' => Exercice::find($id),
        ]);
    }


    // Supprimer un exercice
    public function destroy(Request $request, $id)
    {
        try {
            $exercice = Exercice::findOrFail($id);
            $exercice->delete();

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Suppression exercice ID=' . $id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Exercice supprimé avec succès', null);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur suppression exercice : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => $request->user()->id,
                'user_name'   => $request->user()->name,
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }
    
}
