<?php

namespace App\Http\Controllers\Parametrage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
    use App\Models\Parametrage\Bureau;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;

//Attention ! Attention ! Attention ! Attention ! Attention ! Attention !
//Update & Destroy demandent "bureaux" et non bureau
//Voir les paramètres des fonctions update et destroy

class BureauController extends Controller
{
    // Afficher la liste des bureaux

    /**
 * @OA\Get(
 *     path="/api/bureaux",
 *     tags={"Bureaux"},
 *     summary="Liste des bureaux",
 *     @OA\Response(
 *         response=200,
 *         description="Succès",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Liste des bureaux"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/Bureau")
 *             )
 *         )
 *     )
 * )
 */
    public function index()
    {
        $bureaux = Bureau::latest()->paginate(100);
        return new PostResource(true, 'Liste des bureaux', $bureaux);
    }

    // Créer un nouveau bureau

    /**
 * @OA\Post(
 *     path="/api/bureaux",
 *     tags={"Bureaux"},
 *     summary="Créer un bureau",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"libelle_bureau"},
 *             @OA\Property(property="libelle_bureau", type="string", example="Bureau des RH"),
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Bureau créé",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Bureau créé avec succès"),
 *             @OA\Property(property="data", ref="#/components/schemas/Bureau")
 *         )
 *     ),
 *     @OA\Response(response=422, description="Erreur de validation")
 * )
 */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'libelle_bureau' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $bureau = Bureau::create([
            'libelle_bureau' => $request->libelle_bureau,
            'valeur' => $request->valeur,
        ]);

        return new PostResource(true, 'Bureau créé avec succès', $bureau);
    }

    // Mettre à jour un bureau existant

    /**
 * @OA\Put(
 *     path="/api/bureaux/{id}",
 *     tags={"Bureaux"},
 *     summary="Mettre à jour un bureau",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du bureau à modifier",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"libelle_bureau"},
 *             @OA\Property(property="libelle_bureau", type="string", example="Bureau mis à jour"),
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Bureau mis à jour",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Bureau mis à jour avec succès"),
 *             @OA\Property(property="data", ref="#/components/schemas/Bureau")
 *         )
 *     ),
 *     @OA\Response(response=422, description="Erreur de validation")
 * )
 */
    public function update(Request $request, Bureau $bureaux)
    {
        $validator = Validator::make($request->all(), [
            'libelle_bureau' => 'required|string|max:255',
            'valeur' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $bureaux->update([
            'libelle_bureau' => $request->libelle_bureau,
            'valeur' => $request->valeur,
        ]);

        return new PostResource(true, 'Bureau mis à jour avec succès', $bureaux);
    }

    // Supprimer un bureau

    /**
 * @OA\Delete(
 *     path="/api/bureaux/{id}",
 *     tags={"Bureaux"},
 *     summary="Supprimer un bureau",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID du bureau à supprimer",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Bureau supprimé",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Bureau supprimé avec succès"),
 *             @OA\Property(property="data", type="null", example=null)
 *         )
 *     )
 * )
 */
    public function destroy(Bureau $bureaux)
    {
        $bureaux->delete();
        return new PostResource(true, 'Bureau supprimé avec succès', null);
    }
}