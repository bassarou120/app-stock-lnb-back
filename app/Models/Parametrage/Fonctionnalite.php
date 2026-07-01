<?php

namespace App\Models\Parametrage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;


class Fonctionnalite extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'libelle_fonctionnalite',
        'module_id',
    ];

    // Définir la relation avec le modèle Module
    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
