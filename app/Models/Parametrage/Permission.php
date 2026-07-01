<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\BelongsToTenant;


class Permission extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'role_id',
        'module_id',
        'fonctionnalite_id',
        'is_active',
    ];

    // Définir les relations avec les autres modèles
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function fonctionnalite()
    {
        return $this->belongsTo(Fonctionnalite::class);
    }
}
