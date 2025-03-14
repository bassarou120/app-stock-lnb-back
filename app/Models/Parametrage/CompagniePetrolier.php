<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompagniePetrolier extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'adresse',
    ];
}
