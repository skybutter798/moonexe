<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarketingToolsController extends Controller
{
    public function index()
    {
        // Sectioned (like FAQ), each with items. You can pull this from DB later.
        $tools = [
            [
                'section' => 'Images',
                'items' => [
                    [
                        'title' => 'Auto-Staking Promo Banner',
                        'desc'  => 'Use on social media or landing pages.',
                        'type'  => 'image',
                        'url'   => asset('img/staking.jpeg'),            // sample you gave
                        'thumb' => asset('img/staking.jpeg'),            // same or a smaller thumbnail
                        'tags'  => ['staking','promo','banner'],
                    ],
                    // Add more images here...
                ],
            ],
            [
                'section' => 'PDF Brochures',
                'items' => [
                    [
                        'title' => 'MoonExe Partner Pitch (EN)',
                        'desc'  => 'Concise deck for investor/partner outreach.',
                        'type'  => 'pdf',
                        'url'   => asset('marketing/pitch_en.pdf'),      // e.g. public/marketing/pitch_en.pdf
                        'thumb' => asset('img/pdf-thumb.png'),           // optional thumb image
                        'tags'  => ['deck','partners','english'],
                    ],
                    [
                        'title' => 'Auto-Staking One-Pager',
                        'desc'  => 'Overview of the Auto-Staking program.',
                        'type'  => 'pdf',
                        'url'   => asset('marketing/auto_staking_onepager.pdf'),
                        'thumb' => asset('img/pdf-thumb.png'),
                        'tags'  => ['staking','brochure'],
                    ],
                ],
            ],
            [
                'section' => 'Logos & Brand',
                'items' => [
                    [
                        'title' => 'MoonExe Logo (PNG)',
                        'desc'  => 'Transparent background. Use on light/dark.',
                        'type'  => 'image',
                        'url'   => asset('img/moon_logo.png'),
                        'thumb' => asset('img/moon_logo.png'),
                        'tags'  => ['brand','logo','png'],
                    ],
                    // Add SVG/AI download links here later if needed
                ],
            ],
        ];

        // Flatten for quick filtering (optional)
        $allItems = collect($tools)->flatMap(fn ($s) => $s['items'])->values()->all();

        return view('user.marketing-tools', [
            'title'    => 'Marketing Tools',
            'tools'    => $tools,
            'allItems' => $allItems,
        ]);
    }
}
