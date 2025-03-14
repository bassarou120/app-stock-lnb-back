<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeAffectation extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle_type_affectation',
        'valeur',
    ];
}
