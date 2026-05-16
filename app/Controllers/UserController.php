<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\RoleMiddleware;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        if (!$this->authService()->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $middleware = new RoleMiddleware($this->session(), ['admin']);

        return $middleware->handle($request, function () {
            return Response::html($this->view('users.index', [
                'title' => 'Użytkownicy',
            ]));
        });
    }
}
