<?php

namespace App\Models\Parametrage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeIntervention extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle_type_intervention',
        'applicable_seul_vehicule',
        'observation',
    ];
}
