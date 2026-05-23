@extends('layouts.shop')

@section('content')
    <div style="max-width: 520px; margin: 2rem auto;">
        <div class="page-head" style="text-align: center;">
            <h1>Skapa konto</h1>
            <p class="lead">Eller <a href="{{ route('customer.login') }}" style="color: var(--primary); text-decoration: underline;">logga in</a></p>
        </div>

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; color: #7f1d1d; font-size: 0.875rem;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('customer.register') }}" style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: grid; gap: 1rem;">
            @csrf

            <label style="font-size: 0.85rem; font-weight: 500;">Namn
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
            </label>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <label style="font-size: 0.85rem; font-weight: 500;">E-post
                    <input type="email" name="email" value="{{ old('email') }}" required
                        style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                </label>
                <label style="font-size: 0.85rem; font-weight: 500;">Telefon
                    <input type="tel" name="phone" value="{{ old('phone') }}"
                        style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                </label>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                <label style="font-size: 0.85rem; font-weight: 500;">Lösenord <small style="color: var(--muted); font-weight: 400;">(min 8)</small>
                    <input type="password" name="password" required minlength="8"
                        style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                </label>
                <label style="font-size: 0.85rem; font-weight: 500;">Bekräfta
                    <input type="password" name="password_confirmation" required minlength="8"
                        style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
                </label>
            </div>

            <label style="font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                <input type="checkbox" name="is_business" value="1" id="is_business" {{ old('is_business') ? 'checked' : '' }} style="accent-color: var(--primary);"
                    onchange="document.getElementById('vat_row').style.display = this.checked ? 'block' : 'none'">
                Företagskonto (priser visas exkl. moms i framtida release)
            </label>

            <label id="vat_row" style="font-size: 0.85rem; font-weight: 500; display: {{ old('is_business') ? 'block' : 'none' }};">
                Org.nr / VAT-nummer
                <input type="text" name="vat_number" value="{{ old('vat_number') }}"
                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
            </label>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Skapa konto</button>
        </form>
    </div>
@endsection
