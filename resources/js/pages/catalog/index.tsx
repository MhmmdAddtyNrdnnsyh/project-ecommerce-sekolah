import { Form, Head, Link } from '@inertiajs/react';
import { Package, RotateCcw, Search, Store, Tags } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
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
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index as catalogIndex } from '@/routes/catalog';

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
    const [category, setCategory] = useState(filters.category || 'all');
    const hasActiveFilters = filters.search !== '' || filters.category !== '';

    return (
        <>
            <Head title="Katalog Produk" />
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
                                    Katalog produk
                                </span>
                            </span>
                        </Link>

                        <div className="flex flex-wrap items-center gap-2">
                            <Badge className="rounded-[6px] bg-blue-50 text-blue-700">
                                {products.length} produk tersedia
                            </Badge>
                            <Badge className="rounded-[6px] bg-white text-slate-600 ring-1 ring-slate-200">
                                Stok ready
                            </Badge>
                        </div>
                    </header>

                    <section className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-end">
                        <div className="max-w-3xl">
                            <Badge className="mb-4 rounded-[6px] bg-blue-50 text-blue-700">
                                <Tags className="size-3.5" />
                                Produk approved
                            </Badge>
                            <h1 className="text-3xl font-semibold tracking-normal text-slate-950 sm:text-4xl">
                                Temukan perlengkapan belajar yang siap dibeli.
                            </h1>
                            <p className="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                                Semua produk di katalog ini sudah disetujui
                                admin dan memiliki stok tersedia.
                            </p>
                        </div>

                        <Form
                            {...catalogIndex.form()}
                            options={{
                                preserveScroll: true,
                            }}
                            className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="search"
                                        className="text-slate-700"
                                    >
                                        Cari produk
                                    </Label>
                                    <div className="relative">
                                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                        <Input
                                            id="search"
                                            name="search"
                                            defaultValue={filters.search}
                                            placeholder="Nama atau deskripsi"
                                            className="h-11 rounded-[8px] border-slate-200 bg-white pl-9"
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label
                                        htmlFor="category"
                                        className="text-slate-700"
                                    >
                                        Kategori
                                    </Label>
                                    <Select
                                        name="category"
                                        value={category}
                                        onValueChange={setCategory}
                                    >
                                        <SelectTrigger
                                            id="category"
                                            className="h-11 w-full rounded-[8px] border-slate-200 bg-white"
                                        >
                                            <SelectValue placeholder="Semua kategori" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                Semua kategori
                                            </SelectItem>
                                            {categories.map((category) => (
                                                <SelectItem
                                                    key={category.id}
                                                    value={category.slug}
                                                >
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Button
                                        type="submit"
                                        className="h-10 rounded-[8px] bg-[#0080FF] px-4 hover:bg-[#006FE0]"
                                    >
                                        <Search className="size-4" />
                                        Terapkan
                                    </Button>
                                    {hasActiveFilters && (
                                        <Button
                                            asChild
                                            variant="outline"
                                            type="button"
                                            className="h-10 rounded-[8px] border-slate-200"
                                        >
                                            <Link href={catalogIndex()}>
                                                <RotateCcw className="size-4" />
                                                Reset
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </Form>
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
                        <section className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            {products.map((product) => {
                                const src = imageSource(product.image);

                                return (
                                    <Link
                                        key={product.id}
                                        href={`/catalog/${product.slug}`}
                                        className="group block h-full"
                                    >
                                        <Card className="h-full overflow-hidden rounded-[8px] border border-slate-200 bg-white py-0 shadow-sm transition group-hover:-translate-y-0.5 group-hover:shadow-md">
                                            <div className="aspect-[4/3] bg-slate-100">
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
                                            <CardHeader className="space-y-2 p-4 pb-2">
                                                <div className="flex flex-wrap gap-2">
                                                    <Badge className="rounded-[6px] bg-slate-100 text-slate-700">
                                                        <Tags className="size-3.5" />
                                                        {product.category.name}
                                                    </Badge>
                                                    <Badge className="rounded-[6px] bg-emerald-50 text-emerald-700">
                                                        Stok {product.stock}
                                                    </Badge>
                                                </div>
                                                <CardTitle className="line-clamp-2 text-base leading-6 font-semibold text-slate-950">
                                                    {product.name}
                                                </CardTitle>
                                                <CardDescription className="line-clamp-2 text-sm leading-6 text-slate-500">
                                                    {product.description}
                                                </CardDescription>
                                            </CardHeader>
                                            <CardContent className="space-y-3 p-4 pt-0">
                                                <p className="text-xl font-semibold text-slate-950">
                                                    {formatRupiah(
                                                        product.price,
                                                    )}
                                                </p>
                                                <p className="flex items-center gap-1.5 text-xs font-medium text-slate-500">
                                                    <Store className="size-3.5" />
                                                    {product.seller.name}
                                                </p>
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
