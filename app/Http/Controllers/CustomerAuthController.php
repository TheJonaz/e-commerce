<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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

    // --- Password reset ---

    public function showForgot()
    {
        return view('shop.auth.forgot');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('customers')->sendResetLink(['email' => $data['email']]);

        // Always show the same flash so the response doesn't leak whether the address exists.
        return back()->with('status', 'Om kontot finns har vi skickat en återställningslänk till ' . $data['email'] . '.');
    }

    public function showReset(string $token, Request $request)
    {
        return view('shop.auth.reset', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker('customers')->reset(
            $data,
            function (Customer $customer, string $password) {
                $customer->password = Hash::make($password);
                $customer->setRememberToken(Str::random(60));
                $customer->save();
                event(new PasswordReset($customer));
            }
        );

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => match ($status) {
                    Password::InvalidToken => 'Återställningslänken är ogiltig eller har gått ut.',
                    Password::InvalidUser => 'Hittade ingen kund med den e-postadressen.',
                    default => 'Något gick fel, försök igen.',
                },
            ]);
        }

        return redirect()->route('customer.login')->with('status', 'Ditt lösenord har återställts. Logga in.');
    }
}
