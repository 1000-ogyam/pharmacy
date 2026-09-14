<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

final class HelpController extends Controller
{
    public function index(Request $request): never
    {
        $this->view('help.index', [
            'title' => 'User guide',
            'pageTitle' => 'User guide',
        ], auth()->check() ? layout_for_role() : 'help-public');
    }
}
