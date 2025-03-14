<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class GroupeTypeImmo extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_sous_type_immo',
        'libelle',
        'compte',
    ];

    public function sousTypeImmo()
    {
        return $this->belongsTo(SousTypeImmo::class, 'id_sous_type_immo');
    }
}
