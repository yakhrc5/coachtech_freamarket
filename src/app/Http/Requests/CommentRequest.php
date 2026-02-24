<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'body.required' => '商品コメントは必須です',
            'body.max' => '商品コメントは255文字以内で入力してください',
        ];
    }
}
