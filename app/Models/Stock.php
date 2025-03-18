<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = ['id_Article', 'Qte_actuel'];


    // Relation avec le modèle Article
    public function article()
    {
        return $this->belongsTo(Article::class, 'id_Article');
    }
}
