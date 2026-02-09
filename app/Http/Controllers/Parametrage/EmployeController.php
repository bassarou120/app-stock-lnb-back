<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Employe;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\LogJournalisation;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


/**
 * @OA\Tag(
 *     name="Personnel",
 *     description="Gestion du Personnel "
 * )
 */




class EmployeController extends Controller
{
    // Afficher la liste des Employe

    /**
     * @OA\Get(
     *     path="/api/employes",
     *     tags={"Personnel"},
     *     summary="Liste du Personnel",
     *     @OA\Response(
     *         response=200,
     *         description="Succès",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste des Personnel"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Employe")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $employes = Employe::latest()->where('isdeleted', false)->paginate(500);
        LogJournalisation::create([
            "action"      => "Affichage de la liste des employés",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Liste des employés', $employes);
    }

    // Créer un nouveau Employe

  /**
 * @OA\Post(
 *     path="/api/employes",
 *     tags={"Personnel"},
 *     summary="Créer un Personnel",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"nom", "prenom"},
 *             @OA\Property(property="nom", type="string"),
 *             @OA\Property(property="prenom", type="string"),
 *             @OA\Property(property="telephone", type="string"),
 *             @OA\Property(property="email", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Personnel créé avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Personnel créé avec succès"),
 *             @OA\Property(property="data", ref="#/components/schemas/Employe")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Erreur de validation"
 *     )
 * )
 */

/*     public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $employe = Employe::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'email' => $request->email,
        ]);

        return new PostResource(true, 'Employe créé avec succès', $employe);
    } */

    public function store(Request $request)
    {
        // 0. Nettoyage des entrées : Convertir les chaînes vides en NULL
        // Ceci est crucial pour la règle 'nullable' et pour la cohérence de la BDD.
        $request->merge([
            'telephone' => $request->telephone === '' ? null : $request->telephone,
            'email' => $request->email === '' ? null : $request->email,
        ]);

        // 1. Définition des règles de validation
        $rules = [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',

            'telephone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('employes', 'telephone')->where(function ($query) {
                    return $query->where('isdeleted', false);
                }),
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('employes', 'email')->where(function ($query) {
                    return $query->where('isdeleted', false);
                }),
            ],
        ];

        // 2. Définition des messages personnalisés en français
        $messages = [
            // Règle d'unicité pour le téléphone
            'telephone.unique' => 'Le numéro de téléphone que vous avez saisi est déjà utilisé par un autre employé.',
            'telephone.max'    => 'Le numéro de téléphone ne peut dépasser 20 caractères.',

            // Règle d'unicité pour l'email
            'email.unique'     => "L'adresse email est déjà associée à un autre compte employé. Veuillez en saisir une nouvelle.",
            'email.email'      => 'Veuillez saisir une adresse email valide.',

            // Messages génériques
            'nom.required'     => 'Le nom est obligatoire.',
            'prenom.required'  => 'Le prénom est obligatoire.',
        ];

        // 3. Création du validateur avec les règles ET les messages
        $validator = Validator::make($request->all(), $rules, $messages);

        // 4. Gestion de l'échec de la validation
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 5. Création de l'employé
        $employe = Employe::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            // Les valeurs sont déjà NULL si elles étaient vides grâce au merge
            'telephone' => $request->telephone,
            'email' => $request->email,
        ]);

        LogJournalisation::create([
            "action"      => "Création d'un employé",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        // 6. Succès
        return new PostResource(true, 'Employé créé avec succès', $employe);
    }



 /**
 * @OA\Put(
 *     path="/api/employes/{id}",
 *     tags={"Personnel"},
 *     summary="Mettre à jour un Personnel",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'Personnel",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"nom", "prenom"},
 *             @OA\Property(property="nom", type="string"),
 *             @OA\Property(property="prenom", type="string"),
 *             @OA\Property(property="telephone", type="string"),
 *             @OA\Property(property="email", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Personnel mis à jour avec succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Personnel mis à jour avec succès"),
 *             @OA\Property(property="data", ref="#/components/schemas/Employe")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Erreur de validation"
 *     )
 * )
 */


    public function update(Request $request, Employe $employe)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $employe->update([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'telephone' => $request->telephone,
            'email' => $request->email,
        ]);
        LogJournalisation::create([
            "action"      => "Mise à jour d'un employé",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);

        return new PostResource(true, 'Employé mis à jour avec succès', $employe);
    }

    // Supprimer un Employe

    /**
     * @OA\Delete(
     *     path="/api/employes/{id}",
     *     tags={"Personnel"},
     *     summary="Supprimer un Personnel",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'Personnel",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employé supprimé avec succès"
     *     )
     * )
     */

    public function destroy(Employe $employe, Request $request)
    {
        $employe->isdeleted = true;
        $employe->save();
        LogJournalisation::create([
            "action"      => "Suppression d'un employé",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return new PostResource(true, 'Employe supprimé avec succès', null);
    }


    /**
     * @OA\Get(
     *     path="/api/employes/pdf",
     *     tags={"Personnel"},
     *     summary="Télécharger la liste des employés en PDF",
     *     @OA\Response(
     *         response=200,
     *         description="Fichier PDF téléchargé",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     )
     * )
     */
    public function imprimer(Request $request)
    {
        $employes = Employe::all()->where('isdeleted', false);

        $pdf = Pdf::loadView('pdf.employes', compact('employes'));
        LogJournalisation::create([
            "action"      => "Impression de la liste des employés",
            "ip_address"  => request()->ip(),
            "user_agent"  => request()->userAgent(),
            'user_id'    => $request->user()->id,
            'user_name'   => $request->user()->name,
            "date_action" => now()
        ]);
        return $pdf->download('liste_personnels.pdf');
    }
}
