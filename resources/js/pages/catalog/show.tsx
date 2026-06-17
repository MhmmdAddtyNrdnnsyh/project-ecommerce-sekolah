import { Form, Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Package, ShoppingCart, Store, Tags } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { index as cartIndex } from '@/routes/cart';
import { store as storeCartItem } from '@/routes/cart/items';
import { index as catalogIndex } from '@/routes/catalog';
import type { Auth } from '@/types';

type CatalogProduct = {
    id: number;
    name: string;
    slug: string;
    description: string;
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

type CatalogShowProps = {
    product: CatalogProduct;
};

type PageProps = {
    auth: Auth;
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
    const { auth } = usePage<PageProps>().props;
    const src = imageSource(product.image);
    const isOutOfStock = product.stock <= 0;

    return (
        <>
            <Head title={product.name} />
            <main className="min-h-screen bg-slate-50">
                <div className="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-5 sm:px-6 lg:px-8">
                    <header className="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <Link
                            href="/"
                            className="flex w-fit items-center gap-3 text-slate-950"
                        >
                            <span className="flex size-10 items-center justify-center rounded-[8px] bg-[#0080FF] text-white">
                                <Package className="size-5" />
                            </span>
                            <span>
                                <span className="block text-lg leading-tight font-semibold">
                                    EduCart
                                </span>
                                <span className="block text-xs font-medium text-slate-500">
                                    Detail produk
                                </span>
                            </span>
                        </Link>

                        <div className="flex flex-wrap items-center gap-2">
                            {auth.user && (
                                <Button
                                    asChild
                                    variant="outline"
                                    className="h-10 w-fit rounded-[8px] border-slate-200 bg-white"
                                >
                                    <Link href={cartIndex()}>
                                        <ShoppingCart className="size-4" />
                                        Cart
                                    </Link>
                                </Button>
                            )}
                            <Button
                                asChild
                                variant="outline"
                                className="h-10 w-fit rounded-[8px] border-slate-200 bg-white"
                            >
                                <Link href={catalogIndex()}>
                                    <ArrowLeft className="size-4" />
                                    Katalog
                                </Link>
                            </Button>
                        </div>
                    </header>

                    <section className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_440px] lg:items-start">
                        <div className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
                            <div className="aspect-[4/3] bg-slate-100">
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

                        <Card className="rounded-[8px] border border-slate-200 bg-white py-0 shadow-sm">
                            <CardContent className="space-y-6 p-5 sm:p-6">
                                <div className="flex flex-wrap gap-2">
                                    <Badge className="rounded-[6px] bg-blue-50 text-blue-700">
                                        <Tags className="size-3.5" />
                                        {product.category.name}
                                    </Badge>
                                    <Badge
                                        className={
                                            isOutOfStock
                                                ? 'rounded-[6px] bg-orange-50 text-orange-700'
                                                : 'rounded-[6px] bg-emerald-50 text-emerald-700'
                                        }
                                    >
                                        {isOutOfStock
                                            ? 'Stok habis'
                                            : `Stok ${product.stock}`}
                                    </Badge>
                                </div>

                                <div>
                                    <h1 className="text-3xl font-semibold tracking-normal text-slate-950 sm:text-4xl">
                                        {product.name}
                                    </h1>
                                    <p className="mt-3 flex items-center gap-1.5 text-sm font-medium text-slate-500">
                                        <Store className="size-4" />
                                        {product.seller.name}
                                    </p>
                                </div>

                                <div>
                                    <p className="text-3xl font-semibold text-slate-950">
                                        {formatRupiah(product.price)}
                                    </p>
                                    <p className="mt-1 text-sm text-slate-500">
                                        Harga produk dari seller EduCart.
                                    </p>
                                </div>

                                {auth.user ? (
                                    <Form
                                        {...storeCartItem.form(product.slug)}
                                        disableWhileProcessing
                                        className="space-y-2"
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
                                                    className="h-11 w-full rounded-[8px] bg-[#0080FF] hover:bg-[#006FE0]"
                                                >
                                                    {processing ? (
                                                        <Spinner />
                                                    ) : (
                                                        <ShoppingCart className="size-4" />
                                                    )}
                                                    {isOutOfStock
                                                        ? 'Stok habis'
                                                        : 'Tambah ke cart'}
                                                </Button>
                                                <InputError
                                                    message={errors.quantity}
                                                />
                                            </>
                                        )}
                                    </Form>
                                ) : isOutOfStock ? (
                                    <Button
                                        type="button"
                                        disabled
                                        className="h-11 w-full rounded-[8px] bg-[#0080FF] hover:bg-[#006FE0]"
                                    >
                                        <ShoppingCart className="size-4" />
                                        Stok habis
                                    </Button>
                                ) : (
                                    <Button
                                        asChild
                                        className="h-11 w-full rounded-[8px] bg-[#0080FF] hover:bg-[#006FE0]"
                                    >
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
