<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\Bureau;


class AffectationArticle extends Model
{
    use HasFactory;


    protected $fillable = [
        'id_article',
        'id_type_affectation',
        'id_bureau',
        'description',
        'id_employe',
        'id_mouvement'
    ];

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'id_employe');
    }

    public function bureau()
    {
        return $this->belongsTo(Bureau::class, 'id_bureau');
    }


}
