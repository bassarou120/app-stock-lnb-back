<?php

namespace App\Models;

use App\Models\Parametrage\Role;
use App\Models\Parametrage\Employe;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use App\Traits\UUID;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    use UUID;

    const TABLE_NAME = "users";
    public const ID = 'id';
    public const NAME = 'name';
    public const SURNAME = 'surname';
    public const EMAIL = 'email';
    public const PHONE = 'phone';
    public const PHOTO = 'photo';
    public const SEXE = 'sexe';
    public const PASSWORD = 'password';
    public const REMEMBER_TOKEN = 'remember_token';
    public const ACTIF = 'active';
    public const ROLE = 'role_id';
    public const LAST_ACTIVITY = 'last_activity';
    public const EMPLOYE = 'employe_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        self::NAME,
        self::EMAIL,
        self::PHONE,
        self::SEXE,
        self::PASSWORD,
        self::ACTIF,
        self::PHOTO,
        self::SURNAME,
        self::ROLE,
        self::LAST_ACTIVITY,
        self::EMPLOYE,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        User::PASSWORD,
        User::REMEMBER_TOKEN,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean', // <-- Assurez-vous que c'est là
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, self::ROLE);
    }

    // Relation avec l'employé
    public function employe()
    {
        // Assurez-vous que self::EMPLOYE (qui est 'employe_id') est bien la clé étrangère
        // dans la table 'users' qui pointe vers la clé primaire 'id' de la table 'employes'.
        return $this->belongsTo(Employe::class, self::EMPLOYE);
    }
}
