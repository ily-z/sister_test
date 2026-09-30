<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 1. Menampilkan Halaman Login
    public function index()
    {
        return view('auth.login');
    }

    // 2. Memproses Data Login
    public function login(Request $request)
    {
        // Validasi menangkap input 'nama'
        $credentials = $request->validate([
            'nama' => ['required'],
            'password' => ['required'],
        ]);

        $credentials['is_active'] = true;

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'nama' => 'Nama atau password salah, atau akun belum aktif.',
        ])->onlyInput('nama');
    }
    // 3. Memproses Logout
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}