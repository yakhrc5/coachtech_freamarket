@extends('layouts.app')

@section('title', '商品の出品')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sell.css') }}">
@endsection

@section('content')
<div class="exhibit">
    <div class="exhibit__inner">
        {{-- ページタイトル --}}
        <h1 class="exhibit__title">商品の出品</h1>

        @if (session('status'))
        <p class="exhibit__flash">{{ session('status') }}</p>
        @endif

        @php
        $selectedCategoryIds = array_map('strval', old('category_ids', []));
        @endphp

        {{-- 出品フォーム --}}
        <form
            action="{{ route('sell.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="exhibit__form"
            novalidate>
            @csrf

            {{-- 商品画像 --}}
            <div class="exhibit__block">
                <label class="exhibit__label" for="image">商品画像</label>

                <div class="exhibit__image-box">
                    <label class="exhibit__file-btn" for="image">画像を選択する</label>
                    <input
                        id="image"
                        type="file"
                        name="image"
                        class="exhibit__file-input"
                        accept=".jpg,.jpeg,.png">
                </div>

                <div class="exhibit__error-area">
                    @error('image')
                    <p class="exhibit__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 商品の詳細 --}}
            <section class="exhibit__section" aria-labelledby="detail-heading">
                <div class="exhibit__section-head">
                    <h2 id="detail-heading" class="exhibit__section-title">商品の詳細</h2>
                    <div class="exhibit__divider"></div>
                </div>

                {{-- カテゴリー --}}
                <fieldset class="exhibit__fieldset exhibit__block">
                    <legend class="exhibit__label">カテゴリー</legend>

                    <div class="exhibit__fieldset-body">
                        <div class="exhibit__chips">
                            @foreach ($categories as $category)
                            @php
                            $checked = in_array((string) $category->id, $selectedCategoryIds, true);
                            @endphp

                            <label class="exhibit-chip">
                                <input
                                    type="checkbox"
                                    name="category_ids[]"
                                    value="{{ $category->id }}"
                                    class="exhibit-chip__input"
                                    {{ $checked ? 'checked' : '' }}>
                                <span class="exhibit-chip__label">{{ $category->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="exhibit__error-area">
                        @error('category_ids')
                        <p class="exhibit__error">{{ $message }}</p>
                        @enderror

                        @error('category_ids.*')
                        <p class="exhibit__error">{{ $message }}</p>
                        @enderror
                    </div>
                </fieldset>

                {{-- 商品の状態 --}}
                <div class="exhibit__block">
                    <p class="exhibit__label">商品の状態</p>

                    {{-- 送信用のhidden input --}}
                    <input
                        type="hidden"
                        name="condition_id"
                        id="condition_id"
                        value="{{ old('condition_id', '') }}">

                    {{-- カスタムドロップダウン --}}
                    <div
                        class="exhibit-select"
                        id="conditionSelect"
                        data-placeholder="選択してください">
                        {{-- 閉じた状態の表示 --}}
                        <button
                            type="button"
                            class="exhibit-select__trigger"
                            aria-haspopup="listbox"
                            aria-expanded="false">
                            <span class="exhibit-select__trigger-text">選択してください</span>
                            <span class="exhibit-select__trigger-arrow"></span>
                        </button>

                        {{-- 開いた状態の選択肢 --}}
                        <div class="exhibit-select__panel" role="listbox">
                            <div class="exhibit-select__options" data-options>
                                @foreach ($conditions as $condition)
                                <button
                                    type="button"
                                    class="exhibit-select__option"
                                    data-value="{{ $condition->id }}"
                                    data-label="{{ $condition->name }}"
                                    role="option">
                                    <span class="exhibit-select__check">✔</span>
                                    <span class="exhibit-select__option-label">{{ $condition->name }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="exhibit__error-area">
                        @error('condition_id')
                        <p class="exhibit__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- 商品名と説明 --}}
            <section class="exhibit__section" aria-labelledby="info-heading">
                <div class="exhibit__section-head">
                    <h2 id="info-heading" class="exhibit__section-title">商品名と説明</h2>
                    <div class="exhibit__divider"></div>
                </div>

                {{-- 商品名 --}}
                <div class="exhibit__block">
                    <label class="exhibit__label" for="name">商品名</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        class="exhibit__input"
                        value="{{ old('name') }}">

                    <div class="exhibit__error-area">
                        @error('name')
                        <p class="exhibit__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- ブランド名 --}}
                <div class="exhibit__block">
                    <label class="exhibit__label" for="brand">ブランド名</label>
                    <input
                        id="brand"
                        type="text"
                        name="brand"
                        class="exhibit__input"
                        value="{{ old('brand') }}">

                    <div class="exhibit__error-area">
                        @error('brand')
                        <p class="exhibit__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- 商品の説明 --}}
                <div class="exhibit__block">
                    <label class="exhibit__label" for="description">商品の説明</label>
                    <textarea
                        id="description"
                        name="description"
                        class="exhibit__textarea">{{ old('description') }}</textarea>

                    <div class="exhibit__error-area">
                        @error('description')
                        <p class="exhibit__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                {{-- 販売価格 --}}
                <div class="exhibit__block">
                    <label class="exhibit__label" for="price">販売価格</label>

                    <div class="exhibit-price">
                        <span class="exhibit-price__prefix">¥</span>
                        <input
                            id="price"
                            type="number"
                            name="price"
                            class="exhibit__input exhibit-price__input"
                            value="{{ old('price') }}"
                            min="1"
                            step="1">
                    </div>

                    <div class="exhibit__error-area">
                        @error('price')
                        <p class="exhibit__error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            {{-- 送信ボタン --}}
            <div class="exhibit__actions">
                <button type="submit" class="exhibit__submit">出品する</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('conditionSelect');

        if (!root) {
            return;
        }

        // 要素を取得
        const trigger = root.querySelector('.exhibit-select__trigger');
        const triggerText = root.querySelector('.exhibit-select__trigger-text');
        const optionsWrap = root.querySelector('[data-options]');
        const hiddenInput = document.getElementById('condition_id');
        const placeholder = root.dataset.placeholder || '選択してください';

        // アクティブ状態を解除
        const clearActive = () => {
            optionsWrap.querySelectorAll('.exhibit-select__option.is-active').forEach((option) => {
                option.classList.remove('is-active');
            });
        };

        // アクティブ状態を付与
        const setActive = (option) => {
            if (!option) {
                return;
            }

            clearActive();
            option.classList.add('is-active');
        };

        // トリガーの表示テキストを更新
        const setTriggerLabel = (value) => {
            if (!value) {
                triggerText.textContent = placeholder;
                return;
            }

            const option = optionsWrap.querySelector(
                `.exhibit-select__option[data-value="${CSS.escape(String(value))}"]`
            );

            triggerText.textContent = option ? option.dataset.label : placeholder;
        };

        // 現在選択中のボタンを取得
        const getSelectedOption = () => {
            const value = hiddenInput.value;

            if (!value) {
                return null;
            }

            return optionsWrap.querySelector(
                `.exhibit-select__option[data-value="${CSS.escape(String(value))}"]`
            );
        };

        // ドロップダウンを閉じる
        const close = () => {
            root.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            clearActive();
        };

        // ドロップダウンを開く
        const open = () => {
            root.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');

            const selectedOption = getSelectedOption();

            if (selectedOption) {
                setActive(selectedOption);
            }
        };

        // 初期表示
        setTriggerLabel(hiddenInput.value);

        // 開閉
        trigger.addEventListener('click', (event) => {
            event.preventDefault();

            if (root.classList.contains('is-open')) {
                close();
                return;
            }

            open();
        });

        // ホバー時の見た目を切り替え
        optionsWrap.addEventListener('mouseover', (event) => {
            const option = event.target.closest('.exhibit-select__option');

            if (!option) {
                return;
            }

            setActive(option);
        });

        // 選択時の処理
        optionsWrap.addEventListener('click', (event) => {
            const option = event.target.closest('.exhibit-select__option');

            if (!option) {
                return;
            }

            hiddenInput.value = option.dataset.value;
            setTriggerLabel(option.dataset.value);
            close();
        });

        // 外側クリックで閉じる
        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) {
                close();
            }
        });

        // ESCキーで閉じる
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
            }
        });
    });
</script>
@endsection