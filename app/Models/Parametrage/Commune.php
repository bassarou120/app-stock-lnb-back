<?php

namespace App\Models\Parametrage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    use HasFactory;
    
    protected $fillable = ['libelle_commune'];
}
