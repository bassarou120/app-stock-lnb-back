<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\Bureau;
use App\Models\Parametrage\Fournisseur;
use App\Models\Parametrage\GroupeTypeImmo;
use App\Models\Parametrage\SousTypeImmo;
use App\Models\Parametrage\StatusImmo;
use App\Models\Vehicule;



class Immobilisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bureau_id',
        'employe_id',
        'date_mouvement',
        'fournisseur_id',
        'designation',
        'isVehicule',
        'vehicule_id',
        'code',
        'id_groupe_type_immo',
        'id_sous_type_immo',
        'duree_amorti',
        'etat',
        'taux_ammortissement',
        'duree_ammortissement',
        'date_acquisition',
        'date_mise_en_service',
        'observation',
        'id_status_immo',
        'montant_ttc',
    ];

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

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function groupeTypeImmo()
    {
        return $this->belongsTo(GroupeTypeImmo::class, 'id_groupe_type_immo');
    }
    public function sousTypeImmo()
    {
        return $this->belongsTo(SousTypeImmo::class, 'id_sous_type_immo');
    }

    public function statusImmo()
    {
        return $this->belongsTo(StatusImmo::class, 'id_status_immo');
    }
}
