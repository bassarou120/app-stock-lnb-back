<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogJournalisation extends Model
{
    use HasFactory;

    protected $fillable = [
        'action',
        'ip_address',
        'date_action',
        'user_agent',
        'user_id'
    ];
}
