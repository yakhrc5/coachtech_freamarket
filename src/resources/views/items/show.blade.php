{{-- resources/views/items/show.blade.php --}}
@extends('layouts.app')

@section('title', '商品詳細')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item-detail.css') }}">
@endsection

@section('content')
<div class="product-detail">
    <div class="product-detail__inner">
        <div class="product-detail__grid">
            {{-- 左フレーム --}}
            <section class="product-detail__panel product-detail__panel--left">
                <div class="product-detail__panel-inner">
                    <div class="product-detail__media">
                        <div class="product-detail__image-box">
                            @if($item->image_path)
                            <img
                                class="product-detail__image"
                                src="{{ Storage::url($item->image_path) }}"
                                alt="商品画像">

                            @if($item->purchase)
                            <span class="badge-sold">Sold</span>
                            @endif

                            @else
                            <p class="product-detail__image-placeholder">商品画像</p>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
            {{-- 右フレーム --}}
            <section class="product-detail__panel product-detail__panel--right">
                <div class="product-detail__panel-inner">
                    <div class="product-detail__main">
                        {{-- 商品名 --}}
                        <h1 class="product-detail__title">{{ $item->name }}</h1>
                        {{-- ブランド名 --}}
                        <p class="product-detail__brand">{{ $item->brand }}</p>
                        {{-- 販売価格 --}}
                        <p class="product-detail__price">
                            <span class="product-detail__price-mark">¥</span>
                            <span class="product-detail__price-number">{{ number_format($item->price) }}</span>
                            <span class="product-detail__price-tax">(税込)</span>
                        </p>

                        {{-- いいね数・コメント数（アイコン） --}}
                        <div class="product-detail__stats">
                            {{-- いいね --}}
                            <div class="product-detail__stat">
                                @auth
                                <form action="{{ route('items.like.toggle', $item->id) }}" method="POST" class="product-detail__stat-form">
                                    @csrf
                                    <button type="submit" class="product-detail__icon-btn" aria-label="いいね">
                                        <img
                                            class="product-detail__icon product-detail__icon--heart"
                                            src="{{ $isLiked ? asset('images/icons/heart-liked.png') : asset('images/icons/heart-default.png') }}"
                                            alt="いいね">
                                    </button>
                                </form>
                                @else
                                <a href="{{ route('login') }}" class="product-detail__icon-btn" aria-label="ログインしていいねする">
                                    <img
                                        class="product-detail__icon product-detail__icon--heart"
                                        src="{{ asset('images/icons/heart-default.png') }}"
                                        alt="いいね">
                                </a>
                                @endauth
                                <p class="product-detail__stat-count">{{ $item->likes_count }}</p>
                            </div>

                            {{-- コメント --}}
                            <div class="product-detail__stat">
                                <div class="product-detail__icon-btn product-detail__icon-btn--disabled" aria-hidden="true">
                                    <img
                                        class="product-detail__icon product-detail__icon--comment"
                                        src="{{ asset('images/icons/comment.png') }}"
                                        alt="コメント">
                                </div>
                                <p class="product-detail__stat-count">{{ $item->comments_count }}</p>
                            </div>
                        </div>

                        {{-- 購入ボタン --}}
                        <div class="product-detail__purchase">
                            @if ($item->purchase)
                            <div class="product-detail__purchase-btn product-detail__purchase-btn--sold" aria-disabled="true">
                                売り切れ
                            </div>
                            @else
                            <a href="{{ route('purchase.show', $item) }}" class="product-detail__purchase-btn">
                                購入手続きへ
                            </a>
                            @endif
                        </div>

                        {{-- 商品説明 --}}
                        <section class="product-detail__section">
                            <h2 class="product-detail__section-title">商品説明</h2>
                            <p class="product-detail__text">{{ $item->description }}</p>
                        </section>

                        {{-- 商品情報（カテゴリ / 状態） --}}
                        <section class="product-detail__section">
                            <h2 class="product-detail__section-title">商品の情報</h2>

                            <dl class="product-detail__info">
                                <dt class="product-detail__info-label">カテゴリー</dt>
                                <dd class="product-detail__info-value">
                                    <div class="product-detail__chips">
                                        @foreach($item->categories as $category)
                                        <span class="product-detail__chip">{{ $category->name }}</span>
                                        @endforeach
                                    </div>
                                </dd>
                                <dt class="product-detail__info-label">商品の状態</dt>
                                <dd class="product-detail__info-value">
                                    <span class="product-detail__status">{{ $item->condition->name ?? '' }}</span>
                                </dd>
                            </dl>
                        </section>

                        {{-- コメント一覧 --}}
                        <section class="product-detail__section">
                            <h2 class="product-detail__comment-title">コメント({{ $item->comments_count }})</h2>

                            <div class="comments-list">
                                @forelse($item->comments as $comment)
                                <div class="comment-card">
                                    <div class="comment-card__head">
                                        <div class="comment-card__avatar-wrap">
                                            @if(!empty($comment->user->profile_image_path))
                                            <img
                                                class="comment-card__avatar-img"
                                                src="{{ Storage::disk('public')->url($comment->user->profile_image_path) }}"
                                                alt="プロフィール画像">
                                            @else
                                            <div class="comment-card__avatar" aria-hidden="true"></div>
                                            @endif
                                        </div>
                                        <p class="comment-card__name">{{ $comment->user->name ?? 'user' }}</p>
                                    </div>

                                    <div class="comment-card__bubble">
                                        <p class="comment-card__text">{{ $comment->body }}</p>
                                    </div>
                                </div>
                                @empty
                                <p class="product-detail__empty">コメントはまだありません。</p>
                                @endforelse
                            </div>

                            {{-- コメント送信 --}}
                            <div class="comment-form">
                                <h3 class="comment-form__title">商品へのコメント</h3>

                                <form action="{{ route('items.comments.store', $item->id) }}" method="POST" class="comment-form__form">
                                    @csrf

                                    @auth
                                    <textarea
                                        name="body"
                                        class="comment-form__textarea">
                                    {{ old('body') }}</textarea>
                                    @else
                                    <textarea
                                        class="comment-form__textarea comment-form__textarea--guest"
                                        readonly>コメントを送信するにはログインが必要です。</textarea>
                                    @endauth

                                    <div class="comment-form__error-area">
                                        @auth
                                        @error('body')
                                        <p class="comment-form__error">{{ $message }}</p>
                                        @enderror
                                        @endauth
                                    </div>

                                    <button type="submit" class="comment-form__submit">コメントを送信する</button>
                                </form>
                            </div>
                        </section>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection