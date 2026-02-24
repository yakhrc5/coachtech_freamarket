@extends('layouts.app')

@section('title', '会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('content')
<div class="auth">
    <div class="auth__container">
        <h2 class="auth__title">会員登録</h2>

        <form method="POST" action="{{ route('register') }}" class="auth__form" novalidate>
            @csrf

            <div class="auth__field">
                <label class="auth__label" for="name">ユーザー名</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    class="auth__input"
                    value="{{ old('name') }}"
                    autocomplete="name"
                    autofocus>

                @error('name')
                <p class="auth__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="auth__field">
                <label class="auth__label" for="email">メールアドレス</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="auth__input"
                    value="{{ old('email') }}"
                    autocomplete="email">

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
                    autocomplete="new-password">

                @error('password')
                <p class="auth__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="auth__field">
                <label class="auth__label" for="password_confirmation">確認用パスワード</label>
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="auth__input"
                    autocomplete="new-password">

                @error('password_confirmation')
                <p class="auth__error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="auth__btn">登録する</button>

            <p class="auth__link">
                <a href="{{ route('login') }}" class="auth__link-anchor">ログインはこちら</a>
            </p>
        </form>
    </div>
</div>
@endsection