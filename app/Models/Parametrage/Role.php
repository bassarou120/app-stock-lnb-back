<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle_role',
    ];
    public function permissions()
    {
        return $this->hasMany(Permission::class, 'role_id');
    }
}
