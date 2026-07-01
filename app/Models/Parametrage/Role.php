<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Traits\BelongsToTenant;


class Role extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'libelle_role',
    ];
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'role_id');
    }
}
