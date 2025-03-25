<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class GroupeTypeImmo extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'compte',
    ];


}
