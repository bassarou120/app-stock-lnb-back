<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Parametrage\TypeIntervention;
use App\Models\Parametrage\Bureau;
use App\Models\Parametrage\Employe;



class Transfert extends Model
{
    use HasFactory;


    protected $fillable = [
        'immo_id',
        'old_bureau_id',
        'old_employe_id',
        'bureau_id',
        'employe_id',
        'date_mouvement',
        'observation',
    ];

    public function immobilisation()
    {
        return $this->belongsTo(Immobilisation::class, 'immo_id');
    }
    public function old_bureau()
    {
        return $this->belongsTo(Bureau::class, 'old_bureau_id');
    }
    public function bureau()
    {
        return $this->belongsTo(Bureau::class, 'bureau_id');
    }
    public function old_employe()
    {
        return $this->belongsTo(Employe::class, 'old_employe_id');
    }
    public function employe()
    {
        return $this->belongsTo(Employe::class, 'employe_id');
    }
}
