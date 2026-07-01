<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'logo_path',
        'couleur_primaire',
        'couleur_secondaire',
        'nom_affichage',
    ];
}