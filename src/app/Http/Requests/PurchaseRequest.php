<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
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
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required' => '支払い方法を選択してください。',
            'payment_method_id.integer' => '支払い方法の形式が不正です。',
            'payment_method_id.exists' => '選択した支払い方法は無効です。',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            $item = $this->route('item');

            if (!$user || !$item) {
                return;
            }

            $sessionShipping = session("purchase.shipping.item_{$item->id}", []);

            $postalCode = $sessionShipping['postal_code'] ?? $user->postal_code;
            $address = $sessionShipping['address'] ?? $user->address;

            if ($this->isBlank($postalCode) || $this->isBlank($address)) {
                $validator->errors()->add(
                    'shipping',
                    "配送先住所が未登録です。\n「変更する」から配送先を入力するか、プロフィールに住所を登録してください。"
                );
            }
        });
    }

    private function isBlank($value): bool
    {
        if ($value === null) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return preg_replace('/[\s　]+/u', '', $value) === '';
    }
}
