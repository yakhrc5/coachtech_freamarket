@extends('layouts.app')

@section('title', 'ログイン')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth">
    <div class="auth__container">
        <h2 class="auth__title">ログイン</h2>

        <form method="POST" action="{{ route('login') }}" class="auth__form" novalidate>
            @csrf

            <div class="auth__field">
                <label class="auth__label" for="email">メールアドレス</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="auth__input"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    autofocus>

                @error('email')
                <p class="auth__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="auth__field">
                <label class="auth__label" for="password">パスワード</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="auth__input"
                    autocomplete="current-password">

                @error('password')
                <p class="auth__error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="auth__btn">ログインする</button>

            <p class="auth__link">
                <a href="{{ route('register') }}" class="auth__link-anchor">会員登録はこちら</a>
            </p>
        </form>
    </div>
</div>
@endsection