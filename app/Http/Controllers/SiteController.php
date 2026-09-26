<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Sites/Index', [
            'sites' => Site::where('active', true)->orderBy('id')->get(['id', 'name', 'description', 'url']),
        ]);
    }
}
