@extends('layouts.app')

@section('title', 'マイページ')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
<div class="mypage">
    <div class="mypage__inner">
        <h1 class="visually-hidden">マイページ</h1>

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

        <nav class="mypage-tabs" aria-label="マイページメニュー">
            <a
                href="{{ route('mypage.show', ['page' => 'sell']) }}"
                class="mypage-tabs__tab {{ $page === 'sell' ? 'is-active' : '' }}">
                出品した商品
            </a>

            <a
                href="{{ route('mypage.show', ['page' => 'buy']) }}"
                class="mypage-tabs__tab {{ $page === 'buy' ? 'is-active' : '' }}">
                購入した商品
            </a>
        </nav>

        <section class="mypage-items">
            <div class="mypage-items__grid">
                @if ($page === 'sell')
                @forelse ($sellItems as $item)
                <article class="mypage-card">
                    <a
                        href="{{ route('items.show', ['item_id' => $item->id]) }}"
                        class="mypage-card__link">
                        <div class="mypage-card__image-wrap">
                            <img
                                src="{{ Storage::url($item->image_path) }}"
                                alt="{{ $item->name }}"
                                class="mypage-card__image">

                            @if (!empty($item->purchase))
                            <span class="badge-sold">Sold</span>
                            @endif
                        </div>

                        <p class="mypage-card__name">{{ $item->name }}</p>
                    </a>
                </article>
                @empty
                <p class="mypage-items__empty">出品した商品がありません</p>
                @endforelse
                @else
                @forelse ($buyPurchases as $purchase)
                @php
                $item = $purchase->item;
                @endphp

                @if ($item)
                <article class="mypage-card">
                    <a
                        href="{{ route('items.show', ['item_id' => $item->id]) }}"
                        class="mypage-card__link">
                        <div class="mypage-card__image-wrap">
                            <img
                                src="{{ Storage::url($item->image_path) }}"
                                alt="{{ $item->name }}"
                                class="mypage-card__image">
                            <span class="badge-sold">Sold</span>
                        </div>

                        <p class="mypage-card__name">{{ $item->name }}</p>
                    </a>
                </article>
                @endif
                @empty
                <p class="mypage-items__empty">購入した商品がありません</p>
                @endforelse
                @endif
            </div>
        </section>
    </div>
</div>
@endsection