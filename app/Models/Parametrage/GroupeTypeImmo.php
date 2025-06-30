<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Groupe_type_immo",
 *     title="Groupe_type_immo",
 *     description="Modèle d'un Groupe_type_immo",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="libelle", type="string", example="Table Parakou"),
 *     @OA\Property(property="compte", type="integer", example="24440005"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-25T14:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-25T14:00:00Z")
 * )
 */

class GroupeTypeImmo extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'compte',
    ];


}
