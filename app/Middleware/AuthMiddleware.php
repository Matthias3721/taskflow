<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware
{
    public function __construct(private readonly Session $session)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        if (!$this->session->has('user_id')) {
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
