<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ApiException;
use App\Core\Auth;
use App\Core\Controller;

// slice demo (auth). El patron login -> token -> requests con Bearer es el que
// todo proyecto de este starter necesita; lo que se descarta es el detalle de
// los campos, no el flujo.
final class AuthController extends Controller
{
    // POST /api/auth/login  { "email": "...", "password": "..." }
    public function login(): void
    {
        $email = trim((string) $this->request->input('email', ''));
        $password = (string) $this->request->input('password', '');

        $errores = [];
        if ($email === '') {
            $errores['email'] = 'Requerido.';
        }
        if ($password === '') {
            $errores['password'] = 'Requerido.';
        }
        if ($errores !== []) {
            throw ApiException::validacion($errores);
        }

        $this->json(Auth::login($email, $password));
    }

    // GET /api/auth/me  (protegida) -> devuelve el usuario dueno del token.
    public function me(): void
    {
        $this->json(['usuario' => Auth::usuarioActual($this->request)]);
    }

    // POST /api/auth/logout  (protegida) -> invalida el token actual.
    public function logout(): void
    {
        Auth::usuarioActual($this->request); // 401 si el token ya no sirve
        Auth::logout($this->request);
        $this->sinContenido();
    }
}
