<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StatusImmo extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle_status_immo',
    ];
}
