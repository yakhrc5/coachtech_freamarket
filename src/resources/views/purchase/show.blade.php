@extends('layouts.app')

@section('title', '商品購入')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
<div class="purchase">
    <div class="purchase__inner">
        <form action="{{ route('purchase.store', $item) }}" method="POST" class="purchase__form">
            @csrf

            <div class="purchase__grid">
                {{-- 左 --}}
                <section class="purchase-left">
                    {{-- 商品情報 --}}
                    <div class="purchase-item">
                        <div class="purchase-item__image-wrap">
                            <img
                                class="purchase-item__image"
                                src="{{ Storage::url($item->image_path) }}"
                                alt="商品画像">
                        </div>

                        <div class="purchase-item__meta">
                            <p class="purchase-item__name">{{ $item->name }}</p>
                            <p class="purchase-item__price">¥ {{ number_format($item->price) }}</p>
                        </div>
                    </div>

                    <div class="purchase__divider"></div>

                    {{-- 支払い方法 --}}
                    <div class="purchase-section">
                        <p class="purchase-section__title">支払い方法</p>

                        <div class="purchase-payment">
                            <select
                                name="payment_method"
                                class="purchase-payment__select"
                                id="paymentMethodSelect">
                                <option value="">選択してください</option>
                                <option value="コンビニ支払い" {{ old('payment_method') === 'コンビニ支払い' ? 'selected' : '' }}>
                                    コンビニ支払い
                                </option>
                                <option value="カード支払い" {{ old('payment_method') === 'カード支払い' ? 'selected' : '' }}>
                                    カード支払い
                                </option>
                            </select>

                            @error('payment_method')
                            <p class="purchase__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="purchase__divider"></div>

                    {{-- 配送先 --}}
                    <div class="purchase-section">
                        <div class="purchase-ship__head">
                            <p class="purchase-section__title">配送先</p>
                            <a href="{{ route('purchase.address.edit', $item) }}" class="purchase-ship__link">変更する</a>
                        </div>

                        <div class="purchase-ship__body">
                            <p class="purchase-ship__text">〒 {{ $shipping['postal_code'] ?? 'XXX-YYYY' }}</p>
                            <p class="purchase-ship__text">{{ $shipping['address'] ?? 'ここには住所と建物が入ります' }}</p>
                            @if(!empty($shipping['building']))
                            <p class="purchase-ship__text">{{ $shipping['building'] }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="purchase__divider"></div>
                </section>

                {{-- 右 --}}
                <aside class="purchase-right">
                    <div class="purchase-summary">
                        <div class="purchase-summary__grid">
                            <div class="purchase-summary__cell">
                                <p class="purchase-summary__label">商品代金</p>
                            </div>
                            <div class="purchase-summary__cell">
                                <p class="purchase-summary__value">¥ {{ number_format($item->price) }}</p>
                            </div>

                            <div class="purchase-summary__cell">
                                <p class="purchase-summary__label">支払い方法</p>
                            </div>
                            <div class="purchase-summary__cell">
                                <p class="purchase-summary__value" id="paymentMethodPreview">
                                    {{ old('payment_method') ?: '選択してください' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="purchase-summary__btn">
                        購入する
                    </button>
                </aside>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const select = document.getElementById('paymentMethodSelect');
        const preview = document.getElementById('paymentMethodPreview');
        if (!select || !preview) return;

        const render = () => {
            preview.textContent = select.value ? select.value : '選択してください';
        };

        select.addEventListener('change', render);
        render();
    })();
</script>
@endsection