import { Head, Link, router, usePage } from '@inertiajs/react';
import { Package, Pencil, Plus, Search, Trash2 } from 'lucide-react';
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
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import {
    create as sellerProductsCreate,
    destroy as sellerProductsDestroy,
    edit as sellerProductsEdit,
    index as sellerProductsIndex,
} from '@/routes/seller/products';

type ProductStatus = 'draft' | 'pending' | 'approved' | 'rejected';

type SellerProduct = {
    id: number;
    name: string;
    slug: string;
    category: { id: number; name: string; slug: string };
    price: number;
    stock: number;
    status: { code: ProductStatus; label: string };
};

type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
};

type SellerProductsIndexProps = {
    products: Paginator<SellerProduct>;
    categories: { id: number; name: string; slug: string }[];
    filters: {
        q: string;
        status: string;
        category_id: string | number;
        stock: string;
    };
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

export default function SellerProductsIndex({
    products,
    categories,
    filters,
}: SellerProductsIndexProps) {
    const { flash } = usePage().props;
    const [q, setQ] = useState(filters.q);
    const [status, setStatus] = useState(filters.status || '');
    const [categoryId, setCategoryId] = useState(
        String(filters.category_id || ''),
    );
    const [stock, setStock] = useState(filters.stock || '');
    const [selected, setSelected] = useState<SellerProduct | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState<string>();

    const submitFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            sellerProductsIndex(),
            Object.fromEntries(
                Object.entries({
                    q,
                    status: status === 'all' ? '' : status,
                    category_id: categoryId === 'all' ? '' : categoryId,
                    stock: stock === 'all' ? '' : stock,
                }).filter(([, value]) => value),
            ),
            { preserveState: true, replace: true },
        );
    };

    const resetFilters = () => {
        setQ('');
        setStatus('');
        setCategoryId('');
        setStock('');
        router.get(sellerProductsIndex());
    };

    const deleteProduct = () => {
        if (!selected) {
            return;
        }

        router.delete(sellerProductsDestroy(selected.id), {
            preserveScroll: true,
            onStart: () => {
                setDeleting(true);
                setDeleteError(undefined);
            },
            onFinish: () => setDeleting(false),
            onSuccess: () => setSelected(null),
            onError: (errors) => setDeleteError(errors.product),
        });
    };

    return (
        <>
            <Head title="Produk Seller" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                                <Package className="size-3.5" />
                                {products.total} produk
                            </Badge>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Produk Toko
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Kelola produk, harga, stok, dan status moderasi.
                            </p>
                        </div>
                        <Button
                            asChild
                            className="rounded-[8px] bg-blue-600 text-white hover:bg-blue-700"
                        >
                            <Link href={sellerProductsCreate()}>
                                <Plus className="size-4" /> Tambah Produk
                            </Link>
                        </Button>
                    </section>

                    {(flash.success || flash.error) && (
                        <div
                            role="status"
                            className={cn(
                                'rounded-[8px] border px-4 py-3 text-sm',
                                flash.error
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            )}
                        >
                            {flash.error || flash.success}
                        </div>
                    )}

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle>Filter Produk</CardTitle>
                            <CardDescription>
                                Cari nama produk atau batasi daftar.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-5">
                            <form
                                onSubmit={submitFilters}
                                className="grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_12rem_12rem_10rem_auto]"
                            >
                                <label className="relative">
                                    <span className="sr-only">Cari produk</span>
                                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        value={q}
                                        onChange={(event) =>
                                            setQ(event.target.value)
                                        }
                                        placeholder="Cari produk"
                                        className="rounded-[8px] border-slate-200 bg-white pl-9"
                                    />
                                </label>
                                <label>
                                    <span className="sr-only">Status</span>
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
                                                <SelectItem value="draft">
                                                    Draft
                                                </SelectItem>
                                                <SelectItem value="pending">
                                                    Pending
                                                </SelectItem>
                                                <SelectItem value="approved">
                                                    Disetujui
                                                </SelectItem>
                                                <SelectItem value="rejected">
                                                    Ditolak
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </label>
                                <label>
                                    <span className="sr-only">Kategori</span>
                                    <Select
                                        value={categoryId}
                                        onValueChange={setCategoryId}
                                    >
                                        <SelectTrigger className="w-full rounded-[8px] border-slate-200 bg-white">
                                            <SelectValue placeholder="Pilih kategori" />
                                        </SelectTrigger>
                                        <SelectContent className="rounded-[8px] bg-white text-slate-900 ring-slate-200">
                                            <SelectGroup>
                                                <SelectLabel>
                                                    Kategori
                                                </SelectLabel>
                                                <SelectItem value="all">
                                                    Semua kategori
                                                </SelectItem>
                                                {categories.map((category) => (
                                                    <SelectItem
                                                        key={category.id}
                                                        value={String(
                                                            category.id,
                                                        )}
                                                    >
                                                        {category.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </label>
                                <label>
                                    <span className="sr-only">
                                        Kondisi stok
                                    </span>
                                    <Select
                                        value={stock}
                                        onValueChange={setStock}
                                    >
                                        <SelectTrigger className="w-full rounded-[8px] border-slate-200 bg-white">
                                            <SelectValue placeholder="Pilih kondisi stok" />
                                        </SelectTrigger>
                                        <SelectContent className="rounded-[8px] bg-white text-slate-900 ring-slate-200">
                                            <SelectGroup>
                                                <SelectLabel>
                                                    Kondisi stok
                                                </SelectLabel>
                                                <SelectItem value="all">
                                                    Semua stok
                                                </SelectItem>
                                                <SelectItem value="low">
                                                    Stok rendah
                                                </SelectItem>
                                                <SelectItem value="out">
                                                    Stok habis
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </label>
                                <div className="flex gap-2">
                                    <Button
                                        type="submit"
                                        className="rounded-[8px]"
                                    >
                                        Terapkan
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={resetFilters}
                                        className="rounded-[8px]"
                                    >
                                        Reset
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle>Katalog Produk</CardTitle>
                            <CardDescription>
                                {products.from ?? 0}-{products.to ?? 0} dari{' '}
                                {products.total} produk
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-slate-50 hover:bg-slate-50">
                                            {[
                                                'Nama',
                                                'Kategori',
                                                'Harga',
                                                'Stok',
                                                'Status',
                                                'Aksi',
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
                                                    colSpan={6}
                                                    className="px-5 py-10 text-center text-slate-500"
                                                >
                                                    Tidak ada produk yang sesuai
                                                    filter.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                        {products.data.map((product) => (
                                            <TableRow key={product.id}>
                                                <TableCell className="min-w-56 px-5 font-medium">
                                                    {product.name}
                                                </TableCell>
                                                <TableCell className="px-5 text-slate-600">
                                                    {product.category.name}
                                                </TableCell>
                                                <TableCell className="px-5 font-medium">
                                                    {formatRupiah(
                                                        product.price,
                                                    )}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    {product.stock}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <Badge
                                                        className={cn(
                                                            'rounded-full',
                                                            statusStyles[
                                                                product.status
                                                                    .code
                                                            ],
                                                        )}
                                                    >
                                                        {product.status.label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            variant="outline"
                                                            size="sm"
                                                            className="rounded-[8px]"
                                                        >
                                                            <Link
                                                                href={sellerProductsEdit(
                                                                    product.id,
                                                                )}
                                                            >
                                                                <Pencil className="size-3.5" />{' '}
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => {
                                                                setDeleteError(
                                                                    undefined,
                                                                );
                                                                setSelected(
                                                                    product,
                                                                );
                                                            }}
                                                            className="rounded-[8px] border-rose-200 text-rose-700 hover:bg-rose-50"
                                                        >
                                                            <Trash2 className="size-3.5" />{' '}
                                                            Hapus
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                            {products.last_page > 1 && (
                                <div className="flex items-center justify-between border-t border-slate-100 p-4">
                                    <span className="text-sm text-slate-500">
                                        Halaman {products.current_page} dari{' '}
                                        {products.last_page}
                                    </span>
                                    <div className="flex flex-wrap justify-end gap-2">
                                        {products.links.map((link, index) => {
                                            const label =
                                                index === 0
                                                    ? 'Sebelumnya'
                                                    : index ===
                                                        products.links.length -
                                                            1
                                                      ? 'Berikutnya'
                                                      : link.label;

                                            return (
                                                <Button
                                                    key={`${link.label}-${index}`}
                                                    asChild={Boolean(link.url)}
                                                    disabled={!link.url}
                                                    variant={
                                                        link.active
                                                            ? 'default'
                                                            : 'outline'
                                                    }
                                                    size="sm"
                                                    className="rounded-[8px]"
                                                    aria-current={
                                                        link.active
                                                            ? 'page'
                                                            : undefined
                                                    }
                                                >
                                                    {link.url ? (
                                                        <Link href={link.url}>
                                                            {label}
                                                        </Link>
                                                    ) : (
                                                        <span>{label}</span>
                                                    )}
                                                </Button>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </main>

            <Dialog
                open={Boolean(selected)}
                onOpenChange={(open) => !open && !deleting && setSelected(null)}
            >
                <DialogContent
                    className="rounded-[12px]"
                    showCloseButton={!deleting}
                >
                    <DialogHeader>
                        <DialogTitle>Hapus produk?</DialogTitle>
                        <DialogDescription>
                            {selected?.name} akan dihapus permanen. Produk
                            dengan riwayat pesanan tidak dapat dihapus.
                        </DialogDescription>
                        {deleteError && (
                            <p
                                role="alert"
                                className="rounded-[8px] border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700"
                            >
                                {deleteError}
                            </p>
                        )}
                    </DialogHeader>
                    <DialogFooter>
                        <DialogClose asChild>
                            <Button variant="outline" disabled={deleting}>
                                Batal
                            </Button>
                        </DialogClose>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={deleting}
                            onClick={deleteProduct}
                        >
                            {deleting ? 'Menghapus...' : 'Hapus'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

SellerProductsIndex.layout = {
    breadcrumbs: [{ title: 'Produk Seller', href: sellerProductsIndex() }],
};
