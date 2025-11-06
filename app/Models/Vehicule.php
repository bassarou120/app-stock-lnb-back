<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\Modele;
use App\Models\Parametrage\Marque;
use App\Models\Parametrage\GroupeTypeImmo;
use App\Models\Parametrage\SousTypeImmo;
use App\Models\Parametrage\Bureau;
use App\Models\Parametrage\Fournisseur;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\StatusImmo;


class Vehicule extends Model
{
    use HasFactory;

    protected $fillable = [
        'marque_id',
        'modele_id',
        'immatriculation',
        'numero_chassis',
        'kilometrage',
        'date_mise_en_service',
        'puissance',
        'places_assises',
        'energie',
        'nbreannee_amortissement',
        'date_amortissement',
        'carte_grise',
        'id_sous_type_immo',
        'id_groupe_type_immo',
        //
        'bureau_id',
        'fournisseur_id',
        'etat',
        'observation',
        'id_status_immo',
        'date_acquisition',
        'montant_ttc',
        'taux_ammortissement',
        'code'
    ];

    public function marque()
    {
        return $this->belongsTo(Marque::class, 'marque_id');
    }

    public function modele()
    {
        return $this->belongsTo(Modele::class, 'modele_id');
    }

    public function sousTypeImmo() {
        return $this->belongsTo(SousTypeImmo::class, 'id_sous_type_immo');
    }

    public function groupeTypeImmo() {
        return $this->belongsTo(GroupeTypeImmo::class, 'id_groupe_type_immo');
    }

    public function bureau()
    {
        return $this->belongsTo(Bureau::class);
    }

    public function employe()
    {
        return $this->belongsTo(Employe::class);
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function statusImmo()
    {
        return $this->belongsTo(StatusImmo::class, 'id_status_immo');
    }
}
