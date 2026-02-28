@extends('layouts.app')

@section('title', '商品一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/items-index.css') }}">
@endsection

@section('content')
<div class="items-index">
    <div class="items-index__tabs">
        <div class="items-index__tabs-inner">
            @php
            $activeTab = request('tab', 'recommend');
            @endphp

            <a
                href="{{ route('items.index', array_filter(['keyword' => request('keyword')])) }}"
                class="items-index__tab {{ $activeTab === 'recommend' ? 'is-active' : '' }}">
                おすすめ
            </a>

            <a
                href="{{ route('items.index', array_filter(['tab' => 'mylist', 'keyword' => request('keyword')])) }}"
                class="items-index__tab {{ $activeTab === 'mylist' ? 'is-active' : '' }}">
                マイリスト
            </a>
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
                    <a href="{{ route('items.show', $item) }}" class="item-card__link">
                        <div class="item-card__image-wrap">
                            <img
                                src="{{ Storage::url($item->image_path) }}"
                                alt="{{ $item->name }}"
                                class="item-card__image">

                            @if (!empty($item->purchase))
                            <span class="badge-sold">SOLD</span>
                            @endif
                        </div>

                        <p class="item-card__name">{{ $item->name }}</p>
                    </a>
                </article>
                @endforeach
            </div>

            <div class="items-index__pagination">
                {{ $items->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection