<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Status_immo",
 *     title="Status_immo",
 *     description="Modèle d'un Status_immo",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="libelle_status_immo", type="string", example="En cours"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-06-25T14:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-06-25T14:00:00Z")
 * )
 */

class StatusImmo extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle_status_immo',
    ];
}
