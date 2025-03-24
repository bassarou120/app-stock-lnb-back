<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Parametrage\Fournisseur;


class MouvementStock extends Model
{
    use HasFactory;
    protected $guarded=[];


    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'id_Article');
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseur::class, 'id_fournisseur');
    }

    public function affectation()
{
    return $this->hasOne(AffectationArticle::class, 'id_mouvement');
}
}
