<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Parametrage\Employe;
use App\Models\Exercice;
use App\Models\Parametrage\GroupeTypeImmo;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class DemandeImmo extends Model
{
    use HasFactory;
    protected $guarded = [];
    //


    public function exercice()
    {
        return $this->belongsTo(Exercice::class, 'id_exercice');
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class, 'id_employe');
    }
    public function traiteur()
    {
        return $this->belongsTo(Employe::class, 'id_employe');
    }

    public function groupeTypeImmo()
    {
        return $this->belongsTo(GroupeTypeImmo::class, 'id_groupe_type_immo');
    }
    public function immobilisation()
    {
        return $this->belongsTo(Immobilisation::class, 'id_immo');
    }
}
