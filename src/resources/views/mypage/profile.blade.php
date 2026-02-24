@extends('layouts.app')

@section('title', 'プロフィール設定')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')
<div class="profile-edit">
    <div class="profile-edit__inner">
        <h2 class="profile-edit__title">プロフィール設定</h2>

        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-edit__form">
            @csrf
            @method('PATCH')

            <div class="profile-edit__image-row">
                <div class="profile-edit__avatar">
                    @if (!empty($user->profile_image_path))
                    <img src="{{ Storage::url($user->profile_image_path) }}" alt="プロフィール画像" class="profile-edit__avatar-img">
                    @else
                    <div class="profile-edit__avatar-placeholder"></div>
                    @endif
                </div>

                <label class="profile-edit__image-btn">
                    画像を選択する
                    <input type="file" name="profile_image" class="profile-edit__image-input" accept=".jpg,.jpeg,.png">
                </label>
            </div>

            @error('profile_image')
            <p class="profile-edit__error">{{ $message }}</p>
            @enderror

            <div class="form-group">
                <label class="form-label" for="name">ユーザー名</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    class="form-input"
                    value="{{ old('name', $user->name) }}">
                @error('name')
                <p class="profile-edit__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="postal_code">郵便番号</label>
                <input
                    id="postal_code"
                    type="text"
                    name="postal_code"
                    class="form-input"
                    value="{{ old('postal_code', $user->postal_code) }}">
                @error('postal_code')
                <p class="profile-edit__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="address">住所</label>
                <input
                    id="address"
                    type="text"
                    name="address"
                    class="form-input"
                    value="{{ old('address', $user->address) }}">
                @error('address')
                <p class="profile-edit__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="building">建物名</label>
                <input
                    id="building"
                    type="text"
                    name="building"
                    class="form-input"
                    value="{{ old('building', $user->building) }}">
                @error('building')
                <p class="profile-edit__error">{{ $message }}</p>
                @enderror
            </div>

            <input type="hidden" name="from" value="{{ request('from') }}">
            <button type="submit" class="profile-edit__submit">更新する</button>
        </form>
    </div>
</div>
@endsection