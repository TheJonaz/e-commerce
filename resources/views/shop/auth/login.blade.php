@extends('layouts.shop')

@section('content')
    <div style="max-width: 420px; margin: 2rem auto;">
        <div class="page-head" style="text-align: center;">
            <h1>Logga in</h1>
            <p class="lead">Eller <a href="{{ route('customer.register') }}" style="color: var(--primary); text-decoration: underline;">skapa ett konto</a></p>
        </div>

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; color: #7f1d1d; font-size: 0.875rem;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('customer.login') }}" style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: grid; gap: 1rem;">
            @csrf
            <label style="font-size: 0.85rem; font-weight: 500;">E-post
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
            </label>
            <label style="font-size: 0.85rem; font-weight: 500;">Lösenord
                <input type="password" name="password" required
                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
            </label>
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                <label style="display: inline-flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} style="accent-color: var(--primary);">
                    Kom ihåg mig
                </label>
                <a href="{{ route('password.request') }}" style="color: var(--primary);">Glömt lösenord?</a>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Logga in</button>
        </form>
    </div>
@endsection
