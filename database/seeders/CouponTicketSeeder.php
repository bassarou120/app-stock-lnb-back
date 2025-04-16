<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Parametrage\CouponTicket;


class CouponTicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $couponsTickets = [
            [
                'libelle' => 'Coupon de 1000 F',
                'valeur' => '1000'
            ],
            [
                'libelle' => 'Coupon de 2000 F',
                'valeur' => '2000'
            ],
            [
                'libelle' => 'Coupon de 3000 F',
                'valeur' => '3000'
            ],
            [
                'libelle' => 'Coupon de 4000 F',
                'valeur' => '4000'
            ],
            [
                'libelle' => 'Coupon de 5000 F',
                'valeur' => '5000'
            ],
            [
                'libelle' => 'Coupon de 6000 F',
                'valeur' => '6000'
            ],
            [
                'libelle' => 'Coupon de 7000 F',
                'valeur' => '7000'
            ],
            [
                'libelle' => 'Coupon de 8000 F',
                'valeur' => '8000'
            ],
            [
                'libelle' => 'Coupon de 9000 F',
                'valeur' => '9000'
            ],
            [
                'libelle' => 'Coupon de 10000 F',
                'valeur' => '10000'
            ],
            [
                'libelle' => 'Coupon de 15000 F',
                'valeur' => '15000'
            ],
            [
                'libelle' => 'Coupon de 20000 F',
                'valeur' => '20000'
            ],
        ];

        foreach ($couponsTickets as $couponTicket) {
            CouponTicket::firstOrCreate([
                'libelle' => $couponTicket['libelle'],
            ], [
                'valeur' => $couponTicket['valeur'],
            ]);
        }
    }
}
