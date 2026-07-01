<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;



class UniteDeMesure extends Model
{
    use HasFactory,BelongsToTenant ;   
    protected $fillable = [
        'libelle',
    ];
}
