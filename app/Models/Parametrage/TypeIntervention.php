<?php

namespace App\Models\Parametrage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;


class TypeIntervention extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'libelle_type_intervention',
        'applicable_seul_vehicule',
        'observation',
        'has_expiration_date',
        // 'date_expiration',
    ];

    protected $casts = [
        'applicable_seul_vehicule' => 'boolean',
        'has_expiration_date' => 'boolean', // NOUVEAU: Cast le nouveau champ en booléen
        // 'date_expiration' n'est plus casté ici
    ];
}
