@extends('layouts.app')

@section('title', '商品一覧')

@section('css')
<link rel="stylesheet" href="{{ asset('css/items-index.css') }}">
@endsection

@section('content')
<div class="items-index">
    @php
        // 現在のタブを取得（未指定ならおすすめ）
        $activeTab = request('tab', 'recommend');

        // 検索キーワードを取得
        $keyword = request('keyword');

        // おすすめタブ用のURLパラメータ
        // keyword がある場合だけ引き継ぐ
        $recommendParams = [];
        if ($keyword !== null && $keyword !== '') {
            $recommendParams['keyword'] = $keyword;
        }

        // マイリストタブ用のURLパラメータ
        // tab=mylist を付けたうえで、keyword がある場合はそれも引き継ぐ
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
        </div>
    </div>
</div>
@endsection