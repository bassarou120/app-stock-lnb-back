<?php

namespace App\Swagger\Responses;

/**
 * @OA\Schema(
 *     schema="PostResourceImmobilisationResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(
 *         property="data",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Immobilisation")
 *     )
 * )
 */


class PostResourceImmobilisationResponse {}
