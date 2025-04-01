<?php

namespace App\Models;

use App\Models\Parametrage\TypeIntervention;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Intervention extends Model
{
    use HasFactory;


    protected $fillable = [
        'immo_id',
        'type_intervention_id',
        'date_intervention',
        'titre',
        'cout',
        'observation',
    ];

    public function immobilisation()
    {
        return $this->belongsTo(Immobilisation::class, 'immo_id');
    }
    public function typeIntervention()
    {
        return $this->belongsTo(TypeIntervention::class, 'type_intervention_id');
    }
}
