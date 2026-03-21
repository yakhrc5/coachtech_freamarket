@extends('layouts.app')

@section('title', 'マイページ')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
<div class="mypage">
    <div class="mypage__inner">
        {{-- 見た目には出さないが、ページ全体の見出しとして置く --}}
        <h1 class="visually-hidden">マイページ</h1>

        {{-- ユーザー情報 --}}
        <section class="mypage-user">
            <div class="mypage-user__inner">
                <div class="mypage-user__avatar">
                    @if (!empty($user->profile_image_path))
                    <img
                        src="{{ Storage::url($user->profile_image_path) }}"
                        alt="プロフィール画像"
                        class="mypage-user__avatar-img">
                    @else
                    <div class="mypage-user__avatar-placeholder"></div>
                    @endif
                </div>

                <p class="mypage-user__name">{{ $user->name }}</p>

                <a
                    href="{{ route('profile.edit', ['from' => 'mypage']) }}"
                    class="mypage-user__edit-btn">
                    プロフィールを編集
                </a>
            </div>
        </section>

        {{-- 出品 / 購入の切り替えタブ --}}
        <nav class="mypage-tabs" aria-label="マイページメニュー">
            <a
                href="{{ route('mypage.show', ['page' => 'sell']) }}"
                class="mypage-tabs__tab {{ $page === 'sell' ? 'is-active' : '' }}"
                @if ($page==='sell' ) aria-current="page" @endif>
                出品した商品
            </a>

            <a
                href="{{ route('mypage.show', ['page' => 'buy']) }}"
                class="mypage-tabs__tab {{ $page === 'buy' ? 'is-active' : '' }}"
                @if ($page==='buy' ) aria-current="page" @endif>
                購入した商品
            </a>
        </nav>

        {{-- 商品一覧 --}}
        <section class="mypage-items">
            <div class="mypage-items__grid">
                {{-- page が sell のときは出品一覧、そうでなければ購入一覧を表示 --}}
                @if ($page === 'sell')
                    {{-- 出品した商品一覧 --}}
                    @foreach ($sellItems as $item)
                    <article class="mypage-card">
                        <a
                            href="{{ route('items.show', ['item_id' => $item->id]) }}"
                            class="mypage-card__link">
                            <div class="mypage-card__image-wrap">
                                <img
                                    src="{{ Storage::url($item->image_path) }}"
                                    alt="{{ $item->name }}"
                                    class="mypage-card__image">

                                {{-- 購入履歴がある商品は Sold を表示 --}}
                                @if (!empty($item->purchase))
                                <span class="badge-sold">Sold</span>
                                @endif
                            </div>

                            <p class="mypage-card__name">{{ $item->name }}</p>
                        </a>
                    </article>
                    @endforeach
                @else
                    {{-- 購入した商品一覧 --}}
                    @foreach ($buyPurchases as $purchase)
                        @php
                            $purchasedItem = $purchase->item;
                        @endphp

                        @if ($purchasedItem)
                        <article class="mypage-card">
                            <a
                                href="{{ route('items.show', ['item_id' => $purchasedItem->id]) }}"
                                class="mypage-card__link">
                                <div class="mypage-card__image-wrap">
                                    <img
                                        src="{{ Storage::url($purchasedItem->image_path) }}"
                                        alt="{{ $purchasedItem->name }}"
                                        class="mypage-card__image">
                                    <span class="badge-sold">Sold</span>
                                </div>

                                <p class="mypage-card__name">{{ $purchasedItem->name }}</p>
                            </a>
                        </article>
                        @endif
                    @endforeach
                @endif
            </div>
        </section>
    </div>
</div>
@endsection