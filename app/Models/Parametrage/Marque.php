<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;


class Marque extends Model
{
    use HasFactory, BelongsToTenant;
    protected $fillable = ['libelle'];

}
