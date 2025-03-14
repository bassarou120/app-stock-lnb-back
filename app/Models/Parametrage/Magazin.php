<?php

namespace App\Models\Parametrage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Magazin extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle_magazin',
        'localisation',
    ];
}
