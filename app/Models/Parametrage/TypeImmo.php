<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="TypeImmo",
 *     title="TypeImmo",
 *     description="Modèle d'un TypeImmo",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="libelle_typeImmo", type="string", example="Mobilier de Bureau"),
 *     @OA\Property(property="compte", type="integer", example="2444"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-25T14:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-25T14:00:00Z")
 * )
 */

class TypeImmo extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle_typeImmo',
        'compte',
    ];
}
