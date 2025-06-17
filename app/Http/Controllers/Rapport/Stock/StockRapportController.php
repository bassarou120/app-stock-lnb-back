<?php

namespace App\Http\Controllers\Rapport\Stock;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MouvementStock;
use App\Models\Parametrage\TypeMouvement;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf; // Assure-toi d'importer la façade PDF
use App\Models\Article; // Ajouté pour la récupération du libellé de l'article
use App\Models\Parametrage\Fournisseur; // Ajouté pour la récupération du nom du fournisseur
use App\Models\Employe; // Ajouté pour la récupération du nom de l'employé

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
            'typeMouvement'
        ]);

        $query->whereBetween('date_mouvement', [$request->date_debut, $request->date_fin]);

        if ($request->filled('id_type_mouvement')) {
            $query->where('id_type_mouvement', $request->id_type_mouvement);
        }

        if ($request->id_type_rapport === 'entree') {
            $query->with('fournisseur');
            if ($request->filled('id_fournisseur')) {
                $query->where('id_fournisseur', $request->id_fournisseur);
            }
            if ($request->filled('id_Article')) {
                $query->where('id_Article', $request->id_Article);
            }
        } elseif ($request->id_type_rapport === 'sortie') {
            $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();
            $query->with('employe')->where('statut', '=', 'Accordé')->where('id_type_mouvement', $type_mouvement->id);
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
            'fournisseur'
        ]);

        $query->whereBetween('date_mouvement', [$request->date_debut, $request->date_fin]);

        if ($request->filled('id_type_mouvement')) {
            $query->where('id_type_mouvement', $request->id_type_mouvement);
        }

        if ($request->id_type_rapport === 'entree') {
            $query->with('fournisseur');
            if ($request->filled('id_fournisseur')) {
                $query->where('id_fournisseur', $request->id_fournisseur);
            }
            if ($request->filled('id_Article')) {
                $query->where('id_Article', $request->id_Article);
            }
        } elseif ($request->id_type_rapport === 'sortie') {
            $type_mouvement = TypeMouvement::where('libelle_type_mouvement', "Sortie de Stock")->latest()->first();
            $query->with('employe')->where('statut', '=', 'Accordé')->where('id_type_mouvement', $type_mouvement->id);
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

        // Préparer les libellés des filtres pour la vue Blade
        $filterLabels = [
            'date_debut' => \Carbon\Carbon::parse($request->date_debut)->format('d/m/Y'),
            'date_fin' => \Carbon\Carbon::parse($request->date_fin)->format('d/m/Y'),
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

        $pdf = \Pdf::loadView('pdf.rapport.rapport_stock', compact('mouvements', 'reportTypeLabel', 'filterLabels'));

        $filename = 'rapport_stock_' . $request->id_type_rapport . '.pdf';

        return $pdf->download($filename);
    }
}
