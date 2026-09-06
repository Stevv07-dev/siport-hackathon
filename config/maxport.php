<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Navigasi Aplikasi
    |--------------------------------------------------------------------------
    |
    | Satu sumber kebenaran untuk sidebar dan top nav, supaya urutan, label,
    | dan ikon selalu sama di semua tempat. Item tanpa "route" ditampilkan
    | sebagai menu yang belum aktif (tahap pengembangan berikutnya).
    |
    */

    'nav' => [
        [
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'route' => 'dashboard',
            'pattern' => 'dashboard',
        ],
        [
            'label' => 'Products',
            'icon' => 'box',
            'route' => 'products.create',
            'pattern' => 'products*',
        ],
        [
            'label' => 'Verification',
            'icon' => 'shield-check',
            'route' => null,
            'pattern' => 'verification*',
        ],
        [
            'label' => 'History',
            'icon' => 'history',
            'route' => 'history.index',
            'pattern' => 'history*',
        ],
        [
            'label' => 'Settings',
            'icon' => 'settings',
            'route' => 'profile.edit',
            'pattern' => 'profile*',
            'sidebar_only' => true,
        ],
    ],

];
