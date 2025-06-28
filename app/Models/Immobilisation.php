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

/**
 * @OA\Schema(
 *     schema="Immobilisation",
 *     type="object",
 *     title="Immobilisation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="designation", type="string", example="Imprimante Canon"),
 *     @OA\Property(property="code", type="string", example="IMMO-2025-001"),
 *     @OA\Property(property="montant_ttc", type="integer", example=150000),
 *     @OA\Property(property="date_acquisition", type="string", format="date", example="2025-01-01"),
 *     @OA\Property(property="date_mise_en_service", type="string", format="date", example="2025-02-01"),
 *     @OA\Property(property="date_mouvement", type="string", format="date", example="2025-02-10"),
 *     @OA\Property(property="duree_amorti", type="integer", example=36),
 *     @OA\Property(property="duree_ammortissement", type="integer", example=36),
 *     @OA\Property(property="taux_ammortissement", type="integer", example=10),
 *     @OA\Property(property="etat", type="string", example="Bon état"),
 *     @OA\Property(property="observation", type="string", example="Affectée au bureau 1"),
 *     @OA\Property(property="isVehicule", type="boolean", example=false),
 *     @OA\Property(property="vehicule_id", type="integer", nullable=true),
 *     @OA\Property(property="id_groupe_type_immo", type="integer"),
 *     @OA\Property(property="id_sous_type_immo", type="integer"),
 *     @OA\Property(property="id_status_immo", type="integer"),
 *     @OA\Property(property="fournisseur_id", type="integer", nullable=true),
 *     @OA\Property(property="employe_id", type="integer", nullable=true),
 *     @OA\Property(property="bureau_id", type="integer", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="bureau",
 *         ref="#/components/schemas/Bureau"
 *     ),
 *     @OA\Property(
 *         property="groupe_type_immo",
 *         ref="#/components/schemas/Groupe_type_immo"
 *     ),
 *     @OA\Property(
 *         property="sous_type_immo",
 *         ref="#/components/schemas/Sous_type_immo"
 *     ),
 *     @OA\Property(
 *         property="status_immo",
 *         ref="#/components/schemas/Status_immo"
 *     ),
 *     @OA\Property(
 *         property="employe",
 *         ref="#/components/schemas/Employe"
 *     ),
 *     @OA\Property(
 *         property="fournisseur",
 *         ref="#/components/schemas/Fournisseur"
 *     ),
 * )
 */


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
