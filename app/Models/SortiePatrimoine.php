<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;

class SortiePatrimoine extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'code_immo',
        'designation_immo',
        'type_immo',
        'valeur',
        'date_sortie',
        'observation',
        'exercice_id',
        'isdeleted'
    ];
}
