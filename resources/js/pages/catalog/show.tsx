import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Package, ShoppingCart, Store, Tags } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { home, login } from '@/routes';
import { store as storeCartItem } from '@/routes/cart/items';
import { confirm as checkoutConfirm } from '@/routes/checkout';
import type { Auth } from '@/types';

type CatalogProduct = {
    id: number;
    name: string;
    slug: string;
    description: string;
    price: number;
    stock: number;
    is_pre_order: boolean;
    fulfillment_type: {
        code: 'ready_stock' | 'pre_order';
        label: string;
    };
    pre_order_estimate_days: number | null;
    pre_order_deadline: string | null;
    pre_order_min_quantity: number | null;
    pre_order_note: string | null;
    image: string | null;
    seller: {
        id: number;
        name: string;
    } | null;
    owner: {
        id: number;
        name: string;
        type: 'seller' | 'up_jurusan';
    };
    category: {
        id: number;
        name: string;
        slug: string;
    };
    pickup_place: {
        id: number;
        name: string;
    } | null;
};

type CatalogShowProps = {
    product: CatalogProduct;
};

type PageProps = {
    auth: Auth;
    flash: {
        success?: string;
        error?: string;
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

export default function CatalogShow({ product }: CatalogShowProps) {
    const { auth, flash } = usePage<PageProps>().props;
    const src = imageSource(product.image);
    const isOutOfStock = !product.is_pre_order && product.stock <= 0;
    const isBuyer = auth.user?.role === 'buyer';

    return (
        <>
            <Head title={product.name} />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50">
                <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                    <div>
                        <Button
                            asChild
                            variant="outline"
                            className="h-10 w-fit rounded-[8px] border-slate-200 bg-white"
                        >
                            <Link href={home()}>
                                <ArrowLeft className="size-4" />
                                Home
                            </Link>
                        </Button>
                    </div>

                    <section className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-start">
                        <div className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
                            <div className="aspect-square bg-slate-100">
                                {src ? (
                                    <img
                                        src={src}
                                        alt={product.name}
                                        className="size-full object-cover"
                                    />
                                ) : (
                                    <div className="flex size-full items-center justify-center bg-blue-50 text-blue-700">
                                        <Package className="size-16" />
                                    </div>
                                )}
                            </div>
                        </div>

                        <Card className="rounded-[8px] border border-slate-200 bg-white py-0 shadow-sm lg:sticky lg:top-24">
                            <CardContent className="space-y-6 p-5 sm:p-6">
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

                                <div className="flex flex-wrap gap-2">
                                    <Badge className="rounded-full bg-blue-50 text-blue-700">
                                        <Tags className="size-3.5" />
                                        {product.category.name}
                                    </Badge>
                                    <Badge
                                        className={
                                            product.is_pre_order
                                                ? 'rounded-full bg-blue-50 text-blue-700'
                                                : isOutOfStock
                                                  ? 'rounded-full bg-orange-50 text-orange-700'
                                                  : 'rounded-full bg-emerald-50 text-emerald-700'
                                        }
                                    >
                                        {product.is_pre_order
                                            ? `Pre-Order ${product.pre_order_estimate_days} hari`
                                            : isOutOfStock
                                              ? 'Stok habis'
                                              : `Stok ${product.stock}`}
                                    </Badge>
                                    {!product.is_pre_order && (
                                        <Badge
                                            className={
                                                isOutOfStock
                                                    ? 'rounded-full bg-orange-50 text-orange-700'
                                                    : 'rounded-full bg-emerald-50 text-emerald-700'
                                            }
                                        >
                                            {isOutOfStock
                                                ? 'Stok habis'
                                                : `Ready Stock`}
                                        </Badge>
                                    )}
                                </div>

                                {product.is_pre_order && (
                                    <div className="rounded-[8px] border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                                        Produk ini memakai sistem pre-order.
                                        Estimasi siap dalam{' '}
                                        {product.pre_order_estimate_days} hari
                                        setelah pesanan dibuat.
                                        {product.pre_order_note && (
                                            <span className="mt-1 block text-blue-700">
                                                {product.pre_order_note}
                                            </span>
                                        )}
                                        {(product.pre_order_deadline ||
                                            product.pre_order_min_quantity) && (
                                            <span className="mt-2 block text-xs text-blue-700">
                                                {product.pre_order_deadline &&
                                                    `Deadline ${product.pre_order_deadline}`}
                                                {product.pre_order_deadline &&
                                                    product.pre_order_min_quantity &&
                                                    ' • '}
                                                {product.pre_order_min_quantity &&
                                                    `Minimum ${product.pre_order_min_quantity} pesanan`}
                                            </span>
                                        )}
                                    </div>
                                )}

                                <div>
                                    <h1 className="text-3xl font-semibold tracking-normal text-slate-950 sm:text-4xl">
                                        {product.name}
                                    </h1>
                                    <p className="mt-3 flex items-center gap-1.5 text-sm font-medium text-slate-500">
                                        <Store className="size-4" />
                                        {product.owner.name}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-3xl font-semibold text-slate-950 tabular-nums">
                                        {formatRupiah(product.price)}
                                    </p>
                                    <p className="mt-1 text-sm text-slate-500">
                                        Harga produk dari {product.owner.name}.
                                    </p>
                                    <p className="mt-2 flex items-center gap-1.5 text-sm text-slate-500">
                                        <Store className="size-4" />
                                        Ambil di{' '}
                                        {product.pickup_place?.name ??
                                            'titik pickup sekolah'}
                                    </p>
                                </div>

                                {isBuyer ? (
                                    <div className="space-y-2">
                                        <div className="flex flex-wrap gap-2">
                                            {isOutOfStock ? (
                                                <Button
                                                    type="button"
                                                    disabled
                                                    className="h-11 w-fit px-5"
                                                >
                                                    Stok habis
                                                </Button>
                                            ) : (
                                                <Button
                                                    asChild
                                                    className="h-11 w-fit px-5"
                                                >
                                                    <Link
                                                        href={checkoutConfirm({
                                                            query: {
                                                                product:
                                                                    product.slug,
                                                            },
                                                        })}
                                                    >
                                                        Beli sekarang
                                                    </Link>
                                                </Button>
                                            )}

                                            <Form
                                                {...storeCartItem.form(
                                                    product.slug,
                                                )}
                                                disableWhileProcessing
                                            >
                                                {({ processing, errors }) => (
                                                    <>
                                                        <input
                                                            type="hidden"
                                                            name="quantity"
                                                            value="1"
                                                            readOnly
                                                        />
                                                        <Button
                                                            type="submit"
                                                            disabled={
                                                                isOutOfStock ||
                                                                processing
                                                            }
                                                            variant="outline"
                                                            size="icon"
                                                            className="size-11 rounded-full border-slate-200 bg-white text-blue-700 hover:bg-blue-50"
                                                            aria-label="Tambahkan ke keranjang"
                                                        >
                                                            {processing ? (
                                                                <Spinner />
                                                            ) : (
                                                                <ShoppingCart className="size-5" />
                                                            )}
                                                        </Button>
                                                        <InputError
                                                            message={
                                                                errors.quantity
                                                            }
                                                        />
                                                    </>
                                                )}
                                            </Form>
                                        </div>
                                    </div>
                                ) : auth.user ? (
                                    <Button
                                        type="button"
                                        disabled
                                        className="h-11 w-full"
                                    >
                                        <ShoppingCart className="size-4" />
                                        Khusus buyer
                                    </Button>
                                ) : isOutOfStock ? (
                                    <Button
                                        type="button"
                                        disabled
                                        className="h-11 w-full"
                                    >
                                        <ShoppingCart className="size-4" />
                                        Stok habis
                                    </Button>
                                ) : (
                                    <Button asChild className="h-11 w-full">
                                        <Link href={login()}>
                                            <ShoppingCart className="size-4" />
                                            Login untuk tambah
                                        </Link>
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    </section>

                    <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <h2 className="text-xl font-semibold text-slate-950">
                            Deskripsi produk
                        </h2>
                        <p className="mt-3 text-base leading-8 whitespace-pre-line text-slate-600">
                            {product.description}
                        </p>
                    </section>
                </div>
            </main>
        </>
    );
}
