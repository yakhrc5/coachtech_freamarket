<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FREEMARKET')</title>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a href="{{ route('items.index') }}" class="header__logo">
                COACHTECH
            </a>

            <nav class="header__nav">
                @auth
                <a href="{{ route('items.index') }}" class="header__link">商品一覧</a>
                <a href="{{ route('my.items.index') }}" class="header__link">出品一覧</a>
                <a href="{{ route('profile.edit') }}" class="header__link">プロフィール</a>

                <form action="{{ route('logout') }}" method="POST" class="header__logout">
                    @csrf
                    <button type="submit" class="header__link header__link--button">
                        ログアウト
                    </button>
                </form>
                @endauth

                {{-- @guest
                <a href="{{ route('login') }}" class="header__link">ログイン</a>
                <a href="{{ route('register') }}" class="header__link header__link--primary">会員登録</a>
                @endguest --}}
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="container">
            @if (session('message'))
            <div class="alert alert--success">
                {{ session('message') }}
            </div>
            @endif

            @if ($errors->any())
            <div class="alert alert--danger">
                <ul class="alert__list">
                    @foreach ($errors->all() as $error)
                    <li class="alert__item">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="footer">
        <small class="footer__text">© COACHTECH</small>
    </footer>
</body>

</html>