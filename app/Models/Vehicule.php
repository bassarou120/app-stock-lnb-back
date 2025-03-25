<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\Modele;
use App\Models\Parametrage\Marque;


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
    ];

    public function marque()
    {
        return $this->belongsTo(Marque::class, 'marque_id');
    }

    public function modele()
    {
        return $this->belongsTo(Modele::class, 'modele_id');
    }
}
