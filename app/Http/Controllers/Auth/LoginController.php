<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('usuario', 'password');
        $credentials['activo'] = true;

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // Si falló, comprobar si existe pero está inactivo
        $inactivo = \App\Models\Usuario::where('usuario', $request->input('usuario'))
            ->where('activo', false)
            ->exists();

        if ($inactivo) {
            return back()->withInput($request->only('usuario'))->withErrors([
                'usuario' => 'Su cuenta se encuentra inactiva. Comuníquese con la dirección del plantel.',
            ]);
        }

        return back()->withInput($request->only('usuario'))->withErrors([
            'usuario' => 'Las credenciales introducidas no coinciden con nuestros registros.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
