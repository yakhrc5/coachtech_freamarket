@extends('layouts.app')

@section('title', 'プロフィール設定')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')
<div class="profile-edit">
    <div class="profile-edit__container">
        <h1 class="profile-edit__title">プロフィール設定</h1>

        <form
            action="{{ route('profile.update') }}"
            method="POST"
            enctype="multipart/form-data"
            class="profile-edit__form"
            novalidate>
            @csrf
            @method('PATCH')

            {{-- プロフィール画像 --}}
            <div class="profile-edit__image-block">
                <div class="profile-edit__image-row">
                    <div class="profile-edit__avatar">
                        @if (!empty($user->profile_image_path))
                        <img
                            src="{{ Storage::url($user->profile_image_path) }}"
                            alt="プロフィール画像"
                            class="profile-edit__avatar-img">
                        @else
                        <div class="profile-edit__avatar-placeholder"></div>
                        @endif
                    </div>

                    <label class="profile-edit__image-btn">
                        画像を選択する
                        <input
                            type="file"
                            name="profile_image"
                            class="profile-edit__image-input"
                            accept="image/jpeg,image/png">
                    </label>
                </div>
                <div class="profile-edit__error-area">
                    @error('profile_image')
                    <p class="profile-edit__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            {{-- ユーザー名 --}}
            <div class="profile-edit__field">
                <label class="profile-edit__label" for="name">ユーザー名</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    class="profile-edit__input"
                    value="{{ old('name', $user->name) }}"
                    autocomplete="name">

                <div class="profile-edit__error-area">
                    @error('name')
                    <p class="profile-edit__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 郵便番号 --}}
            <div class="profile-edit__field">
                <label class="profile-edit__label" for="postal_code">郵便番号</label>
                <input
                    id="postal_code"
                    type="text"
                    name="postal_code"
                    class="profile-edit__input"
                    value="{{ old('postal_code', $user->postal_code) }}"
                    autocomplete="postal-code">

                <div class="profile-edit__error-area">
                    @error('postal_code')
                    <p class="profile-edit__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 住所 --}}
            <div class="profile-edit__field">
                <label class="profile-edit__label" for="address">住所</label>
                <input
                    id="address"
                    type="text"
                    name="address"
                    class="profile-edit__input"
                    value="{{ old('address', $user->address) }}"
                    autocomplete="street-address">

                <div class="profile-edit__error-area">
                    @error('address')
                    <p class="profile-edit__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 建物名 --}}
            <div class="profile-edit__field">
                <label class="profile-edit__label" for="building">建物名</label>
                <input
                    id="building"
                    type="text"
                    name="building"
                    class="profile-edit__input"
                    value="{{ old('building', $user->building) }}"
                    autocomplete="address-line2">

                <div class="profile-edit__error-area">
                    @error('building')
                    <p class="profile-edit__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <input type="hidden" name="from" value="{{ request('from') }}">

            <button type="submit" class="profile-edit__submit">更新する</button>
        </form>
    </div>
</div>
@endsection