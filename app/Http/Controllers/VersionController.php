<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class VersionController extends Controller
{
    public function version(): Response
    {
        return \response(\app()->version(), 200)
            ->header('Content-Type', 'text/html');
    }
}
