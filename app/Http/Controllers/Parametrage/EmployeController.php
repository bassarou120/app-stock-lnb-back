<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Parametrage\Employe;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

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
    public function index()
    {
        $employes = Employe::latest()->paginate(500);
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

    public function store(Request $request)
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

    public function destroy(Employe $employe)
    {
        $employe->delete();
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
    public function imprimer()
    {
        $employes = Employe::all();

        $pdf = Pdf::loadView('pdf.employes', compact('employes'));

        return $pdf->download('liste_personnels.pdf');
    }
}
