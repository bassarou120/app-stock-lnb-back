<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\Factories\HasFactory;

class SousTypeImmo extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_type_immo',
        'libelle',
        'compte',
    ];

    public function typeImmo()
    {
        return $this->belongsTo(TypeImmo::class, 'id_type_immo');
    }
}
