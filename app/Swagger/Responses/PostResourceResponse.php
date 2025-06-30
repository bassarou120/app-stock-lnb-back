<?php

namespace App\Swagger\Responses;

/**
 * @OA\Schema(
 *     schema="PostResourceResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Opération réussie"),
 *     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Article"))
 * )
 */
class PostResourceResponse {}