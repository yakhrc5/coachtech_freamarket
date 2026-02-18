@extends('layouts.app')

@section('content')
<h1>商品一覧</h1>

@foreach ($items as $item)
<div style="margin-bottom:20px;">
    <img src="{{ asset('storage/' . $item->image_path) }}" width="150">
    <p>{{ $item->name }}</p>
    <p>¥{{ number_format($item->price) }}</p>
</div>
@endforeach

@endsection