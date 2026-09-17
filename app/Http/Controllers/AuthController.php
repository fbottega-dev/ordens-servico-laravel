<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:64']);
        if (! Auth::attempt($data)) {
            throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
        }
        $request->session()->regenerate();

        return redirect()->intended('/orders');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers(), 'max:64', 'regex:/^[\x20-\x7E]+$/']]);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/orders');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
