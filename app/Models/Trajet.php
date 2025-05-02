<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MouvementTicket;
use App\Models\Parametrage\Commune;

class Trajet extends Model
{
    use HasFactory;

    protected $fillable = [
        'commune_depart',
        'commune_arriver',
        'trajet_aller_retour',
        'observation',
        'valeur'
    ];


    public function depart()
    {
        return $this->belongsTo(Commune::class, 'commune_depart');
    }

    public function arriver()
    {
        return $this->belongsTo(Commune::class, 'commune_arriver');
    }
}
