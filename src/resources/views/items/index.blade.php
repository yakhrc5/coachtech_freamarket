@extends('layouts.app')

@section('title', '商品一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/items-index.css') }}">
@endsection

@section('content')
<div class="items-index">
    @php
    $activeTab = request('tab', 'recommend');
    $keyword = request('keyword');

    $recommendParams = [];
    if ($keyword !== null && $keyword !== '') {
    $recommendParams['keyword'] = $keyword;
    }

    $mylistParams = ['tab' => 'mylist'];
    if ($keyword !== null && $keyword !== '') {
    $mylistParams['keyword'] = $keyword;
    }
    @endphp

    <div class="items-index__tabs">
        <div class="items-index__tabs-inner">
            <div class="items-index__tab-list">
                <a
                    href="{{ route('items.index', $recommendParams) }}"
                    class="items-index__tab {{ $activeTab === 'recommend' ? 'is-active' : '' }}"
                    @if ($activeTab==='recommend' ) aria-current="page" @endif>
                    おすすめ
                </a>

                <a
                    href="{{ route('items.index', $mylistParams) }}"
                    class="items-index__tab {{ $activeTab === 'mylist' ? 'is-active' : '' }}"
                    @if ($activeTab==='mylist' ) aria-current="page" @endif>
                    マイリスト
                </a>
            </div>
        </div>
    </div>

    <div class="items-index__body">
        <div class="items-index__body-inner">
            @if ($items->isEmpty())
            <p class="items-index__empty">商品がありません</p>
            @else
            <div class="items-grid">
                @foreach ($items as $item)
                <article class="item-card">
                    <a
                        href="{{ route('items.show', ['item_id' => $item->id]) }}"
                        class="item-card__link">
                        <div class="item-card__image-wrap">
                            <img
                                src="{{ Storage::url($item->image_path) }}"
                                alt="{{ $item->name }}"
                                class="item-card__image">

                            @if (!empty($item->purchase))
                            <span class="badge-sold">Sold</span>
                            @endif
                        </div>

                        <p class="item-card__name">{{ $item->name }}</p>
                    </a>
                </article>
                @endforeach
            </div>

            <div class="items-index__pagination">
                {{ $items->links('vendor.pagination.app-pagination') }}
            </div>
            @endif
        </div>
    </div>
    @endsection