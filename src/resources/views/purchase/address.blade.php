@extends('layouts.app')

@section('title', '送付先住所変更')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase-address.css') }}">
@endsection

@section('content')
<div class="purchase-address">
    <div class="purchase-address__inner">
        <h1 class="purchase-address__title">住所の変更</h1>

        <form
            action="{{ route('purchase.address.update', $item) }}"
            method="POST"
            class="address-form">
            @csrf
            @method('PATCH')

            {{-- 郵便番号 --}}
            <div class="address-form__group">
                <label class="address-form__label" for="postal_code">郵便番号</label>
                <input
                    id="postal_code"
                    type="text"
                    name="postal_code"
                    class="address-form__input"
                    value="{{ old('postal_code', $address['postal_code'] ?? '') }}">

                <div class="address-form__error-area">
                    @error('postal_code')
                    <p class="address-form__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 住所 --}}
            <div class="address-form__group">
                <label class="address-form__label" for="address">住所</label>
                <input
                    id="address"
                    type="text"
                    name="address"
                    class="address-form__input"
                    value="{{ old('address', $address['address'] ?? '') }}">

                <div class="address-form__error-area">
                    @error('address')
                    <p class="address-form__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 建物名 --}}
            <div class="address-form__group">
                <label class="address-form__label" for="building">建物名</label>
                <input
                    id="building"
                    type="text"
                    name="building"
                    class="address-form__input"
                    value="{{ old('building', $address['building'] ?? '') }}">

                <div class="address-form__error-area">
                    @error('building')
                    <p class="address-form__error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="submit" class="address-form__btn">更新する</button>
        </form>
    </div>
</div>
@endsection