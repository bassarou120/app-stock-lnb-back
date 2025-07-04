<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * Le nom de la table associée au modèle.
     * C'est 'settings' comme vous l'avez confirmé.
     * @var string
     */
    protected $table = 'settings';

    /**
     * Les attributs qui peuvent être massivement assignés.
     * 'key' : la clé unique du paramètre (ex: 'company_name', 'logo_url')
     * 'value' : la valeur du paramètre
     * 'type' : le type de paramètre (ex: 'text', 'image_url')
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    /**
     * Les attributs qui devraient être castés.
     * Utile si 'value' devait être converti automatiquement (ex: en JSON).
     * @var array<string, string>
     */
    protected $casts = [
        // 'value' => 'array', // Exemple si 'value' stockait du JSON
    ];
}
