<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class MenuController extends Controller
{
    /**
     * Halaman menu/hub setelah login — pusat akses cepat ke aplikasi internal.
     */
    public function index(): View
    {
        $menus = [
            [
                'label' => 'Update Data Terbaru',
                'url' => route('admin.assets.import-full'),
                'icon' => '📤',
            ],
            [
                'label' => 'Peta Sebaran Aset (KD List)',
                'url' => route('public.assets.map'),
                'icon' => '🗺️',
            ],
            [
                'label' => 'List Data Aset',
                'url' => route('public.assets.index'),
                'icon' => '📋',
            ],
            [
                'label' => 'Peta Sebaran Aset (Non KD List)',
                'url' => route('public.assets.map-non-kd'),
                'icon' => '🗾',
            ],
            [
                'label' => 'Dashboard Infografis',
                'url' => route('public.assets.dashboard'),
                'icon' => '📊',
            ],
        ];

        return view('menu', compact('menus'));
    }
}