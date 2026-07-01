import { Form, Head, usePage } from '@inertiajs/react';
import {
    CreditCard,
    Minus,
    Package,
    Plus,
    ReceiptText,
    Search,
    ShoppingCart,
    Store,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type PosProduct = {
    id: number;
    source: 'consignment' | 'product';
    seller_name: string;
    product_name: string;
    price: number;
    available_quantity: number;
};

type DailyReportItem = {
    product_name: string;
    quantity: number;
    subtotal: number;
};

type CartItem = PosProduct & {
    quantity: number;
};

type Props = {
    up_jurusan: { id: number; name: string } | null;
    pos_products: PosProduct[];
    daily_report: {
        date: string;
        total_sold: number;
        total_revenue: number;
        items: DailyReportItem[];
    };
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function PicketUpJurusanConsignments({
    up_jurusan,
    pos_products,
    daily_report,
}: Props) {
    const { flash } = usePage().props;
    const [query, setQuery] = useState('');
    const [cart, setCart] = useState<CartItem[]>([]);
    const filteredProducts = useMemo(() => {
        const keyword = query.trim().toLowerCase();

        if (!keyword) {
            return pos_products;
        }

        return pos_products.filter((product) =>
            `${product.product_name} ${product.seller_name}`
                .toLowerCase()
                .includes(keyword),
        );
    }, [pos_products, query]);
    const cartSubtotal = cart.reduce(
        (total, item) => total + item.price * item.quantity,
        0,
    );
    const cartQuantity = cart.reduce((total, item) => total + item.quantity, 0);

    const setQuantity = (product: PosProduct, quantity: number) => {
        const nextQuantity = Math.max(
            0,
            Math.min(quantity, product.available_quantity),
        );

        setCart((items) => {
            const exists = items.some(
                (item) =>
                    item.id === product.id && item.source === product.source,
            );

            if (nextQuantity === 0) {
                return items.filter(
                    (item) =>
                        item.id !== product.id ||
                        item.source !== product.source,
                );
            }

            if (exists) {
                return items.map((item) =>
                    item.id === product.id && item.source === product.source
                        ? { ...item, quantity: nextQuantity }
                        : item,
                );
            }

            return [...items, { ...product, quantity: nextQuantity }];
        });
    };

    const quantityFor = (product: PosProduct) =>
        cart.find(
            (item) => item.id === product.id && item.source === product.source,
        )?.quantity ?? 0;

    return (
        <>
            <Head title="POS UP Jurusan" />
            <main className="min-h-dvh bg-slate-50 p-4 text-slate-950 sm:p-6">
                <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
                    <div className="space-y-4">
                        <section className="rounded-[8px] border border-slate-100 bg-white p-4 shadow-sm">
                            <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                                <div>
                                    <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                                        <Store className="size-3.5" />
                                        {up_jurusan?.name ?? 'UP Jurusan'}
                                    </Badge>
                                    <h1 className="text-2xl font-semibold">
                                        POS Picket Officer
                                    </h1>
                                    <p className="mt-1 text-sm text-slate-500">
                                        Pilih produk titipan yang keluar, cek
                                        cart, lalu catat penjualan.
                                    </p>
                                </div>

                                <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    <Summary
                                        label="Produk aktif"
                                        value={pos_products.length}
                                    />
                                    <Summary
                                        label="Terjual hari ini"
                                        value={daily_report.total_sold}
                                    />
                                    <Summary
                                        label="Omzet"
                                        value={formatRupiah(
                                            daily_report.total_revenue,
                                        )}
                                    />
                                </div>
                            </div>
                        </section>

                        {(flash.success || flash.error) && (
                            <div
                                role="status"
                                className={`flex flex-col gap-3 rounded-[8px] border px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between ${
                                    flash.error
                                        ? 'border-rose-200 bg-rose-50 text-rose-700'
                                        : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                }`}
                            >
                                <span>{flash.error || flash.success}</span>
                                {flash.receipt_url && (
                                    <Button
                                        asChild
                                        size="sm"
                                        className="w-fit bg-emerald-600 hover:bg-emerald-700"
                                    >
                                        <a href={flash.receipt_url}>
                                            <ReceiptText className="size-4" />
                                            Lihat Nota
                                        </a>
                                    </Button>
                                )}
                            </div>
                        )}

                        <section className="rounded-[8px] border border-slate-100 bg-white p-4 shadow-sm">
                            <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold">
                                        Menu Produk
                                    </h2>
                                    <p className="text-sm text-slate-500">
                                        Stok aktif yang tersedia di UP Jurusan
                                    </p>
                                </div>
                                <label className="relative block md:w-80">
                                    <span className="sr-only">Cari produk</span>
                                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        value={query}
                                        onChange={(event) =>
                                            setQuery(event.target.value)
                                        }
                                        placeholder="Cari produk atau seller..."
                                        className="pl-9"
                                    />
                                </label>
                            </div>

                            <div className="mt-4 flex gap-2 overflow-x-auto pb-1">
                                <Badge className="rounded-[6px] bg-blue-600 text-white">
                                    Semua {pos_products.length}
                                </Badge>
                                <Badge className="rounded-[6px] bg-slate-100 text-slate-700">
                                    Stok rendah{' '}
                                    {
                                        pos_products.filter(
                                            (product) =>
                                                product.available_quantity <= 3,
                                        ).length
                                    }
                                </Badge>
                                <Badge className="rounded-[6px] bg-slate-100 text-slate-700">
                                    Di cart {cart.length}
                                </Badge>
                            </div>

                            {filteredProducts.length === 0 ? (
                                <div className="mt-4 rounded-[8px] border border-dashed border-slate-300 px-5 py-12 text-center">
                                    <div className="mx-auto flex size-12 items-center justify-center rounded-[8px] bg-blue-50 text-blue-700">
                                        <Package className="size-5" />
                                    </div>
                                    <h3 className="mt-4 text-lg font-semibold">
                                        Produk tidak ditemukan
                                    </h3>
                                    <p className="mx-auto mt-2 max-w-md text-sm text-slate-500">
                                        Coba kata kunci lain atau pastikan
                                        barang sudah diterima admin jurusan.
                                    </p>
                                </div>
                            ) : (
                                <div className="mt-4 grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                                    {filteredProducts.map((product) => {
                                        const quantity = quantityFor(product);

                                        return (
                                            <article
                                                key={`${product.source}:${product.id}`}
                                                className="overflow-hidden rounded-[8px] border border-slate-100 bg-white shadow-sm"
                                            >
                                                <div className="flex aspect-[5/3] items-center justify-center bg-slate-100 text-blue-700">
                                                    <Package className="size-12" />
                                                </div>
                                                <div className="space-y-3 p-3">
                                                    <div className="flex items-start justify-between gap-2">
                                                        <div className="min-w-0">
                                                            <p className="line-clamp-2 font-semibold">
                                                                {
                                                                    product.product_name
                                                                }
                                                            </p>
                                                            <p className="mt-1 text-xs text-slate-500">
                                                                {
                                                                    product.seller_name
                                                                }
                                                            </p>
                                                        </div>
                                                        <Badge className="rounded-[6px] bg-emerald-50 text-emerald-700">
                                                            Stok{' '}
                                                            {
                                                                product.available_quantity
                                                            }
                                                        </Badge>
                                                    </div>
                                                    <div className="flex items-center justify-between gap-3">
                                                        <p className="font-semibold text-blue-700">
                                                            {formatRupiah(
                                                                product.price,
                                                            )}
                                                        </p>
                                                        <Stepper
                                                            value={quantity}
                                                            onMinus={() =>
                                                                setQuantity(
                                                                    product,
                                                                    quantity -
                                                                        1,
                                                                )
                                                            }
                                                            onPlus={() =>
                                                                setQuantity(
                                                                    product,
                                                                    quantity +
                                                                        1,
                                                                )
                                                            }
                                                        />
                                                    </div>
                                                </div>
                                                <Button
                                                    type="button"
                                                    onClick={() =>
                                                        setQuantity(
                                                            product,
                                                            quantity || 1,
                                                        )
                                                    }
                                                    className="h-11 w-full rounded-none"
                                                >
                                                    <ShoppingCart className="size-4" />
                                                    Tambah Cart
                                                </Button>
                                            </article>
                                        );
                                    })}
                                </div>
                            )}
                        </section>
                    </div>

                    <aside className="xl:sticky xl:top-4 xl:h-fit">
                        <section className="rounded-[8px] border border-slate-100 bg-white p-4 shadow-sm">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <h2 className="text-lg font-semibold">
                                        Cart Details
                                    </h2>
                                    <p className="text-sm text-slate-500">
                                        {cartQuantity} item dipilih
                                    </p>
                                </div>
                                {cart.length > 0 && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => setCart([])}
                                        className="h-10 text-rose-600 hover:bg-rose-50 hover:text-rose-700"
                                    >
                                        <Trash2 className="size-4" />
                                        Clear
                                    </Button>
                                )}
                            </div>

                            <div className="mt-4 space-y-3">
                                {cart.length === 0 ? (
                                    <div className="rounded-[8px] border border-dashed border-slate-200 p-5 text-center text-sm text-slate-500">
                                        Cart masih kosong.
                                    </div>
                                ) : (
                                    cart.map((item) => (
                                        <div
                                            key={`${item.source}:${item.id}`}
                                            className="rounded-[8px] border border-slate-200 p-3"
                                        >
                                            <div className="flex justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="line-clamp-2 text-sm font-semibold">
                                                        {item.product_name}
                                                    </p>
                                                    <p className="mt-1 text-xs text-slate-500">
                                                        {item.seller_name}
                                                    </p>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    onClick={() =>
                                                        setQuantity(item, 0)
                                                    }
                                                    className="size-10 shrink-0 text-slate-500 hover:bg-rose-50 hover:text-rose-700"
                                                    aria-label={`Hapus ${item.product_name} dari cart`}
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                            <div className="mt-3 flex items-center justify-between gap-3">
                                                <p className="font-semibold">
                                                    {formatRupiah(
                                                        item.price *
                                                            item.quantity,
                                                    )}
                                                </p>
                                                <Stepper
                                                    value={item.quantity}
                                                    onMinus={() =>
                                                        setQuantity(
                                                            item,
                                                            item.quantity - 1,
                                                        )
                                                    }
                                                    onPlus={() =>
                                                        setQuantity(
                                                            item,
                                                            item.quantity + 1,
                                                        )
                                                    }
                                                />
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            <div className="mt-4 rounded-[8px] bg-slate-50 p-4 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-slate-500">
                                        Sub total
                                    </span>
                                    <span className="font-semibold">
                                        {formatRupiah(cartSubtotal)}
                                    </span>
                                </div>
                                <div className="mt-2 flex justify-between text-base">
                                    <span className="font-semibold">Total</span>
                                    <span className="font-semibold">
                                        {formatRupiah(cartSubtotal)}
                                    </span>
                                </div>
                            </div>

                            <Form
                                action="/picket/up-jurusan/sales"
                                method="post"
                                className="mt-4"
                                onSuccess={() => setCart([])}
                            >
                                {({ processing, errors }) => (
                                    <>
                                        {cart.map((item, index) => (
                                            <div
                                                key={`${item.source}:${item.id}`}
                                            >
                                                <input
                                                    type="hidden"
                                                    name={`items[${index}][id]`}
                                                    value={item.id}
                                                />
                                                <input
                                                    type="hidden"
                                                    name={`items[${index}][source]`}
                                                    value={item.source}
                                                />
                                                <input
                                                    type="hidden"
                                                    name={`items[${index}][quantity]`}
                                                    value={item.quantity}
                                                />
                                            </div>
                                        ))}
                                        <Button
                                            type="submit"
                                            disabled={
                                                processing || cart.length === 0
                                            }
                                            className="h-11 w-full bg-emerald-600 text-white hover:bg-emerald-700"
                                        >
                                            <CreditCard className="size-4" />
                                            Catat Penjualan
                                        </Button>
                                        <InputError
                                            message={
                                                errors.report ??
                                                errors.items ??
                                                errors.quantity ??
                                                errors['items.0.quantity']
                                            }
                                        />
                                    </>
                                )}
                            </Form>
                        </section>
                    </aside>
                </div>
            </main>
        </>
    );
}

function Stepper({
    value,
    onMinus,
    onPlus,
}: {
    value: number;
    onMinus: () => void;
    onPlus: () => void;
}) {
    return (
        <div className="flex items-center rounded-[8px] border border-slate-200">
            <Button
                type="button"
                variant="ghost"
                onClick={onMinus}
                disabled={value === 0}
                className="size-10 text-slate-700"
                aria-label="Kurangi jumlah"
            >
                <Minus className="size-4" />
            </Button>
            <span className="w-8 text-center text-sm font-semibold tabular-nums">
                {value}
            </span>
            <Button
                type="button"
                variant="ghost"
                onClick={onPlus}
                className="size-10 bg-blue-600 text-white hover:bg-blue-700"
                aria-label="Tambah jumlah"
            >
                <Plus className="size-4" />
            </Button>
        </div>
    );
}

function Summary({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="rounded-[8px] border border-slate-100 bg-white px-4 py-3">
            <p className="text-xs font-medium text-slate-500">{label}</p>
            <p className="mt-1 font-semibold">{value}</p>
        </div>
    );
}
