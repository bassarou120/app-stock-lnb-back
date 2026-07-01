<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;

class LogJournalisation extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'action',
        'ip_address',
        'date_action',
        'user_agent',
        'user_id',
        'user_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
