import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Package,
    RefreshCcw,
    ShoppingCart,
    Store,
    Tags,
    Trash2,
} from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { checkout } from '@/routes';
import { index as cartIndex } from '@/routes/cart';
import {
    destroy as destroyCartItem,
    update as updateCartItem,
} from '@/routes/cart/items';
import { index as catalogIndex, show as catalogShow } from '@/routes/catalog';

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
    return (
        <>
            <Head title="Cart" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
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
                            <Link href={catalogIndex()}>
                                <ArrowLeft className="size-4" />
                                Katalog
                            </Link>
                        </Button>
                    </section>

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
                                <Table>
                                    <TableHeader>
                                        <TableRow className="border-slate-100 bg-slate-50 hover:bg-slate-50">
                                            {[
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
                                                    colSpan={5}
                                                    className="px-6 py-12 text-center"
                                                >
                                                    <div className="mx-auto flex size-12 items-center justify-center rounded-[8px] bg-blue-50 text-blue-700">
                                                        <ShoppingCart className="size-5" />
                                                    </div>
                                                    <p className="mt-4 text-sm font-medium text-slate-700">
                                                        Cart masih kosong.
                                                    </p>
                                                    <p className="mt-1 text-sm text-slate-500">
                                                        Tambahkan produk dari
                                                        katalog EduCart.
                                                    </p>
                                                </TableCell>
                                            </TableRow>
                                        )}

                                        {items.map((item) => {
                                            const src = imageSource(
                                                item.product.image,
                                            );

                                            return (
                                                <TableRow
                                                    key={item.id}
                                                    className="border-slate-100 hover:bg-slate-50/70"
                                                >
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
                                                            item.product.price,
                                                        )}
                                                        <p className="mt-1 text-xs font-normal text-slate-500">
                                                            Stok{' '}
                                                            {item.product.stock}
                                                        </p>
                                                    </TableCell>
                                                    <TableCell className="px-6 py-4">
                                                        <Form
                                                            {...updateCartItem.form(
                                                                item.id,
                                                            )}
                                                            disableWhileProcessing
                                                            className="space-y-2"
                                                        >
                                                            {({
                                                                processing,
                                                                errors,
                                                            }) => (
                                                                <>
                                                                    <div className="flex items-center gap-2">
                                                                        <Input
                                                                            type="number"
                                                                            name="quantity"
                                                                            min={
                                                                                1
                                                                            }
                                                                            max={
                                                                                item
                                                                                    .product
                                                                                    .stock
                                                                            }
                                                                            defaultValue={
                                                                                item.quantity
                                                                            }
                                                                            className="h-9 w-24 rounded-[8px] border-slate-200 bg-white"
                                                                        />
                                                                        <Button
                                                                            type="submit"
                                                                            variant="outline"
                                                                            size="icon"
                                                                            className="size-9 rounded-[8px] border-slate-200 bg-white"
                                                                            disabled={
                                                                                processing
                                                                            }
                                                                            aria-label="Update quantity"
                                                                        >
                                                                            {processing ? (
                                                                                <Spinner />
                                                                            ) : (
                                                                                <RefreshCcw className="size-4" />
                                                                            )}
                                                                        </Button>
                                                                    </div>
                                                                    <InputError
                                                                        message={
                                                                            errors.quantity
                                                                        }
                                                                    />
                                                                </>
                                                            )}
                                                        </Form>
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
                            </CardContent>
                        </Card>

                        <Card className="h-fit rounded-[8px] border border-slate-100 bg-white shadow-sm">
                            <CardHeader>
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Ringkasan
                                </CardTitle>
                                <CardDescription>
                                    Total sementara cart.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between gap-4 text-sm text-slate-600">
                                    <span>Total item</span>
                                    <span className="font-semibold text-slate-950">
                                        {summary.total_items}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                                    <span className="text-sm font-medium text-slate-600">
                                        Total harga
                                    </span>
                                    <span className="text-xl font-semibold text-slate-950">
                                        {formatRupiah(summary.total_price)}
                                    </span>
                                </div>
                                <Form
                                    {...checkout.form()}
                                    disableWhileProcessing
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            disabled={
                                                processing ||
                                                items.length === 0
                                            }
                                            className="h-10 w-full rounded-[8px] bg-[#0080FF] hover:bg-[#006FE0]"
                                        >
                                            {processing && <Spinner />}
                                            Checkout
                                        </Button>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>
                    </section>
                </div>
            </main>
        </>
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
