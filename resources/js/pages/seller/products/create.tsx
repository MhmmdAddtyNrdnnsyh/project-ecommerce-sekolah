import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CircleDollarSign,
    Clock3,
    ImagePlus,
    PackagePlus,
    Save,
    Tags,
} from 'lucide-react';
import { useState } from 'react';
import type { CSSProperties } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    index as sellerProductsIndex,
    store as sellerProductsStore,
} from '@/routes/seller/products';

type CategoryOption = {
    id: number;
    name: string;
    slug: string;
};

type SellerProductCreateProps = {
    categories: CategoryOption[];
    upJurusans: { id: number; name: string }[];
};

type SelectTheme = CSSProperties & Record<`--${string}`, string>;

const selectPortalTheme: SelectTheme = {
    '--foreground': '#0F172A',
    '--popover': '#FFFFFF',
    '--popover-foreground': '#0F172A',
    '--muted-foreground': '#64748B',
    '--accent': '#EFF6FF',
    '--accent-foreground': '#1D4ED8',
    '--border': '#E2E8F0',
};

const fieldClassName = 'grid gap-2';
const labelClassName = 'text-sm font-medium text-slate-700';
const inputClassName =
    'h-10 rounded-[8px] border-slate-200 bg-white text-slate-950 shadow-none placeholder:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-blue-100';
const selectTriggerClassName =
    'h-10 w-full rounded-[8px] border-slate-200 bg-white text-slate-950 shadow-none data-[placeholder]:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-blue-100 data-[size=default]:h-10';

export default function SellerProductCreate({
    categories,
    upJurusans,
}: SellerProductCreateProps) {
    const [categoryId, setCategoryId] = useState('');
    const [salesMethod, setSalesMethod] = useState('self_managed');
    const [fulfillmentType, setFulfillmentType] = useState('ready_stock');
    const [status, setStatus] = useState('pending');
    const [upJurusanId, setUpJurusanId] = useState('');

    return (
        <>
            <Head title="Tambah Produk" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="mx-auto max-w-4xl space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <div className="mb-2 inline-flex items-center gap-2 rounded-[6px] bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                <PackagePlus className="size-3.5" />
                                Seller Center
                            </div>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Tambah Produk
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm text-slate-500">
                                Jual mandiri direview super admin, titip barang
                                direview admin jurusan.
                            </p>
                        </div>
                        <Button
                            asChild
                            variant="outline"
                            className="h-9 rounded-[8px] border-slate-200 bg-white"
                        >
                            <Link href={sellerProductsIndex()}>
                                <ArrowLeft className="size-4" />
                                Kembali
                            </Link>
                        </Button>
                    </section>

                    <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                        <CardHeader className="flex-row items-center border-b border-slate-100 p-6">
                            <div className="space-y-1">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Informasi Produk
                                </CardTitle>
                                <CardDescription>
                                    Isi detail produk untuk katalog seller.
                                </CardDescription>
                            </div>
                            <CardAction>
                                <div className="flex size-10 items-center justify-center rounded-[8px] bg-slate-100 text-slate-600">
                                    <Tags className="size-5" />
                                </div>
                            </CardAction>
                        </CardHeader>
                        <CardContent className="p-6">
                            <Form
                                {...sellerProductsStore.form()}
                                disableWhileProcessing
                                className="space-y-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-5 md:grid-cols-2">
                                            <div className={fieldClassName}>
                                                <Label
                                                    htmlFor="name"
                                                    className={labelClassName}
                                                >
                                                    Nama Produk
                                                </Label>
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    required
                                                    autoFocus
                                                    maxLength={120}
                                                    placeholder="Contoh: Pulpen Gel Hitam"
                                                    className={inputClassName}
                                                    aria-invalid={Boolean(
                                                        errors.name,
                                                    )}
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>

                                            <div className={fieldClassName}>
                                                <Label
                                                    htmlFor="category_id"
                                                    className={labelClassName}
                                                >
                                                    Kategori
                                                </Label>
                                                <Select
                                                    name="category_id"
                                                    value={categoryId}
                                                    onValueChange={
                                                        setCategoryId
                                                    }
                                                    required
                                                >
                                                    <SelectTrigger
                                                        id="category_id"
                                                        className={
                                                            selectTriggerClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.category_id,
                                                        )}
                                                    >
                                                        <SelectValue placeholder="Pilih kategori" />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                        style={
                                                            selectPortalTheme
                                                        }
                                                    >
                                                        <SelectGroup>
                                                            <SelectLabel>
                                                                Kategori
                                                            </SelectLabel>
                                                            {categories.map(
                                                                (category) => (
                                                                    <SelectItem
                                                                        key={
                                                                            category.id
                                                                        }
                                                                        value={String(
                                                                            category.id,
                                                                        )}
                                                                    >
                                                                        {
                                                                            category.name
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectGroup>
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={errors.category_id}
                                                />
                                            </div>
                                        </div>

                                        <div className={fieldClassName}>
                                            <Label
                                                htmlFor="description"
                                                className={labelClassName}
                                            >
                                                Deskripsi
                                            </Label>
                                            <Textarea
                                                id="description"
                                                name="description"
                                                required
                                                minLength={10}
                                                maxLength={5000}
                                                placeholder="Jelaskan kondisi, ukuran, varian, atau catatan penting produk."
                                                aria-invalid={Boolean(
                                                    errors.description,
                                                )}
                                            />
                                            <InputError
                                                message={errors.description}
                                            />
                                        </div>

                                        <div className={fieldClassName}>
                                            <Label
                                                htmlFor="sales_method"
                                                className={labelClassName}
                                            >
                                                Metode Penjualan
                                            </Label>
                                            <Select
                                                name="sales_method"
                                                value={salesMethod}
                                                onValueChange={setSalesMethod}
                                                required
                                            >
                                                <SelectTrigger
                                                    id="sales_method"
                                                    className={
                                                        selectTriggerClassName
                                                    }
                                                    aria-invalid={Boolean(
                                                        errors.sales_method,
                                                    )}
                                                >
                                                    <SelectValue placeholder="Pilih metode penjualan" />
                                                </SelectTrigger>
                                                <SelectContent
                                                    style={selectPortalTheme}
                                                >
                                                    <SelectGroup>
                                                        <SelectLabel>
                                                            Metode Penjualan
                                                        </SelectLabel>
                                                        <SelectItem value="self_managed">
                                                            Jual Mandiri
                                                        </SelectItem>
                                                        <SelectItem value="up_jurusan">
                                                            Titip ke UP Jurusan
                                                        </SelectItem>
                                                    </SelectGroup>
                                                </SelectContent>
                                            </Select>
                                            <InputError
                                                message={errors.sales_method}
                                            />
                                        </div>

                                        {salesMethod === 'up_jurusan' ? (
                                            <div className={fieldClassName}>
                                                <Label
                                                    htmlFor="status-note"
                                                    className={labelClassName}
                                                >
                                                    Status Produk
                                                </Label>
                                                <div
                                                    id="status-note"
                                                    className="rounded-[8px] border border-blue-100 bg-blue-50 px-3 py-2 text-sm text-blue-700"
                                                >
                                                    Status otomatis diajukan ke
                                                    admin jurusan. Seller tidak
                                                    perlu mengatur draft atau
                                                    review untuk produk titipan.
                                                </div>
                                            </div>
                                        ) : (
                                            <div className={fieldClassName}>
                                                <Label
                                                    htmlFor="status"
                                                    className={labelClassName}
                                                >
                                                    Status Produk
                                                </Label>
                                                <Select
                                                    name="status"
                                                    value={status}
                                                    onValueChange={setStatus}
                                                    required
                                                >
                                                    <SelectTrigger
                                                        id="status"
                                                        className={
                                                            selectTriggerClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.status,
                                                        )}
                                                    >
                                                        <SelectValue placeholder="Pilih status produk" />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                        style={
                                                            selectPortalTheme
                                                        }
                                                    >
                                                        <SelectGroup>
                                                            <SelectLabel>
                                                                Status Produk
                                                            </SelectLabel>
                                                            <SelectItem value="pending">
                                                                Ajukan Review
                                                            </SelectItem>
                                                            <SelectItem value="draft">
                                                                Simpan Draft
                                                            </SelectItem>
                                                        </SelectGroup>
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={errors.status}
                                                />
                                            </div>
                                        )}

                                        <div className="grid gap-5 md:grid-cols-2">
                                            <div className={fieldClassName}>
                                                <Label
                                                    htmlFor="fulfillment_type"
                                                    className={labelClassName}
                                                >
                                                    Sistem Pemesanan
                                                </Label>
                                                <Select
                                                    name="fulfillment_type"
                                                    value={fulfillmentType}
                                                    onValueChange={
                                                        setFulfillmentType
                                                    }
                                                    required
                                                >
                                                    <SelectTrigger
                                                        id="fulfillment_type"
                                                        className={
                                                            selectTriggerClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.fulfillment_type,
                                                        )}
                                                    >
                                                        <SelectValue placeholder="Pilih sistem pemesanan" />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                        style={
                                                            selectPortalTheme
                                                        }
                                                    >
                                                        <SelectGroup>
                                                            <SelectLabel>
                                                                Sistem
                                                                Pemesanan
                                                            </SelectLabel>
                                                            <SelectItem value="ready_stock">
                                                                Ready Stock
                                                            </SelectItem>
                                                            <SelectItem value="pre_order">
                                                                Pre-Order
                                                            </SelectItem>
                                                        </SelectGroup>
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={
                                                        errors.fulfillment_type
                                                    }
                                                />
                                            </div>

                                            <div className={fieldClassName}>
                                                <Label
                                                    htmlFor="price"
                                                    className={labelClassName}
                                                >
                                                    Harga
                                                </Label>
                                                <div className="relative">
                                                    <CircleDollarSign className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                                    <Input
                                                        id="price"
                                                        name="price"
                                                        type="number"
                                                        required
                                                        min={1}
                                                        max={100000000}
                                                        step={1}
                                                        inputMode="numeric"
                                                        placeholder="5000"
                                                        className={`${inputClassName} pl-9`}
                                                        aria-invalid={Boolean(
                                                            errors.price,
                                                        )}
                                                    />
                                                </div>
                                                <InputError
                                                    message={errors.price}
                                                />
                                            </div>

                                            {salesMethod === 'self_managed' &&
                                                fulfillmentType ===
                                                    'ready_stock' && (
                                                <div className={fieldClassName}>
                                                    <Label
                                                        htmlFor="stock"
                                                        className={
                                                            labelClassName
                                                        }
                                                    >
                                                        Stok
                                                    </Label>
                                                    <Input
                                                        id="stock"
                                                        name="stock"
                                                        type="number"
                                                        required
                                                        min={0}
                                                        max={100000}
                                                        step={1}
                                                        inputMode="numeric"
                                                        placeholder="10"
                                                        className={
                                                            inputClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.stock,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={errors.stock}
                                                    />
                                                </div>
                                            )}

                                            {salesMethod === 'up_jurusan' &&
                                                fulfillmentType ===
                                                    'ready_stock' && (
                                                <div className={fieldClassName}>
                                                    <Label
                                                        htmlFor="requested_quantity"
                                                        className={
                                                            labelClassName
                                                        }
                                                    >
                                                        Jumlah Titip
                                                    </Label>
                                                    <Input
                                                        id="requested_quantity"
                                                        name="requested_quantity"
                                                        type="number"
                                                        required
                                                        min={1}
                                                        max={100000}
                                                        step={1}
                                                        inputMode="numeric"
                                                        placeholder="20"
                                                        className={
                                                            inputClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.requested_quantity,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.requested_quantity
                                                        }
                                                    />
                                                </div>
                                            )}
                                        </div>

                                        {fulfillmentType === 'pre_order' && (
                                            <div className="grid gap-5 md:grid-cols-2">
                                                <div className={fieldClassName}>
                                                    <Label
                                                        htmlFor="pre_order_estimate_days"
                                                        className={
                                                            labelClassName
                                                        }
                                                    >
                                                        Estimasi PO
                                                    </Label>
                                                    <div className="relative">
                                                        <Clock3 className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                                        <Input
                                                            id="pre_order_estimate_days"
                                                            name="pre_order_estimate_days"
                                                            type="number"
                                                            required
                                                            min={1}
                                                            max={365}
                                                            step={1}
                                                            inputMode="numeric"
                                                            placeholder="7"
                                                            className={`${inputClassName} pl-9`}
                                                            aria-invalid={Boolean(
                                                                errors.pre_order_estimate_days,
                                                            )}
                                                        />
                                                    </div>
                                                    <InputError
                                                        message={
                                                            errors.pre_order_estimate_days
                                                        }
                                                    />
                                                </div>

                                                <div className={fieldClassName}>
                                                    <Label
                                                        htmlFor="pre_order_note"
                                                        className={
                                                            labelClassName
                                                        }
                                                    >
                                                        Catatan PO
                                                    </Label>
                                                    <Input
                                                        id="pre_order_note"
                                                        name="pre_order_note"
                                                        maxLength={255}
                                                        placeholder="Contoh: Diproduksi setelah kuota pesanan terkumpul"
                                                        className={
                                                            inputClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.pre_order_note,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.pre_order_note
                                                        }
                                                    />
                                                </div>
                                                <div className={fieldClassName}>
                                                    <Label
                                                        htmlFor="pre_order_deadline"
                                                        className={
                                                            labelClassName
                                                        }
                                                    >
                                                        Deadline PO
                                                    </Label>
                                                    <Input
                                                        id="pre_order_deadline"
                                                        name="pre_order_deadline"
                                                        type="date"
                                                        className={
                                                            inputClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.pre_order_deadline,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.pre_order_deadline
                                                        }
                                                    />
                                                </div>
                                                <div className={fieldClassName}>
                                                    <Label
                                                        htmlFor="pre_order_min_quantity"
                                                        className={
                                                            labelClassName
                                                        }
                                                    >
                                                        Minimum Kuota
                                                    </Label>
                                                    <Input
                                                        id="pre_order_min_quantity"
                                                        name="pre_order_min_quantity"
                                                        type="number"
                                                        min={1}
                                                        max={100000}
                                                        step={1}
                                                        inputMode="numeric"
                                                        placeholder="Opsional"
                                                        className={
                                                            inputClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.pre_order_min_quantity,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.pre_order_min_quantity
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        )}

                                        {salesMethod === 'up_jurusan' && (
                                            <div className={fieldClassName}>
                                                <Label
                                                    htmlFor="up_jurusan_id"
                                                    className={labelClassName}
                                                >
                                                    UP Jurusan
                                                </Label>
                                                <Select
                                                    name="up_jurusan_id"
                                                    value={upJurusanId}
                                                    onValueChange={
                                                        setUpJurusanId
                                                    }
                                                    required
                                                >
                                                    <SelectTrigger
                                                        id="up_jurusan_id"
                                                        className={
                                                            selectTriggerClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.up_jurusan_id,
                                                        )}
                                                    >
                                                        <SelectValue placeholder="Pilih UP Jurusan" />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                        style={
                                                            selectPortalTheme
                                                        }
                                                    >
                                                        <SelectGroup>
                                                            <SelectLabel>
                                                                UP Jurusan
                                                            </SelectLabel>
                                                            {upJurusans.map(
                                                                (up) => (
                                                                    <SelectItem
                                                                        key={
                                                                            up.id
                                                                        }
                                                                        value={String(
                                                                            up.id,
                                                                        )}
                                                                    >
                                                                        {
                                                                            up.name
                                                                        }
                                                                    </SelectItem>
                                                                ),
                                                            )}
                                                        </SelectGroup>
                                                    </SelectContent>
                                                </Select>
                                                <InputError
                                                    message={
                                                        errors.up_jurusan_id
                                                    }
                                                />
                                            </div>
                                        )}

                                        <div className={fieldClassName}>
                                            <Label
                                                htmlFor="image"
                                                className={labelClassName}
                                            >
                                                Gambar Produk
                                            </Label>
                                            <div className="relative">
                                                <ImagePlus className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                                <Input
                                                    id="image"
                                                    name="image"
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/webp"
                                                    className={`${inputClassName} h-11 pl-9 file:mr-3 file:rounded-[6px] file:bg-slate-100 file:px-2 file:text-slate-700`}
                                                    aria-invalid={Boolean(
                                                        errors.image,
                                                    )}
                                                />
                                            </div>
                                            <InputError
                                                message={errors.image}
                                            />
                                        </div>

                                        <div className="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                                            <Button
                                                asChild
                                                type="button"
                                                variant="outline"
                                                className="h-10 rounded-[8px] border-slate-200 bg-white"
                                            >
                                                <Link
                                                    href={sellerProductsIndex()}
                                                >
                                                    Batal
                                                </Link>
                                            </Button>
                                            <Button
                                                type="submit"
                                                className="h-10 rounded-[8px] bg-blue-600 px-4 text-white hover:bg-blue-700"
                                                disabled={processing}
                                            >
                                                {processing && <Spinner />}
                                                <Save className="size-4" />
                                                Simpan Produk
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

SellerProductCreate.layout = {
    breadcrumbs: [
        {
            title: 'Produk Seller',
            href: sellerProductsIndex(),
        },
        {
            title: 'Tambah Produk',
            href: '/seller/products/create',
        },
    ],
};
