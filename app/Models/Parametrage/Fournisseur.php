<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Fournisseur",
 *     title="Fournisseur",
 *     description="Modèle d'un Fournisseur",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="APS BENIN"),
 *     @OA\Property(property="telephone", type="string", example="+229 0198899775"),
 *     @OA\Property(property="adresse", type="string", example="Cotonou/Akpakpa"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-25T14:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-25T14:00:00Z")
 * )
 */

class Fournisseur extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'telephone',
        'adresse',
    ];
}
