<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private function getDefaultRedirectPath(User $user): string
    {
        if ($user->hasPermission('view_dashboard')) {
            return '/dashboard';
        }

        if ($user->hasPermission('access_simulation_qty')) {
            return route('simulation.qty');
        }

        if ($user->hasPermission('access_simulation')) {
            return route('simulation.index');
        }

        if ($user->hasPermission('manage_users')) {
            return route('users.index');
        }

        if ($user->hasPermission('manage_roles')) {
            return route('roles.index');
        }

        if ($user->hasPermission('manage_permissions')) {
            return route('permissions.index');
        }

        if ($user->hasPermission('manage_kategori')) {
            return route('kategori.index');
        }

        if ($user->hasPermission('manage_layanan')) {
            return route('layanan.index');
        }

        return '/login';
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        $roles = Role::all();
        return view('auth.register', compact('roles'));
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Determine if login is email or username
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        
        // Manual authentication using email or username
        $user = User::where($loginField, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'login' => 'Email/Username atau password salah.',
            ])->withInput();
        }

        // Manual login
        Auth::login($user, $request->filled('remember'));
        $request->session()->regenerate();

        $fallback = $this->getDefaultRedirectPath($user);
        return redirect()->intended($fallback);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id
        ]);

        Auth::login($user);

        return redirect($this->getDefaultRedirectPath($user));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
