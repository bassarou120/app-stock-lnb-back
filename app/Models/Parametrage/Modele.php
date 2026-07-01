<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;


class Modele extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'libelle_modele',
    ];
}
