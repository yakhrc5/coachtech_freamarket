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
                            <input
                                type="hidden"
                                name="payment_method_id"
                                id="payment_method_id"
                                value="{{ old('payment_method_id', '') }}">

                            <div class="cselect" id="paymentMethodCselect"
                                data-placeholder="選択してください"
                                data-selected="{{ old('payment_method_id', '') }}">
                                <button type="button" class="cselect__trigger" aria-haspopup="listbox" aria-expanded="false">
                                    <span class="cselect__trigger-text">選択してください</span>
                                    <span class="cselect__trigger-arrow"></span>
                                </button>

                                <div class="cselect__panel" role="listbox">
                                    <div class="cselect__options" data-options>
                                        @foreach ($paymentMethods as $paymentMethod)
                                        <button
                                            type="button"
                                            class="cselect__option"
                                            data-value="{{ $paymentMethod->id }}"
                                            data-label="{{ $paymentMethod->name }}"
                                            data-code="{{ $paymentMethod->code }}"
                                            role="option">
                                            <span class="cselect__check">✓</span>
                                            <span class="cselect__label">{{ $paymentMethod->name }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            @error('payment_method_id')
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
                            <p class="purchase-ship__text">{{ $shipping['address'] }}</p>
                            @if(!empty($shipping['building']))
                            <p class="purchase-ship__text">{{ $shipping['building'] }}</p>
                            @endif
                        </div>
                        @error('shipping')
                        <p class="purchase__error">{{ $message }}</p>
                        @enderror
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
                            @php
                            $oldPaymentMethodId = old('payment_method_id');
                            $oldPaymentMethodName = $paymentMethods->firstWhere('id', (int) $oldPaymentMethodId)?->name;
                            @endphp

                            <div class="purchase-summary__cell">
                                <p class="purchase-summary__value" id="paymentMethodPreview">
                                    {{ $oldPaymentMethodName ?: '選択してください' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="purchase-summary__btn">
                        購入する
                    </button>

                    <div class="purchase-note" id="konbiniNote" hidden>
                        <p class="purchase-note__text">
                            「購入する」をクリックすると決済画面が表示されます。</P>
                        <p class="purchase-note__text">
                            コンビニ支払い時は、支払い番号を控えた後にブラウザの「戻る」でお戻りください。</p>
                        <p class="purchase-note__text">
                            購入状態はトップページまたはマイページでご確認ください。</p>
                    </div>

                </aside>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('paymentMethodCselect');

        if (!root) {
            return;
        }

        const trigger = root.querySelector('.cselect__trigger');
        const triggerText = root.querySelector('.cselect__trigger-text');
        const optionsWrap = root.querySelector('[data-options]');
        const hiddenInput = document.getElementById('payment_method_id');
        const preview = document.getElementById('paymentMethodPreview');
        const konbiniNote = document.getElementById('konbiniNote');

        const placeholder = root.dataset.placeholder || '選択してください';

        const clearActive = () => {
            optionsWrap.querySelectorAll('.cselect__option.is-active').forEach((el) => {
                el.classList.remove('is-active');
            });
        };

        const setActive = (btn) => {
            if (!btn) {
                return;
            }

            clearActive();
            btn.classList.add('is-active');
        };

        const setLabels = (value) => {
            if (!value) {
                triggerText.textContent = placeholder;

                if (preview) {
                    preview.textContent = placeholder;
                }

                toggleKonbiniNote(null);

                return;
            }

            const btn = optionsWrap.querySelector(
                `.cselect__option[data-value="${CSS.escape(String(value))}"]`
            );

            const label = btn ? btn.dataset.label : placeholder;

            triggerText.textContent = label;

            if (preview) {
                preview.textContent = label;
            }

            toggleKonbiniNote(btn);
        };

        const getSelectedButton = () => {
            const val = hiddenInput.value;

            if (!val) {
                return null;
            }

            return optionsWrap.querySelector(
                `.cselect__option[data-value="${CSS.escape(String(val))}"]`
            );
        };

        const close = () => {
            root.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            clearActive();
        };

        const open = () => {
            root.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');

            const selectedBtn = getSelectedButton();

            if (selectedBtn) {
                setActive(selectedBtn);
            }
        };

        const toggleKonbiniNote = (btn) => {
            if (!konbiniNote) {
                return;
            }

            const code = btn ? btn.dataset.code : '';
            konbiniNote.hidden = code !== 'konbini';
        };

        // 初期表示（old対応）
        setLabels(hiddenInput.value);

        trigger.addEventListener('click', (e) => {
            e.preventDefault();

            if (root.classList.contains('is-open')) {
                close();
                return;
            }

            open();
        });

        optionsWrap.addEventListener('mouseover', (e) => {
            const btn = e.target.closest('.cselect__option');

            if (!btn) {
                return;
            }

            setActive(btn);
        });

        optionsWrap.addEventListener('click', (e) => {
            const btn = e.target.closest('.cselect__option');

            if (!btn) {
                return;
            }

            hiddenInput.value = btn.dataset.value;
            setLabels(btn.dataset.value);
            close();
        });

        document.addEventListener('click', (e) => {
            if (!root.contains(e.target)) {
                close();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                close();
            }
        });
    });
</script>
@endsection