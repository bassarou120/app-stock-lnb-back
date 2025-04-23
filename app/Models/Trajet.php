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
        'MouvementTicket_id', // Assurez-vous que la clé étrangère est dans $fillable si vous utilisez la création massive
        'commune_depart',
        'commune_arriver',
        'trajet_aller_retour',
        'observation',
    ];

    public function mouvementTicket()
    {
        return $this->belongsTo(MouvementTicket::class, 'MouvementTicket_id'); // Utilisation de 'mouvement_ticket_id' (conventionnel)
    }

    public function depart()
    {
        return $this->belongsTo(Commune::class, 'commune_depart');
    }

    public function arriver()
    {
        return $this->belongsTo(Commune::class, 'commune_arriver');
    }
}