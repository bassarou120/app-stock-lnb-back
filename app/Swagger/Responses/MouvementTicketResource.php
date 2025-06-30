<?php

namespace App\Swagger\Responses;

/**
 * @OA\Schema(
 *     schema="MouvementTicketResource",
 *     type="object",
 *     title="Réponse d'un MouvementTicket",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Mouvement enregistré avec succès"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="vehicule_id", type="integer", example=12),
 *         @OA\Property(property="compagnie_petrolier_id", type="integer", example=5),
 *         @OA\Property(property="coupon_ticket_id", type="integer", example=3),
 *         @OA\Property(property="employe_id", type="integer", example=7),
 *         @OA\Property(property="qte", type="integer", example=10),
 *         @OA\Property(property="date", type="string", format="date", example="2025-06-26"),
 *         @OA\Property(property="kilometrage", type="integer", example=15200),
 *         @OA\Property(property="objet", type="string", example="Déplacement mission"),
 *         @OA\Property(property="description", type="string", example="Sortie de tickets pour carburant"),
 *         @OA\Property(property="reference", type="string", example="MVT-ABC1234567"),
 *         @OA\Property(property="commune_depart", type="integer", example=1),
 *         @OA\Property(property="commune_arriver", type="integer", example=2),
 *         @OA\Property(property="trajet_aller_retour", type="boolean", example=true),
 *         @OA\Property(property="created_at", type="string", format="date-time"),
 *         @OA\Property(property="updated_at", type="string", format="date-time")
 *     )
 * )
 */
class MouvementTicketResource {}