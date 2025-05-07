<?php

namespace App\Models;

use App\Models\Vehicule;
use App\Models\Parametrage\TypeIntervention;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
