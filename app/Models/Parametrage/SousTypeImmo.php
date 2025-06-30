<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Sous_type_immo",
 *     title="Sous_type_immo",
 *     description="Modèle d'un Sous_type_immo",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="id_type_immo", type="integer"),
 *     @OA\Property(property="libelle", type="string", example="Mobilier de Bureau Parakou"),
 *     @OA\Property(property="compte", type="string", example="24440005"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-25T14:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-25T14:00:00Z"),
 *     @OA\Property(
 *         property="typeImmo",
 *         ref="#/components/schemas/Bureau"
 *     ),
 * )
 */

class SousTypeImmo extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_type_immo',
        'libelle',
        'compte',
    ];

    public function typeImmo()
    {
        return $this->belongsTo(TypeImmo::class, 'id_type_immo');
    }
}