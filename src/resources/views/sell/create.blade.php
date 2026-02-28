@extends('layouts.app')

@section('title', '商品の出品')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sell.css') }}">
@endsection

@section('content')
<div class="exhibit">
    <div class="exhibit__inner">

        <h2 class="exhibit__title">商品の出品</h2>

        @if (session('status'))
        <p class="exhibit__flash">{{ session('status') }}</p>
        @endif

        <form action="{{ route('sell.store') }}" method="POST" enctype="multipart/form-data" class="exhibit__form" novalidate>
            @csrf

            {{-- 商品画像 --}}
            <div class="exhibit__block">
                <p class="exhibit__label">商品画像</p>

                <div class="exhibit__image-box">
                    <label class="exhibit__file-btn">
                        画像を選択する
                        <input type="file" name="image" class="exhibit__file-input" accept=".jpg,.jpeg,.png">
                    </label>
                </div>

                @error('image')
                <p class="exhibit__error">{{ $message }}</p>
                @enderror
            </div>

            {{-- 商品の詳細 --}}
            <div class="exhibit__section">
                <div class="exhibit__section-head">
                    <p class="exhibit__section-title">商品の詳細</p>
                    <div class="exhibit__divider"></div>
                </div>

                {{-- カテゴリー --}}
                <div class="exhibit__block">
                    <p class="exhibit__subhead">カテゴリー</p>

                    @php
                    $selectedCategoryIds = array_map('strval', old('category_ids', []));
                    @endphp

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

                    @error('category_ids')
                    <p class="exhibit__error">{{ $message }}</p>
                    @enderror
                    @error('category_ids.*')
                    <p class="exhibit__error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 商品の状態 --}}
                <div class="exhibit__block">
                    <p class="exhibit__subhead">商品の状態</p>

                    {{-- 送信用（実際にPOSTされる値） --}}
                    <input
                        type="hidden"
                        name="condition_id"
                        id="condition_id"
                        value="{{ old('condition_id', '') }}">

                    {{-- カスタムドロップダウン --}}
                    <div class="cselect" id="conditionSelect"
                        data-placeholder="選択してください"
                        data-selected="{{ old('condition_id', '') }}">
                        {{-- 閉じたときの見た目 --}}
                        <button type="button" class="cselect__trigger" aria-haspopup="listbox" aria-expanded="false">
                            <span class="cselect__trigger-text">選択してください</span>
                            <span class="cselect__trigger-arrow"></span>
                        </button>

                        {{-- 開いたときのリスト --}}
                        <div class="cselect__panel" role="listbox">
                            {{-- ここはJSで描画（「選択中の項目」を上に出すため） --}}
                            <div class="cselect__options" data-options>
                                @foreach ($conditions as $condition)
                                <button
                                    type="button"
                                    class="cselect__option"
                                    data-value="{{ $condition->id }}"
                                    data-label="{{ $condition->name }}"
                                    role="option">
                                    <span class="cselect__check">✓</span>
                                    <span class="cselect__label">{{ $condition->name }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    @error('condition_id')
                    <p class="exhibit__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 商品名と説明 --}}
            <div class="exhibit__section">
                <div class="exhibit__section-head">
                    <p class="exhibit__section-title exhibit__section-title--muted">商品名と説明</p>
                    <div class="exhibit__divider"></div>
                </div>

                {{-- 商品名 --}}
                <div class="exhibit__block">
                    <label class="exhibit__label" for="name">商品名</label>
                    <input id="name" type="text" name="name" class="exhibit__input" value="{{ old('name') }}">

                    @error('name')
                    <p class="exhibit__error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ブランド名 --}}
                <div class="exhibit__block">
                    <label class="exhibit__label" for="brand">ブランド名</label>
                    <input id="brand" type="text" name="brand" class="exhibit__input" value="{{ old('brand') }}">

                    @error('brand')
                    <p class="exhibit__error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 商品の説明 --}}
                <div class="exhibit__block">
                    <label class="exhibit__label" for="description">商品の説明</label>
                    <textarea id="description" name="description" class="exhibit__textarea">{{ old('description') }}</textarea>

                    @error('description')
                    <p class="exhibit__error">{{ $message }}</p>
                    @enderror
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
                            min="0"
                            step="1">
                    </div>

                    @error('price')
                    <p class="exhibit__error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 出品する --}}
                <div class="exhibit__actions">
                    <button type="submit" class="exhibit__submit">出品する</button>
                </div>

        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('conditionSelect');
        if (!root) return;

        const trigger = root.querySelector('.cselect__trigger');
        const triggerText = root.querySelector('.cselect__trigger-text');
        const optionsWrap = root.querySelector('[data-options]');
        const hiddenInput = document.getElementById('condition_id');

        const placeholder = root.dataset.placeholder || '選択してください';

        const clearActive = () => {
            optionsWrap.querySelectorAll('.cselect__option.is-active').forEach(el => {
                el.classList.remove('is-active');
            });
        };

        const setActive = (btn) => {
            if (!btn) return;
            clearActive();
            btn.classList.add('is-active');
        };

        const setTriggerLabel = (value) => {
            if (!value) {
                triggerText.textContent = placeholder;
                return;
            }
            const btn = optionsWrap.querySelector(`.cselect__option[data-value="${CSS.escape(String(value))}"]`);
            triggerText.textContent = btn ? btn.dataset.label : placeholder;
        };

        const getSelectedButton = () => {
            const val = hiddenInput.value;
            if (!val) return null;
            return optionsWrap.querySelector(`.cselect__option[data-value="${CSS.escape(String(val))}"]`);
        };

        const close = () => {
            root.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            clearActive(); // 閉じたら消しておく
        };

        const open = () => {
            root.classList.add('is-open');
            trigger.setAttribute('aria-expanded', 'true');

            // 選択済みなら、その行を“初期ホバー状態”にする
            const selectedBtn = getSelectedButton();
            if (selectedBtn) setActive(selectedBtn);
        };

        // 初期（old対応）
        setTriggerLabel(hiddenInput.value);

        // 開閉
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            root.classList.contains('is-open') ? close() : open();
        });

        // マウスが当たった行をアクティブ化（=ホバー見た目）
        optionsWrap.addEventListener('mouseover', (e) => {
            const btn = e.target.closest('.cselect__option');
            if (!btn) return;
            setActive(btn);
        });

        // 選択
        optionsWrap.addEventListener('click', (e) => {
            const btn = e.target.closest('.cselect__option');
            if (!btn) return;

            hiddenInput.value = btn.dataset.value;
            setTriggerLabel(btn.dataset.value);
            close();
        });

        // 外側クリックで閉じる
        document.addEventListener('click', (e) => {
            if (!root.contains(e.target)) close();
        });

        // ESCで閉じる
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    });
</script>
@endsection