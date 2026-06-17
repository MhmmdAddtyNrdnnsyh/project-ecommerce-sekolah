<?php

namespace App\Http\Requests\Seller;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'price' => ['required', 'integer', 'min:1', 'max:100000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama produk wajib diisi.',
            'name.min' => 'Nama produk minimal :min karakter.',
            'name.max' => 'Nama produk maksimal :max karakter.',
            'category_id.required' => 'Kategori produk wajib dipilih.',
            'category_id.exists' => 'Kategori produk yang dipilih tidak valid.',
            'description.required' => 'Deskripsi produk wajib diisi.',
            'description.min' => 'Deskripsi produk minimal :min karakter.',
            'description.max' => 'Deskripsi produk maksimal :max karakter.',
            'price.required' => 'Harga produk wajib diisi.',
            'price.integer' => 'Harga produk harus berupa angka bulat.',
            'price.min' => 'Harga produk minimal Rp 1.',
            'price.max' => 'Harga produk terlalu besar.',
            'stock.required' => 'Stok produk wajib diisi.',
            'stock.integer' => 'Stok produk harus berupa angka bulat.',
            'stock.min' => 'Stok produk tidak boleh negatif.',
            'stock.max' => 'Stok produk terlalu besar.',
            'image.image' => 'File gambar harus berupa gambar.',
            'image.mimes' => 'Gambar produk harus berformat JPG, JPEG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar produk maksimal 2 MB.',
        ];
    }
}
