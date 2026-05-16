<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return Response::html($this->view('auth.login', [
            'title' => 'Logowanie',
        ]));
    }

    public function showRegister(Request $request): Response
    {
        return Response::html($this->view('auth.register', [
            'title' => 'Rejestracja',
        ]));
    }
}
