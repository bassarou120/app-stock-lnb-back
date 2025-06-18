<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Parametrage\Fournisseur;
use App\Models\Parametrage\Employe;
use App\Models\Parametrage\Bureau;
use App\Models\Parametrage\TypeMouvement;


class MouvementStock extends Model
{
    use HasFactory;
    protected $guarded = [];


    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'id_Article');
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'id_fournisseur');
    }

    public function typeMouvement()
    {
        return $this->belongsTo(TypeMouvement::class, 'id_type_mouvement');
    }

    public function affectation()
    {
        return $this->hasOne(AffectationArticle::class, 'id_mouvement');
    }
    public function employe()
    {
        return $this->belongsTo(Employe::class, 'id_employe');
    }
    public function bureau()
    {
        return $this->belongsTo(Bureau::class, 'bureau_id');
    }
    public function piecesJointes()
    {
        return $this->hasMany(PieceJointeMouvement::class, 'id_mouvement_stock');
    }
}
