<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        return view('shop.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        if (! Auth::guard('customer')->attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            (bool) ($data['remember'] ?? false),
        )) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Fel e-post eller lösenord.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.show'));
    }

    public function showRegister()
    {
        return view('shop.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:64'],
            'is_business' => ['nullable', 'boolean'],
            'vat_number' => ['nullable', 'string', 'max:64', Rule::requiredIf(fn () => $request->boolean('is_business'))],
        ]);

        $existing = Customer::where('email', $data['email'])->first();

        if ($existing && $existing->hasPassword()) {
            return back()->withInput()->withErrors([
                'email' => 'E-postadressen är redan registrerad — logga in istället.',
            ]);
        }

        // Claim existing guest customer record, or create a new one.
        $customer = $existing ?: new Customer();
        $customer->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? $customer->phone,
            'is_business' => (bool) ($data['is_business'] ?? false),
            'vat_number' => $data['vat_number'] ?? null,
        ]);
        $customer->password = Hash::make($data['password']);
        $customer->save();

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('account.show');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
