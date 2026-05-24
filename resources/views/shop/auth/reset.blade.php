@extends('layouts.shop')

@section('content')
    <div style="max-width: 420px; margin: 2rem auto;">
        <div class="page-head" style="text-align: center;">
            <h1>Sätt nytt lösenord</h1>
        </div>

        @if ($errors->any())
            <div style="background: #fef2f2; border: 1px solid #fecaca; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; color: #7f1d1d; font-size: 0.875rem;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" style="background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; display: grid; gap: 1rem;">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label style="font-size: 0.85rem; font-weight: 500;">E-post
                <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
            </label>
            <label style="font-size: 0.85rem; font-weight: 500;">Nytt lösenord <small style="color: var(--muted); font-weight: 400;">(min 8)</small>
                <input type="password" name="password" required minlength="8"
                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
            </label>
            <label style="font-size: 0.85rem; font-weight: 500;">Bekräfta lösenord
                <input type="password" name="password_confirmation" required minlength="8"
                    style="display: block; width: 100%; margin-top: 0.25rem; padding: 0.55rem 0.7rem; border: 1px solid var(--border); border-radius: 6px; font: inherit;">
            </label>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Spara nytt lösenord</button>
        </form>
    </div>
@endsection
