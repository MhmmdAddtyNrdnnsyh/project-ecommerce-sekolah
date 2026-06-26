import { Head, router } from '@inertiajs/react';
import { Package, Search } from 'lucide-react';
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
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

type ProductStatus = 'draft' | 'pending' | 'approved' | 'rejected';

type AdminProduct = {
    id: number;
    name: string;
    slug: string;
    price: number;
    stock: number;
    order_items_count: number;
    status: { code: ProductStatus; label: string };
    seller: { id: number; name: string; email: string };
    category: { id: number; name: string; slug: string };
};

type Paginator<T> = {
    data: T[];
    from: number | null;
    to: number | null;
    total: number;
};

type Props = {
    products: Paginator<AdminProduct>;
    categories: { id: number; name: string; slug: string }[];
    statuses: { code: ProductStatus; name: string }[];
    filters: { q: string; status: string; category_id: string | number };
};

const statusStyles: Record<ProductStatus, string> = {
    draft: 'bg-slate-100 text-slate-700',
    pending: 'bg-amber-50 text-amber-700',
    approved: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-rose-50 text-rose-700',
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function AdminProductsIndex({
    products,
    categories,
    statuses,
    filters,
}: Props) {
    const [q, setQ] = useState(filters.q);
    const [status, setStatus] = useState(filters.status || '');
    const [categoryId, setCategoryId] = useState(
        String(filters.category_id || ''),
    );

    const submitFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            '/admin/products',
            Object.fromEntries(
                Object.entries({
                    q,
                    status: status === 'all' ? '' : status,
                    category_id: categoryId === 'all' ? '' : categoryId,
                }).filter(([, value]) => value),
            ),
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Admin Produk" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section>
                        <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                            <Package className="size-3.5" /> {products.total}{' '}
                            produk
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Produk
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pantau produk seller, status moderasi, stok, dan
                            penjualan.
                        </p>
                    </section>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle>Daftar Produk</CardTitle>
                            <CardDescription>
                                {products.from ?? 0}-{products.to ?? 0} dari{' '}
                                {products.total} produk
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <form
                                onSubmit={submitFilters}
                                className="grid gap-3 border-b border-slate-100 p-5 lg:grid-cols-[1fr_12rem_12rem_auto]"
                            >
                                <label className="relative">
                                    <span className="sr-only">Cari produk</span>
                                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        value={q}
                                        onChange={(event) =>
                                            setQ(event.target.value)
                                        }
                                        placeholder="Nama, slug, atau seller"
                                        className="rounded-[8px] border-slate-200 bg-white pl-9"
                                    />
                                </label>
                                <Select
                                    value={status}
                                    onValueChange={setStatus}
                                >
                                    <SelectTrigger className="w-full rounded-[8px] border-slate-200 bg-white">
                                        <SelectValue placeholder="Pilih status" />
                                    </SelectTrigger>
                                    <SelectContent className="rounded-[8px] bg-white text-slate-900 ring-slate-200">
                                        <SelectGroup>
                                            <SelectLabel>Status</SelectLabel>
                                            <SelectItem value="all">
                                                Semua status
                                            </SelectItem>
                                            {statuses.map((item) => (
                                                <SelectItem
                                                    key={item.code}
                                                    value={item.code}
                                                >
                                                    {item.name}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <Select
                                    value={categoryId}
                                    onValueChange={setCategoryId}
                                >
                                    <SelectTrigger className="w-full rounded-[8px] border-slate-200 bg-white">
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent className="rounded-[8px] bg-white text-slate-900 ring-slate-200">
                                        <SelectGroup>
                                            <SelectLabel>Kategori</SelectLabel>
                                            <SelectItem value="all">
                                                Semua kategori
                                            </SelectItem>
                                            {categories.map((category) => (
                                                <SelectItem
                                                    key={category.id}
                                                    value={String(category.id)}
                                                >
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                                <Button type="submit" className="rounded-[8px]">
                                    Terapkan
                                </Button>
                            </form>

                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50">
                                        {[
                                            'Produk',
                                            'Seller',
                                            'Kategori',
                                            'Harga',
                                            'Stok',
                                            'Status',
                                            'Terjual',
                                        ].map((heading) => (
                                            <TableHead
                                                key={heading}
                                                className="px-5"
                                            >
                                                {heading}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {products.data.length === 0 && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="py-10 text-center text-slate-500"
                                            >
                                                Tidak ada produk.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {products.data.map((product) => (
                                        <TableRow key={product.id}>
                                            <TableCell className="px-5">
                                                <div className="font-medium text-slate-950">
                                                    {product.name}
                                                </div>
                                                <div className="text-xs text-slate-500">
                                                    {product.slug}
                                                </div>
                                            </TableCell>
                                            <TableCell className="px-5">
                                                <div>{product.seller.name}</div>
                                                <div className="text-xs text-slate-500">
                                                    {product.seller.email}
                                                </div>
                                            </TableCell>
                                            <TableCell className="px-5">
                                                {product.category.name}
                                            </TableCell>
                                            <TableCell className="px-5">
                                                {formatRupiah(product.price)}
                                            </TableCell>
                                            <TableCell className="px-5">
                                                {product.stock}
                                            </TableCell>
                                            <TableCell className="px-5">
                                                <Badge
                                                    className={cn(
                                                        'rounded-[6px]',
                                                        statusStyles[
                                                            product.status.code
                                                        ],
                                                    )}
                                                >
                                                    {product.status.label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="px-5">
                                                {product.order_items_count}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

AdminProductsIndex.layout = {
    breadcrumbs: [{ title: 'Produk', href: '/admin/products' }],
};
