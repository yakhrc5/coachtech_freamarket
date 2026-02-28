@extends('layouts.app')

@section('title', '送付先住所変更')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase-address.css') }}">
@endsection

@section('content')
<div class="purchase-address">
    <div class="purchase-address__inner">
        <div class="purchase-address__head">
            <h2 class="purchase-address__title">送付先住所変更</h2>
            <a href="{{ route('purchase.show', $item) }}" class="purchase-address__back">← 購入画面へ戻る</a>
        </div>

        <div class="purchase-address__card">
            <form action="{{ route('purchase.address.update', $item) }}" method="POST" class="address-form">
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
                        value="{{ old('postal_code', $address['postal_code'] ?? '') }}"
                        placeholder="">

                    @error('postal_code')
                    <p class="address-form__error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 住所 --}}
                <div class="address-form__group">
                    <label class="address-form__label" for="address">住所</label>
                    <input
                        id="address"
                        type="text"
                        name="address"
                        class="address-form__input"
                        value="{{ old('address', $address['address'] ?? '') }}"
                        placeholder="">

                    @error('address')
                    <p class="address-form__error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 建物名 --}}
                <div class="address-form__group">
                    <label class="address-form__label" for="building">建物名</label>
                    <input
                        id="building"
                        type="text"
                        name="building"
                        class="address-form__input"
                        value="{{ old('building', $address['building'] ?? '') }}"
                        placeholder="">
                    @error('building')
                    <p class="address-form__error">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="address-form__btn">更新する</button>
            </form>
        </div>
    </div>
</div>
@endsection