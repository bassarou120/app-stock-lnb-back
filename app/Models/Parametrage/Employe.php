<?php

namespace App\Models\Parametrage;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="EmployeResource",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="nom", type="string", example="Doe"),
 *         @OA\Property(property="prenom", type="string", example="John"),
 *         @OA\Property(property="telephone", type="string", example="+22912345678"),
 *         @OA\Property(property="email", type="string", example="john.doe@example.com"),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     )
 * )
 */
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