<?php

namespace App\Http\Requests\Seller;

use App\Models\Product;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            && $this->user()?->id === $product->seller_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return collect(parent::rules())
            ->except('stock')
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return collect(parent::messages())
            ->reject(fn (string $message, string $key) => str_starts_with($key, 'stock.'))
            ->all();
    }
}
