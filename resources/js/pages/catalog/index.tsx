import { Head, Link, usePage } from '@inertiajs/react';
import { Package, Search, Store, Tags } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';
import { show as catalogShow } from '@/routes/catalog';
import type { Auth } from '@/types';

type CatalogCategory = {
    id: number;
    name: string;
    slug: string;
};

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
};

type CatalogIndexProps = {
    categories: CatalogCategory[];
    filters: {
        search: string;
        category: string;
    };
    products: CatalogProduct[];
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

export default function CatalogIndex({
    categories,
    filters,
    products,
}: CatalogIndexProps) {
    const { auth } = usePage<PageProps>().props;
    const greetingName =
        auth.user?.role === 'buyer'
            ? auth.user.name.split(' ')[0]
            : 'selamat datang';

    return (
        <>
            <Head title="EduCart" />
            <main className="min-h-screen bg-slate-50">
                <div className="mx-auto flex w-full max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                    <section className="pt-2 pb-1">
                        <Badge className="mb-4 rounded-full bg-blue-50 px-3 py-1 text-blue-700">
                            <Tags className="size-3.5" />
                            Produk approved
                        </Badge>
                        <h1 className="max-w-3xl text-3xl leading-tight font-semibold tracking-normal text-slate-950 sm:text-4xl">
                            Halo, {greetingName}. Mau cari apa hari ini?
                        </h1>
                        <p className="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                            Pilih kategori di bawah, atau gunakan search di
                            navbar untuk menemukan produk sekolah yang sudah
                            disetujui admin.
                        </p>
                    </section>

                    <section className="space-y-3">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-800">
                                    Kategori
                                </h2>
                                <p className="mt-1 text-xs text-slate-500">
                                    Filter produk tanpa keluar dari Home.
                                </p>
                            </div>
                            {filters.search && (
                                <Badge className="rounded-full bg-white text-slate-600 ring-1 ring-slate-200">
                                    <Search className="size-3.5" />
                                    {filters.search}
                                </Badge>
                            )}
                        </div>
                        <div className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0">
                            <Button
                                asChild
                                variant={
                                    filters.category === ''
                                        ? 'default'
                                        : 'outline'
                                }
                                className={
                                    filters.category === ''
                                        ? 'h-10 shrink-0 rounded-full bg-[#0080FF] px-4 hover:bg-[#006FE0]'
                                        : 'h-10 shrink-0 rounded-full border-slate-200 bg-white px-4 text-slate-700 hover:bg-slate-50'
                                }
                            >
                                <Link
                                    href={home({
                                        query: filters.search
                                            ? { search: filters.search }
                                            : {},
                                    })}
                                >
                                    All
                                </Link>
                            </Button>
                            {categories.map((category) => (
                                <Button
                                    key={category.id}
                                    asChild
                                    variant={
                                        filters.category === category.slug
                                            ? 'default'
                                            : 'outline'
                                    }
                                    className={
                                        filters.category === category.slug
                                            ? 'h-10 shrink-0 rounded-full bg-[#0080FF] px-4 hover:bg-[#006FE0]'
                                            : 'h-10 shrink-0 rounded-full border-slate-200 bg-white px-4 text-slate-700 hover:bg-slate-50'
                                    }
                                >
                                    <Link
                                        href={home({
                                            query: {
                                                ...(filters.search
                                                    ? {
                                                          search: filters.search,
                                                      }
                                                    : {}),
                                                category: category.slug,
                                            },
                                        })}
                                    >
                                        {category.name}
                                    </Link>
                                </Button>
                            ))}
                        </div>
                    </section>

                    <section className="flex items-end justify-between gap-4">
                        <div className="max-w-3xl">
                            <h2 className="text-xl font-semibold text-slate-950">
                                Produk pilihan
                            </h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Semua item yang tampil sudah siap dilihat buyer.
                            </p>
                        </div>
                        <Badge className="w-fit shrink-0 rounded-full bg-white text-slate-600 ring-1 ring-slate-200">
                            {products.length} produk tersedia
                        </Badge>
                    </section>

                    {products.length === 0 ? (
                        <section className="rounded-[8px] border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
                            <div className="mx-auto flex size-12 items-center justify-center rounded-[8px] bg-blue-50 text-blue-700">
                                <Package className="size-5" />
                            </div>
                            <h2 className="mt-4 text-lg font-semibold text-slate-950">
                                Produk belum ditemukan
                            </h2>
                            <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                                Coba gunakan kata kunci lain atau pilih semua
                                kategori untuk melihat produk yang tersedia.
                            </p>
                        </section>
                    ) : (
                        <section className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 xl:grid-cols-4">
                            {products.map((product) => {
                                const src = imageSource(product.image);

                                return (
                                    <Link
                                        key={product.id}
                                        href={catalogShow(product.slug)}
                                        className="group block h-full rounded-[8px] focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        <Card className="h-full overflow-hidden rounded-[8px] border border-slate-200 bg-white py-0 shadow-sm transition duration-200 group-hover:-translate-y-0.5 group-hover:border-blue-200 group-hover:shadow-md">
                                            <div className="aspect-square bg-slate-100">
                                                {src ? (
                                                    <img
                                                        src={src}
                                                        alt={product.name}
                                                        className="size-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="flex size-full items-center justify-center bg-blue-50 text-blue-700">
                                                        <Package className="size-9" />
                                                    </div>
                                                )}
                                            </div>
                                            <CardHeader className="space-y-2 p-3 pb-2 sm:p-4 sm:pb-2">
                                                <div className="flex flex-wrap gap-2">
                                                    <Badge className="rounded-full bg-slate-100 text-slate-700">
                                                        <Tags className="size-3.5" />
                                                        {product.category.name}
                                                    </Badge>
                                                    <Badge className="rounded-full bg-emerald-50 text-emerald-700">
                                                        Stok {product.stock}
                                                    </Badge>
                                                </div>
                                                <CardTitle className="line-clamp-2 text-sm leading-5 font-semibold text-slate-950 sm:text-base sm:leading-6">
                                                    {product.name}
                                                </CardTitle>
                                                <CardDescription className="line-clamp-2 text-xs leading-5 text-slate-500 sm:text-sm sm:leading-6">
                                                    {product.description}
                                                </CardDescription>
                                            </CardHeader>
                                            <CardContent className="space-y-3 p-3 pt-0 sm:p-4 sm:pt-0">
                                                <p className="text-base font-semibold text-slate-950 sm:text-xl">
                                                    {formatRupiah(
                                                        product.price,
                                                    )}
                                                </p>
                                                <div className="flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                                    <p className="flex min-w-0 items-center gap-1.5 text-xs font-medium text-slate-500">
                                                        <Store className="size-3.5 shrink-0" />
                                                        <span className="truncate">
                                                            {product.owner.name}
                                                        </span>
                                                    </p>
                                                    <span className="text-xs font-semibold text-blue-700">
                                                        Detail
                                                    </span>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </Link>
                                );
                            })}
                        </section>
                    )}
                </div>
            </main>
        </>
    );
}
