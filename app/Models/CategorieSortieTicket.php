<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;


class CategorieSortieTicket extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = ['libelle'];
}
