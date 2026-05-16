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
        if ($this->authService()->isAuthenticated()) {
            return $this->redirect('/');
        }

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

    public function login(Request $request): Response
    {
        $body = $request->json();
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $password === '') {
            return $this->json(['message' => 'E-mail i hasło są wymagane.'], 400);
        }

        if (!$this->authService()->attempt($email, $password)) {
            return $this->json(['message' => 'Nieprawidłowy e-mail lub hasło.'], 401);
        }

        return $this->json([
            'message' => 'Zalogowano pomyślnie.',
            'user' => $this->authService()->currentUser(),
        ]);
    }

    public function logout(Request $request): Response
    {
        $this->authService()->logout();

        return $this->json(['message' => 'Wylogowano pomyślnie.']);
    }

    public function me(Request $request): Response
    {
        $user = $this->authService()->currentUser();

        if ($user === null) {
            return $this->json(['message' => 'Brak aktywnej sesji.'], 401);
        }

        return $this->json(['user' => $user]);
    }
}
