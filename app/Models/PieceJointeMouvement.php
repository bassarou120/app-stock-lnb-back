<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PieceJointeMouvement extends Model
{
    use HasFactory;

    // Spécifier la table associée
    protected $table = 'piece_jointe_mouvement';

    protected $fillable = [
        'url',
        'id_mouvement_stock',
    ];
}
