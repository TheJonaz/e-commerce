@extends('layouts.shop')

@section('content')
    <div style="max-width: 420px; margin: 2rem auto;">
        <div class="page-head" style="text-align: center;">
            <h1>Glömt lösenord?</h1>
            <p class="lead">Skriv in din e-post så skickar vi en återställningslänk.</p>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; color: #7f1d1d; font-size: 0.875rem;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: grid; gap: 1rem;">
            @csrf
            <label style="font-size: 0.85rem; font-weight: 500;">E-post
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
            </label>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Skicka återställningslänk</button>
        </form>

        <p style="text-align: center; margin-top: 1rem; font-size: 0.85rem;">
            <a href="{{ route('customer.login') }}" style="color: var(--primary);">Tillbaka till login</a>
        </p>
    </div>
@endsection
