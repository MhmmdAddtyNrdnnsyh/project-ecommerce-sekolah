<?php

namespace App\Http\Requests\Seller;

use App\Enums\ProductFulfillmentType;
use App\Enums\ProductSalesMethod;
use App\Enums\ProductStatus;
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
            'sales_method' => ['nullable', Rule::in(ProductSalesMethod::values())],
            'fulfillment_type' => ['nullable', Rule::in(ProductFulfillmentType::values())],
            'pre_order_estimate_days' => [
                Rule::requiredIf(fn () => $this->input('fulfillment_type', ProductFulfillmentType::ReadyStock->value) === ProductFulfillmentType::PreOrder->value),
                'nullable',
                'integer',
                'min:1',
                'max:365',
            ],
            'pre_order_deadline' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],
            'pre_order_min_quantity' => [
                'nullable',
                'integer',
                'min:1',
                'max:100000',
            ],
            'pre_order_note' => [
                'nullable',
                'string',
                'max:255',
            ],
            'status' => ['nullable', Rule::in([ProductStatus::Draft->value, ProductStatus::Pending->value])],
            'stock' => [
                Rule::requiredIf(fn () => $this->input('sales_method', ProductSalesMethod::SelfManaged->value) !== ProductSalesMethod::UpJurusan->value
                    && $this->input('fulfillment_type', ProductFulfillmentType::ReadyStock->value) !== ProductFulfillmentType::PreOrder->value),
                'nullable',
                'integer',
                'min:0',
                'max:100000',
            ],
            'up_jurusan_id' => [
                Rule::requiredIf(fn () => $this->input('sales_method') === ProductSalesMethod::UpJurusan->value),
                'nullable',
                'integer',
                Rule::exists('up_jurusans', 'id'),
            ],
            'requested_quantity' => [
                Rule::requiredIf(fn () => $this->input('sales_method') === ProductSalesMethod::UpJurusan->value
                    && $this->input('fulfillment_type', ProductFulfillmentType::ReadyStock->value) === ProductFulfillmentType::ReadyStock->value),
                'nullable',
                'integer',
                'min:1',
                'max:100000',
            ],
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
            'sales_method.in' => 'Metode penjualan tidak valid.',
            'fulfillment_type.in' => 'Tipe pemenuhan produk tidak valid.',
            'pre_order_estimate_days.required' => 'Estimasi pre-order wajib diisi.',
            'pre_order_estimate_days.integer' => 'Estimasi pre-order harus berupa angka hari.',
            'pre_order_estimate_days.min' => 'Estimasi pre-order minimal 1 hari.',
            'pre_order_estimate_days.max' => 'Estimasi pre-order maksimal 365 hari.',
            'pre_order_deadline.date' => 'Deadline pre-order harus berupa tanggal valid.',
            'pre_order_deadline.after_or_equal' => 'Deadline pre-order tidak boleh sebelum hari ini.',
            'pre_order_min_quantity.integer' => 'Minimum kuota pre-order harus berupa angka.',
            'pre_order_min_quantity.min' => 'Minimum kuota pre-order minimal 1.',
            'pre_order_min_quantity.max' => 'Minimum kuota pre-order terlalu besar.',
            'pre_order_note.max' => 'Catatan pre-order maksimal :max karakter.',
            'status.in' => 'Status produk tidak valid.',
            'stock.required' => 'Stok produk wajib diisi.',
            'stock.integer' => 'Stok produk harus berupa angka bulat.',
            'stock.min' => 'Stok produk tidak boleh negatif.',
            'stock.max' => 'Stok produk terlalu besar.',
            'up_jurusan_id.required' => 'UP Jurusan wajib dipilih.',
            'up_jurusan_id.exists' => 'UP Jurusan yang dipilih tidak valid.',
            'requested_quantity.required' => 'Jumlah titip wajib diisi.',
            'requested_quantity.integer' => 'Jumlah titip harus berupa angka bulat.',
            'requested_quantity.min' => 'Jumlah titip minimal 1.',
            'requested_quantity.max' => 'Jumlah titip terlalu besar.',
            'image.image' => 'File gambar harus berupa gambar.',
            'image.mimes' => 'Gambar produk harus berformat JPG, JPEG, PNG, atau WEBP.',
            'image.max' => 'Ukuran gambar produk maksimal 2 MB.',
        ];
    }
}
