<?php

namespace App\Models\Parametrage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *     schema="Bureau",
 *     title="Bureau",
 *     description="Modèle d'un bureau",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="libelle_bureau", type="string", example="Bureau Central"),
 *     @OA\Property(property="valeur", type="string", example="Valeur optionnelle"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-25T14:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-25T14:00:00Z")
 * )
 */

class Bureau extends Model
{
    use HasFactory;
    protected $fillable = [
        'libelle_bureau',
        'valeur',
    ];
}