<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class RoleMiddleware
{
    /** @param list<string> $allowedRoles */
    public function __construct(
        private readonly Session $session,
        private readonly array $allowedRoles,
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $role = $this->session->get('user_role');

        if ($role === null || !in_array($role, $this->allowedRoles, true)) {
            return ErrorHandler::forStatus(403, '', $request->uri());
        }

        return $next($request);
    }

}
