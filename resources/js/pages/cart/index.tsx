import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    Minus,
    Package,
    Plus,
    ShoppingCart,
    Store,
    Tags,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { home } from '@/routes';
import { index as cartIndex } from '@/routes/cart';
import {
    destroy as destroyCartItem,
    update as updateCartItem,
} from '@/routes/cart/items';
import { show as catalogShow } from '@/routes/catalog';

type CartItem = {
    id: number;
    quantity: number;
    subtotal: number;
    product: {
        id: number;
        name: string;
        slug: string;
        price: number;
        stock: number;
        is_pre_order: boolean;
        pre_order_estimate_days: number | null;
        pre_order_deadline: string | null;
        pre_order_min_quantity: number | null;
        pre_order_note: string | null;
        image: string | null;
        seller: {
            id: number;
            name: string;
        };
        category: {
            id: number;
            name: string;
            slug: string;
        };
    };
};

type CartIndexProps = {
    items: CartItem[];
    summary: {
        total_items: number;
        total_price: number;
    };
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const imageSource = (image: string | null) => {
    if (!image) {
        return null;
    }

    return image.startsWith('http') || image.startsWith('/')
        ? image
        : `/storage/${image}`;
};

export default function CartIndex({ items, summary }: CartIndexProps) {
    const { flash } = usePage().props;
    const [selectedIds, setSelectedIds] = useState<number[]>(
        items.map((item) => item.id),
    );
    const selectedItems = items.filter((item) => selectedIds.includes(item.id));
    const selectedSummary = {
        total_items: selectedItems.reduce(
            (total, item) => total + item.quantity,
            0,
        ),
        total_price: selectedItems.reduce(
            (total, item) => total + item.subtotal,
            0,
        ),
    };
    const hasInvalidStock = selectedItems.some(
        (item) =>
            !item.product.is_pre_order &&
            (item.product.stock <= 0 || item.quantity > item.product.stock),
    );
    const checkoutHref = `/checkout/confirm?items=${selectedIds.join(',')}`;

    const toggleItem = (id: number, checked: boolean) => {
        setSelectedIds((current) =>
            checked
                ? [...current, id]
                : current.filter((itemId) => itemId !== id),
        );
    };

    return (
        <>
            <Head title="Cart" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <Badge className="rounded-[6px] bg-blue-50 text-blue-700">
                                    <ShoppingCart className="size-3.5" />
                                    Buyer Cart
                                </Badge>
                                <Badge className="rounded-[6px] bg-emerald-50 text-emerald-700">
                                    {summary.total_items} item
                                </Badge>
                            </div>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Cart Belanja
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm text-slate-500">
                                Kelola quantity produk sebelum melanjutkan ke
                                checkout.
                            </p>
                        </div>
                        <Button
                            asChild
                            variant="outline"
                            className="h-9 w-fit rounded-[8px] border-slate-200 bg-white"
                        >
                            <Link href={home()}>
                                <ArrowLeft className="size-4" />
                                Home
                            </Link>
                        </Button>
                    </section>

                    {(flash.success || flash.error) && (
                        <div
                            className={`rounded-[8px] border px-4 py-3 text-sm ${
                                flash.error
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                            }`}
                            role="status"
                        >
                            {flash.error || flash.success}
                        </div>
                    )}

                    <section className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
                        <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                            <CardHeader className="flex-row items-center border-b border-slate-100 p-6">
                                <div className="space-y-1">
                                    <CardTitle className="text-xl font-semibold text-slate-950">
                                        Item Cart
                                    </CardTitle>
                                    <CardDescription>
                                        Quantity tidak boleh melebihi stok
                                        produk.
                                    </CardDescription>
                                </div>
                                <CardAction>
                                    <div className="flex size-10 items-center justify-center rounded-[8px] bg-slate-100 text-slate-600">
                                        <Package className="size-5" />
                                    </div>
                                </CardAction>
                            </CardHeader>
                            <CardContent className="p-0">
                                <div className="space-y-3 p-4 md:hidden">
                                    {items.length === 0 && (
                                        <div className="rounded-[8px] border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center">
                                            <div className="mx-auto flex size-12 items-center justify-center rounded-[8px] bg-blue-50 text-blue-700">
                                                <ShoppingCart className="size-5" />
                                            </div>
                                            <p className="mt-4 text-sm font-medium text-slate-700">
                                                Cart masih kosong.
                                            </p>
                                            <p className="mt-1 text-sm text-slate-500">
                                                Tambahkan produk dari Home
                                                EduCart.
                                            </p>
                                            <Button
                                                asChild
                                                variant="outline"
                                                className="mt-4 h-10 rounded-full border-slate-200 bg-white"
                                            >
                                                <Link href={home()}>
                                                    Lihat produk
                                                </Link>
                                            </Button>
                                        </div>
                                    )}

                                    {items.map((item) => {
                                        const src = imageSource(
                                            item.product.image,
                                        );
                                        const hasStockIssue =
                                            !item.product.is_pre_order &&
                                            (item.product.stock <= 0 ||
                                                item.quantity >
                                                    item.product.stock);

                                        return (
                                            <div
                                                key={item.id}
                                                className="rounded-[8px] border border-slate-200 bg-white p-3 shadow-sm"
                                            >
                                                <div className="flex gap-3">
                                                    <Checkbox
                                                        checked={selectedIds.includes(
                                                            item.id,
                                                        )}
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            toggleItem(
                                                                item.id,
                                                                checked ===
                                                                    true,
                                                            )
                                                        }
                                                        aria-label={`Pilih ${item.product.name} untuk checkout`}
                                                        className="mt-7"
                                                    />
                                                    <Link
                                                        href={catalogShow(
                                                            item.product.slug,
                                                        )}
                                                        className="flex min-w-0 flex-1 gap-3"
                                                    >
                                                        <div className="size-20 shrink-0 overflow-hidden rounded-[8px] bg-blue-50 text-blue-700">
                                                            {src ? (
                                                                <img
                                                                    src={src}
                                                                    alt={
                                                                        item
                                                                            .product
                                                                            .name
                                                                    }
                                                                    className="size-full object-cover"
                                                                />
                                                            ) : (
                                                                <div className="flex size-full items-center justify-center">
                                                                    <Package className="size-6" />
                                                                </div>
                                                            )}
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="line-clamp-2 font-semibold text-slate-950">
                                                                {
                                                                    item.product
                                                                        .name
                                                                }
                                                            </p>
                                                            <p className="mt-1 text-sm font-semibold text-slate-950">
                                                                {formatRupiah(
                                                                    item.product
                                                                        .price,
                                                                )}
                                                            </p>
                                                            <p
                                                                className={`mt-1 text-xs ${
                                                                    hasStockIssue
                                                                        ? 'text-rose-600'
                                                                        : 'text-slate-500'
                                                                }`}
                                                            >
                                                                {item.product
                                                                    .is_pre_order
                                                                    ? `Pre-Order ${item.product.pre_order_estimate_days} hari`
                                                                    : `Stok ${item.product.stock}`}
                                                            </p>
                                                        </div>
                                                    </Link>
                                                </div>

                                                <div className="mt-4 flex items-end justify-between gap-3 border-t border-slate-100 pt-3">
                                                    <QuantityStepper
                                                        item={item}
                                                        buttonClassName="size-11"
                                                    />
                                                    <div className="text-right">
                                                        <p className="text-xs text-slate-500">
                                                            Subtotal
                                                        </p>
                                                        <p className="font-semibold text-slate-950">
                                                            {formatRupiah(
                                                                item.subtotal,
                                                            )}
                                                        </p>
                                                    </div>
                                                    <Form
                                                        {...destroyCartItem.form(
                                                            item.id,
                                                        )}
                                                        disableWhileProcessing
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                type="submit"
                                                                variant="outline"
                                                                size="icon"
                                                                className="size-11 rounded-[8px] border-rose-200 bg-white text-rose-700 hover:bg-rose-50 hover:text-rose-800"
                                                                disabled={
                                                                    processing
                                                                }
                                                                aria-label="Hapus item cart"
                                                            >
                                                                {processing ? (
                                                                    <Spinner />
                                                                ) : (
                                                                    <Trash2 className="size-4" />
                                                                )}
                                                            </Button>
                                                        )}
                                                    </Form>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                <div className="hidden overflow-x-auto md:block">
                                    <Table className="min-w-[760px]">
                                        <TableHeader>
                                            <TableRow className="border-slate-100 bg-slate-50 hover:bg-slate-50">
                                                {[
                                                    'Pilih',
                                                    'Produk',
                                                    'Harga',
                                                    'Quantity',
                                                    'Subtotal',
                                                    'Aksi',
                                                ].map((heading) => (
                                                    <TableHead
                                                        key={heading}
                                                        className="h-11 px-6 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                                                    >
                                                        {heading}
                                                    </TableHead>
                                                ))}
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {items.length === 0 && (
                                                <TableRow className="border-slate-100">
                                                    <TableCell
                                                        colSpan={6}
                                                        className="px-6 py-12 text-center"
                                                    >
                                                        <div className="mx-auto flex size-12 items-center justify-center rounded-[8px] bg-blue-50 text-blue-700">
                                                            <ShoppingCart className="size-5" />
                                                        </div>
                                                        <p className="mt-4 text-sm font-medium text-slate-700">
                                                            Cart masih kosong.
                                                        </p>
                                                        <p className="mt-1 text-sm text-slate-500">
                                                            Tambahkan produk
                                                            dari Home EduCart.
                                                        </p>
                                                    </TableCell>
                                                </TableRow>
                                            )}

                                            {items.map((item) => {
                                                const src = imageSource(
                                                    item.product.image,
                                                );
                                                const hasStockIssue =
                                                    item.product.stock <= 0 ||
                                                    item.quantity >
                                                        item.product.stock;

                                                return (
                                                    <TableRow
                                                        key={item.id}
                                                        className="border-slate-100 hover:bg-slate-50/70"
                                                    >
                                                        <TableCell className="px-6 py-4">
                                                            <Checkbox
                                                                checked={selectedIds.includes(
                                                                    item.id,
                                                                )}
                                                                onCheckedChange={(
                                                                    checked,
                                                                ) =>
                                                                    toggleItem(
                                                                        item.id,
                                                                        checked ===
                                                                            true,
                                                                    )
                                                                }
                                                                aria-label={`Pilih ${item.product.name} untuk checkout`}
                                                            />
                                                        </TableCell>
                                                        <TableCell className="min-w-[18rem] px-6 py-4">
                                                            <Link
                                                                href={catalogShow(
                                                                    item.product
                                                                        .slug,
                                                                )}
                                                                className="flex min-w-0 items-center gap-3"
                                                            >
                                                                <div className="size-12 shrink-0 overflow-hidden rounded-[8px] bg-blue-50 text-blue-700">
                                                                    {src ? (
                                                                        <img
                                                                            src={
                                                                                src
                                                                            }
                                                                            alt={
                                                                                item
                                                                                    .product
                                                                                    .name
                                                                            }
                                                                            className="size-full object-cover"
                                                                        />
                                                                    ) : (
                                                                        <div className="flex size-full items-center justify-center">
                                                                            <Package className="size-5" />
                                                                        </div>
                                                                    )}
                                                                </div>
                                                                <div className="min-w-0">
                                                                    <p className="truncate font-semibold text-slate-950">
                                                                        {
                                                                            item
                                                                                .product
                                                                                .name
                                                                        }
                                                                    </p>
                                                                    <p className="mt-1 flex items-center gap-1 truncate text-xs text-slate-500">
                                                                        <Tags className="size-3.5" />
                                                                        {
                                                                            item
                                                                                .product
                                                                                .category
                                                                                .name
                                                                        }
                                                                    </p>
                                                                    <p className="mt-1 flex items-center gap-1 truncate text-xs text-slate-500">
                                                                        <Store className="size-3.5" />
                                                                        {
                                                                            item
                                                                                .product
                                                                                .seller
                                                                                .name
                                                                        }
                                                                    </p>
                                                                </div>
                                                            </Link>
                                                        </TableCell>
                                                        <TableCell className="px-6 py-4 font-semibold text-slate-950">
                                                            {formatRupiah(
                                                                item.product
                                                                    .price,
                                                            )}
                                                            <p className="mt-1 text-xs font-normal text-slate-500">
                                                                <span
                                                                    className={
                                                                        hasStockIssue
                                                                            ? 'text-rose-600'
                                                                            : undefined
                                                                    }
                                                                >
                                                                    {item
                                                                        .product
                                                                        .is_pre_order
                                                                        ? `Pre-Order ${item.product.pre_order_estimate_days} hari`
                                                                        : 'Stok '}
                                                                </span>
                                                                {!item.product
                                                                    .is_pre_order &&
                                                                    item.product
                                                                        .stock}
                                                            </p>
                                                        </TableCell>
                                                        <TableCell className="px-6 py-4">
                                                            <QuantityStepper
                                                                item={item}
                                                                buttonClassName="size-9"
                                                            />
                                                        </TableCell>
                                                        <TableCell className="px-6 py-4 font-semibold text-slate-950">
                                                            {formatRupiah(
                                                                item.subtotal,
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="px-6 py-4 text-right">
                                                            <Form
                                                                {...destroyCartItem.form(
                                                                    item.id,
                                                                )}
                                                                disableWhileProcessing
                                                            >
                                                                {({
                                                                    processing,
                                                                }) => (
                                                                    <Button
                                                                        type="submit"
                                                                        variant="outline"
                                                                        size="icon"
                                                                        className="size-9 rounded-[8px] border-rose-200 bg-white text-rose-700 hover:bg-rose-50 hover:text-rose-800"
                                                                        disabled={
                                                                            processing
                                                                        }
                                                                        aria-label="Hapus item cart"
                                                                    >
                                                                        {processing ? (
                                                                            <Spinner />
                                                                        ) : (
                                                                            <Trash2 className="size-4" />
                                                                        )}
                                                                    </Button>
                                                                )}
                                                            </Form>
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            })}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="h-fit rounded-[8px] border border-slate-100 bg-white shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Ringkasan
                                </CardTitle>
                                <CardDescription>
                                    Total item yang dipilih.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between gap-4 text-sm text-slate-600">
                                    <span>Total item</span>
                                    <span className="font-semibold text-slate-950">
                                        {selectedSummary.total_items}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                                    <span className="text-sm font-medium text-slate-600">
                                        Total harga
                                    </span>
                                    <span className="text-xl font-semibold text-slate-950">
                                        {formatRupiah(
                                            selectedSummary.total_price,
                                        )}
                                    </span>
                                </div>
                                <div className="space-y-2">
                                    {selectedIds.length === 0 ||
                                    hasInvalidStock ? (
                                        <Button
                                            type="button"
                                            disabled
                                            className="h-10 w-full rounded-[8px] bg-blue-600 hover:bg-blue-700"
                                        >
                                            Checkout
                                        </Button>
                                    ) : (
                                        <Button
                                            asChild
                                            className="h-10 w-full rounded-[8px] bg-blue-600 hover:bg-blue-700"
                                        >
                                            <Link href={checkoutHref}>
                                                Checkout
                                            </Link>
                                        </Button>
                                    )}
                                    {selectedIds.length === 0 && (
                                        <p className="text-xs text-slate-500">
                                            Pilih minimal satu item untuk
                                            checkout.
                                        </p>
                                    )}
                                    {hasInvalidStock && (
                                        <p className="text-xs text-rose-600">
                                            Ada item dengan stok tidak cukup.
                                            Update quantity atau hapus item
                                            tersebut.
                                        </p>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </main>
        </>
    );
}

function QuantityStepper({
    item,
    buttonClassName,
}: {
    item: CartItem;
    buttonClassName: string;
}) {
    return (
        <Form
            {...updateCartItem.form(item.id)}
            disableWhileProcessing
            className="space-y-2"
        >
            {({ processing, errors }) => (
                <>
                    <div className="flex items-center gap-2">
                        <Button
                            type="submit"
                            name="quantity"
                            value={item.quantity - 1}
                            variant="outline"
                            size="icon"
                            className={`${buttonClassName} rounded-[8px] border-slate-200 bg-white`}
                            disabled={processing || item.quantity <= 1}
                            aria-label="Kurangi quantity"
                        >
                            {processing ? (
                                <Spinner />
                            ) : (
                                <Minus className="size-4" />
                            )}
                        </Button>
                        <span className="min-w-8 text-center text-sm font-semibold text-slate-950 tabular-nums">
                            {item.quantity}
                        </span>
                        <Button
                            type="submit"
                            name="quantity"
                            value={item.quantity + 1}
                            variant="outline"
                            size="icon"
                            className={`${buttonClassName} rounded-[8px] border-slate-200 bg-white`}
                            disabled={
                                processing ||
                                (!item.product.is_pre_order &&
                                    item.quantity >= item.product.stock)
                            }
                            aria-label="Tambah quantity"
                        >
                            {processing ? (
                                <Spinner />
                            ) : (
                                <Plus className="size-4" />
                            )}
                        </Button>
                    </div>
                    <InputError message={errors.quantity} />
                </>
            )}
        </Form>
    );
}

CartIndex.layout = {
    breadcrumbs: [
        {
            title: 'Cart',
            href: cartIndex(),
        },
    ],
};
