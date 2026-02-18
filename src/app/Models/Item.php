<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'user_id',
        'condition_id',
        'name',
        'brand',
        'description',
        'price',
        'image_path',
    ];

    // 出品者
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 状態
    public function condition()
    {
        return $this->belongsTo(Condition::class);
    }

    // カテゴリ（多対多）
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    // いいね
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    // コメント
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // 購入（1商品1回）
    public function purchase()
    {
        return $this->hasOne(Purchase::class);
    }
}
