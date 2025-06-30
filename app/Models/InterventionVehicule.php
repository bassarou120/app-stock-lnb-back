<?php

namespace App\Models;

use App\Models\Vehicule;
use App\Models\Parametrage\TypeIntervention;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="InterventionVehicule",
 *     type="object",
 *     title="InterventionVehicule",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="vehicule_id", type="integer", example=2),
 *     @OA\Property(property="titre", type="string", example="Réparation moteur"),
 *     @OA\Property(property="montant", type="number", format="float", example=85000),
 *     @OA\Property(property="observation", type="string", example="Pièce changée"),
 *     @OA\Property(property="date_intervention", type="string", format="date", example="2025-07-01"),
 *     @OA\Property(property="date_expiration", type="string", format="date", example="2025-12-01"),
 *     @OA\Property(property="type_intervention_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */


class InterventionVehicule extends Model
{
    use HasFactory;

    protected $table = 'intervention_vehicules';

    protected $fillable = [
        'vehicule_id',
        'titre',
        'montant',
        'observation',
        'date_intervention',
        'type_intervention_id', // Ajout de la clé étrangère
        'date_expiration',
    ];

    // AJOUTÉ: Gérer date_expiration comme une date Carbon
    protected $casts = [
        'date_intervention' => 'date',
        'date_expiration' => 'date', // Convertit automatiquement en instance Carbon
    ];

    // Définir la relation avec le modèle Vehicule
    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id');
    }

    // Nouvelle relation avec TypeIntervention
    public function typeIntervention(): BelongsTo
    {
        return $this->belongsTo(TypeIntervention::class, 'type_intervention_id');
    }

}
