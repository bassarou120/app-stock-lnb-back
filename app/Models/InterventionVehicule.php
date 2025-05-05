<?php

namespace App\Models;

use App\Models\Parametrage\Vehicule;
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
    ];

    // Définir la relation avec le modèle Vehicule
    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class, 'vehicule_id');
    }

}
