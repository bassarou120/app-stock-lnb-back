<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class CouponTicket extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'libelle',
        'valeur',
    ];
}
