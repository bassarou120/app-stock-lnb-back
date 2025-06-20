<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class UniteDeMesure extends Model
{
    use HasFactory;
    protected $fillable = [
        'libelle',
    ];
}
