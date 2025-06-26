<?php

namespace App\Swagger\Responses;

/**
 * @OA\Schema(
 *     schema="PostResourceInterventionResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Intervention créée avec succès"),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/InterventionVehicule"
 *     )
 * )
 */

 class PostResourceInterventionResponse {}
