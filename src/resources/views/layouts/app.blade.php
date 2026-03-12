<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Flea Market')</title>

    <link rel="stylesheet" href="https://unpkg.com/sanitize.css">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">

    @yield('css')
</head>

<body>
    @php
    // 認証系画面では「ロゴのみヘッダー」を表示する
    $isAuthHeader = request()->routeIs(
    'register',
    'login',
    'verification.notice',
    'verification.verify'
    );
    @endphp

    {{-- ヘッダー --}}
    <header class="header">
        <div class="header__inner">
            {{-- ロゴ --}}
            <div class="header__logo">
                <a href="{{ route('items.index') }}" class="header__logo-link" aria-label="トップへ">
                    <img src="{{ asset('images/logo.png') }}" alt="COACHTECH" class="header__logo-img">
                </a>
            </div>

            @unless($isAuthHeader)
            {{-- 商品検索フォーム --}}
            <form action="{{ route('items.index') }}" method="GET" class="header__search">
                <input
                    type="text"
                    name="keyword"
                    class="header__search-input"
                    value="{{ request('keyword') }}"
                    placeholder="なにをお探しですか？">
            </form>

            {{-- ナビゲーション --}}
            <nav class="header__nav">
                @auth
                <form action="{{ route('logout') }}" method="POST" class="header__nav-item">
                    @csrf
                    <button type="submit" class="header__link">ログアウト</button>
                </form>

                <a href="{{ route('mypage.show') }}" class="header__link header__nav-item">マイページ</a>
                <a href="{{ route('sell.create') }}" class="header__btn header__nav-item">出品</a>
                @endauth

                @guest
                <a href="{{ route('login') }}" class="header__link header__nav-item">ログイン</a>
                <a href="{{ route('mypage.show') }}" class="header__link header__nav-item">マイページ</a>
                <a href="{{ route('sell.create') }}" class="header__btn header__nav-item">出品</a>
                @endguest
            </nav>
            @endunless
        </div>
    </header>

    {{-- フラッシュメッセージ --}}
    @if (session('info'))
    <div class="flash-message flash-message--info">
        <div class="flash-message__inner">
            {{ session('info') }}
        </div>
    </div>
    @endif

    @if (session('success'))
    <div class="flash-message flash-message--success">
        <div class="flash-message__inner">
            {{ session('success') }}
        </div>
    </div>
    @endif

    @if (session('error'))
    <div class="flash-message flash-message--error">
        <div class="flash-message__inner">
            {{ session('error') }}
        </div>
    </div>
    @endif

    {{-- メインコンテンツ --}}
    <main class="layout_main">
        @yield('content')
    </main>

    @yield('js')
</body>

</html>
