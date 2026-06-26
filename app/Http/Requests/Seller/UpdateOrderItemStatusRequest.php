<?php

namespace App\Http\Requests\Seller;

use App\Enums\OrderItemStatus;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderItemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User $seller */
        $seller = $this->user();

        /** @var OrderItem|null $orderItem */
        $orderItem = $this->route('orderItem');

        return $orderItem instanceof OrderItem
            && $orderItem->product->seller_id === $seller->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(OrderItemStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status wajib diisi.',
            'status.enum' => 'Status pesanan tidak valid.',
        ];
    }
}
