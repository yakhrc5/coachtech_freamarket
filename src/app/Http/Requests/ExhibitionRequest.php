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
            'image'         => ['required', 'image', 'mimes:jpeg,png', 'max:5120'],
            'category_ids'  => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            'condition_id'  => ['required', 'integer', 'exists:conditions,id'],

            'name'          => ['required', 'string', 'max:255'],
            'brand'         => ['nullable', 'string', 'max:255'],
            'description'   => ['required', 'string', 'max:255'],
            'price'         => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => '商品画像は必須です',
            'image.image' => '商品画像は画像ファイルを選択してください',
            'image.mimes' => '商品画像はjpegまたはpng形式でアップロードしてください',
            'image.max' => '商品画像は5MB以下にしてください',

            'category_ids.required' => 'カテゴリーは必須です',
            'category_ids.array' => 'カテゴリーの形式が不正です',
            'category_ids.min' => 'カテゴリーは1つ以上選択してください',
            'category_ids.*.exists' => '選択したカテゴリーが不正です',

            'condition_id.required' => '商品の状態は必須です',
            'condition_id.exists' => '選択した商品の状態が不正です',

            'name.required' => '商品名は必須です',
            'description.required' => '商品の説明は必須です',
            'description.max' => '商品の説明は255文字以内で入力してください',

            'price.required' => '販売価格は必須です',
            'price.numeric' => '販売価格は数値で入力してください',
            'price.min' => '販売価格は0円以上で入力してください',
        ];
    }
}
