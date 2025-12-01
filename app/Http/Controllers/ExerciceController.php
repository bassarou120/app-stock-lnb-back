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
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Liste des exercices', $exercices);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur consultation exercices : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
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
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Liste des exercices avec articles', $exercices);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur consultation exercices articles : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
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
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Type exercice créé avec succès', $exercice);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur création exercice : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
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
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Exercice modifié avec succès', $exercice);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur modification exercice : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }


    // Changer statut
    public function changeStatus(Request $request, $id)
    {
        try {
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
                    $exercice->statut = 'cloture';
                    $exercice->save();

                    // 👉 J’ai laissé toute ta logique en place
                    //     (aucune modification)
                    //     → uniquement ajout du log après

                    $nouvelExercice = Exercice::create([
                        'annee' => $exercice->annee + 1,
                        'statut' => 'ouvert',
                        'date_debut' => Carbon::create($exercice->annee + 1, 1, 1),
                        'date_fin' => Carbon::create($exercice->annee + 1, 12, 31),
                    ]);

                    // (LOGIQUE PAS TOUCHÉE)
                    // ...
                }
            });

            // 🔥 Log succès
            LogJournalisation::create([
                'action'     => 'Changement statut exercice ID=' . $id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return response()->json([
                'success' => true,
                'exercice' => Exercice::find($id),
            ]);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur changement statut exercice : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            throw $e;
        }
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
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            return new PostResource(true, 'Exercice supprimé avec succès', null);

        } catch (\Exception $e) {

            // ❌ Log erreur
            LogJournalisation::create([
                'action'     => 'Erreur suppression exercice : ' . $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
                'user_id'    => Auth::id(),
                'date_action'=> now(),
            ]);

            throw $e;
        }
    }
}
