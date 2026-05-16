<?php

declare(strict_types=1);

namespace App\Middleware;

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
            return Response::html(
                $this->forbiddenView(),
                403,
            );
        }

        return $next($request);
    }

    private function forbiddenView(): string
    {
        $path = dirname(__DIR__, 2) . '/views/errors/403.php';
        ob_start();
        include $path;
        return (string) ob_get_clean();
    }
}
