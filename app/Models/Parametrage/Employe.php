<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employe extends Model
{
    use HasFactory;

    protected $appends = ['fullnameEmploye'];


    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
    ];

    public function getFullnameEmployeAttribute()
{
    return $this->nom . ' ' . $this->prenom;
}




}
