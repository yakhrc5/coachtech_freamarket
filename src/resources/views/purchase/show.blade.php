@extends('layouts.app')

@section('title', '商品購入')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
<div class="purchase">
    <div class="purchase__inner">
        <h1 class="visually-hidden">商品購入</h1>

        <form action="{{ route('purchase.store', ['item_id' => $item->id]) }}" method="POST" class="purchase__form">
            @csrf

            <div class="purchase__grid">
                {{-- 左カラム --}}
                <section class="purchase__left">
                    {{-- 商品情報 --}}
                    <div class="purchase-item">
                        <div class="purchase-item__image-wrap">
                            <img
                                class="purchase-item__image"
                                src="{{ Storage::url($item->image_path) }}"
                                alt="{{ $item->name }}">
                        </div>

                        <div class="purchase-item__meta">
                            <p class="purchase-item__name">{{ $item->name }}</p>
                            <p class="purchase-item__price">
                                <span class="purchase-item__price-prefix">¥</span>
                                <span class="purchase-item__price-value">{{ number_format($item->price) }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="purchase__divider"></div>

                    {{-- 支払い方法 --}}
                    <section class="purchase-section">
                        <h2 class="purchase-section__title">支払い方法</h2>

                        <div class="purchase-payment">
                            <input
                                type="hidden"
                                name="payment_method_id"
                                id="payment_method_id"
                                value="{{ old('payment_method_id', '') }}">

                            <div
                                class="payment-select"
                                id="paymentMethodSelect"
                                data-placeholder="選択してください">
                                <button
                                    type="button"
                                    class="payment-select__trigger"
                                    aria-haspopup="listbox"
                                    aria-expanded="false">
                                    <span class="payment-select__trigger-text">選択してください</span>
                                    <span class="payment-select__trigger-arrow"></span>
                                </button>

                                <div class="payment-select__panel" role="listbox">
                                    <div class="payment-select__options" data-options>
                                        @foreach ($paymentMethods as $paymentMethod)
                                        <button
                                            type="button"
                                            class="payment-select__option"
                                            data-value="{{ $paymentMethod->id }}"
                                            data-label="{{ $paymentMethod->name }}"
                                            data-code="{{ $paymentMethod->code }}"
                                            role="option">
                                            <span class="payment-select__check">✓</span>
                                            <span class="payment-select__label">{{ $paymentMethod->name }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="purchase__error-area">
                                @error('payment_method_id')
                                <p class="purchase__error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </section>

                    <div class="purchase__divider"></div>

                    {{-- 配送先 --}}
                    <section class="purchase-section">
                        <div class="purchase-ship">
                            <div class="purchase-ship__head">
                                <h2 class="purchase-section__title">配送先</h2>
                                <a
                                    href="{{ route('purchase.address.edit', ['item_id' => $item->id]) }}"
                                    class="purchase-ship__link">
                                    変更する
                                </a>
                            </div>

                            <div class="purchase-ship__body">
                                <p class="purchase-ship__text">
                                    〒 {{ $shipping['postal_code'] ?? 'XXX-YYYY' }}
                                </p>
                                <p class="purchase-ship__text">{{ $shipping['address'] }}</p>

                                @if (!empty($shipping['building']))
                                <p class="purchase-ship__text">{{ $shipping['building'] }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="purchase__error-area">
                            @error('shipping')
                            <p class="purchase__error">{{ $message }}</p>
                            @enderror
                        </div>
                    </section>

                    <div class="purchase__divider"></div>
                </section>

                {{-- 右カラム --}}
                <aside class="purchase__right">
                    @php
                    $oldPaymentMethodId = old('payment_method_id');
                    $oldPaymentMethodName = $paymentMethods->firstWhere('id', (int) $oldPaymentMethodId)?->name;
                    @endphp

                    <div class="purchase-summary">
                        <div class="purchase-summary__grid">
                            <div class="purchase-summary__cell">
                                <p class="purchase-summary__label">商品代金</p>
                            </div>
                            <div class="purchase-summary__cell">
                                <p class="purchase-summary__value">
                                    <span class="purchase-summary__value-prefix">¥</span>
                                    <span class="purchase-summary__value-price">{{ number_format($item->price) }}</span>
                                </p>
                            </div>

                            <div class="purchase-summary__cell">
                                <p class="purchase-summary__label">支払い方法</p>
                            </div>
                            <div class="purchase-summary__cell">
                                <p class="purchase-summary__value" id="paymentMethodPreview">
                                    {{ $oldPaymentMethodName ?: '選択してください' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="purchase__submit-btn">
                        購入する
                    </button>

                </aside>
            </div>
        </form>
    </div>
</div>

<script>
    // HTMLの読み込みが完了してから処理を開始
    document.addEventListener('DOMContentLoaded', () => {
        // 支払い方法セレクト全体の親要素を取得
        const root = document.getElementById('paymentMethodSelect');

        // 要素が存在しないページでは何もしない
        if (!root) {
            return;
        }

        // 開閉ボタン
        const trigger = root.querySelector('.payment-select__trigger');

        // 開閉ボタン内に表示するテキスト
        const triggerText = root.querySelector('.payment-select__trigger-text');

        // 選択肢一覧のラッパー要素
        const optionsWrap = root.querySelector('[data-options]');

        // 実際にフォーム送信用の値を保持する hidden input
        const hiddenInput = document.getElementById('payment_method_id');

        // 右側などの確認表示用テキスト
        const preview = document.getElementById('paymentMethodPreview');

        // data-placeholder があればそれを使い、なければデフォルト文言を使う
        const placeholder = root.dataset.placeholder || '選択してください';

        // ------------------------------
        // 選択肢の active 状態を全解除
        // ------------------------------
        const clearActive = () => {
            optionsWrap.querySelectorAll('.payment-select__option.is-active').forEach((el) => {
                el.classList.remove('is-active');
            });
        };

        // ------------------------------
        // 指定した選択肢だけ active にする
        // ------------------------------
        const setActive = (btn) => {
            if (!btn) {
                return;
            }

            clearActive();
            btn.classList.add('is-active');
        };

        // -----------------------------------------------------
        // 現在の選択値に応じて表示ラベルを更新する
        // ・トリガー内のテキスト
        // ・確認表示用テキスト
        // -----------------------------------------------------
        const setLabels = (value) => {
            // 未選択ならプレースホルダー表示
            if (!value) {
                triggerText.textContent = placeholder;

                if (preview) {
                    preview.textContent = placeholder;
                }

                return;
            }

            // value に一致する選択肢ボタンを探す
            const btn = optionsWrap.querySelector(
                `.payment-select__option[data-value="${CSS.escape(String(value))}"]`
            );

            // 一致するボタンがあればそのラベル、なければプレースホルダー
            const label = btn ? btn.dataset.label : placeholder;

            triggerText.textContent = label;

            if (preview) {
                preview.textContent = label;
            }
        };

        // ---------------------------------------
        // hidden input の値から現在選択中のボタンを取得
        // ---------------------------------------
        const getSelectedButton = () => {
            const value = hiddenInput.value;

            if (!value) {
                return null;
            }

            return optionsWrap.querySelector(
                `.payment-select__option[data-value="${CSS.escape(String(value))}"]`
            );
        };

        // ------------------------------
        // セレクトを閉じる
        // ------------------------------
        const close = () => {
            root.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            clearActive();
        };

        // ------------------------------
        // セレクトを開く
        // ------------------------------
        const open = () => {
            root.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');

            // すでに選択済みの項目があれば active を付ける
            const selectedButton = getSelectedButton();

            if (selectedButton) {
                setActive(selectedButton);
            }
        };

        // 初期表示時に、hidden input の値をもとにラベルを反映
        setLabels(hiddenInput.value);

        // ------------------------------------------------
        // トリガークリック時
        // 開いていれば閉じる、閉じていれば開く
        // ------------------------------------------------
        trigger.addEventListener('click', (e) => {
            e.preventDefault();

            if (root.classList.contains('is-open')) {
                close();
                return;
            }

            open();
        });

        // ------------------------------------------------
        // 選択肢にマウスを乗せたとき
        // hover中の項目に active を付ける
        // ------------------------------------------------
        optionsWrap.addEventListener('mouseover', (e) => {
            const btn = e.target.closest('.payment-select__option');

            if (!btn) {
                return;
            }

            setActive(btn);
        });

        // ------------------------------------------------
        // 選択肢クリック時
        // hidden input に値を入れて表示を更新し、一覧を閉じる
        // ------------------------------------------------
        optionsWrap.addEventListener('click', (e) => {
            const btn = e.target.closest('.payment-select__option');

            if (!btn) {
                return;
            }

            hiddenInput.value = btn.dataset.value;
            setLabels(btn.dataset.value);
            close();
        });

        // ------------------------------------------------
        // セレクト外をクリックしたら閉じる
        // ------------------------------------------------
        document.addEventListener('click', (e) => {
            if (!root.contains(e.target)) {
                close();
            }
        });

        // ------------------------------------------------
        // Escapeキーで閉じる
        // ------------------------------------------------
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                close();
            }
        });
    });
</script>
@endsection