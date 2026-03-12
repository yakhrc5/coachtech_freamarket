<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'image'         => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'category_ids'  => ['required', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'condition_id'  => ['required', 'integer', 'exists:conditions,id'],

            'name'          => ['required', 'string', 'max:100'],
            'brand'         => ['nullable', 'string', 'max:50'],
            'description'   => ['required', 'string', 'max:1000'],
            'price'         => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => '商品画像は必須です',
            'image.image' => '商品画像はjpg、jpeg、png形式の画像ファイルを選択してください',
            'image.mimes' => '商品画像はjpg、jpeg、png形式でアップロードしてください',
            'image.max' => '商品画像は5MB以下にしてください',

            'category_ids.required' => 'カテゴリーは必須です',
            'category_ids.array' => 'カテゴリーの形式が不正です',
            'category_ids.*.exists' => '選択したカテゴリーが不正です',

            'condition_id.required' => '商品の状態は必須です',
            'condition_id.exists' => '選択した商品の状態が不正です',

            'name.required' => '商品名は必須です',
            'name.max' => '商品名は100文字以内で入力してください',

            'brand.max' => 'ブランド名は50文字以内で入力してください',

            'description.required' => '商品の説明は必須です',
            'description.max' => '商品の説明は1000文字以内で入力してください',

            'price.required' => '販売価格は必須です',
            'price.numeric' => '販売価格は数値で入力してください',
            'price.min' => '販売価格は0円以上で入力してください',
        ];
    }
}
