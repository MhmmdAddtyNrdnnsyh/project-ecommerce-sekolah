import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, Boxes, PackageCheck, Search } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
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
    index as inventoryIndex,
    update as inventoryUpdate,
} from '@/routes/seller/inventory';

type ProductStatus = 'draft' | 'pending' | 'approved' | 'rejected';

type InventoryProduct = {
    id: number;
    name: string;
    slug: string;
    image: string | null;
    status: { code: ProductStatus; label: string };
    stock: number;
    category: { id: number; name: string; slug: string };
    is_low_stock: boolean;
    is_out_of_stock: boolean;
};

type InventoryProps = {
    products: {
        data: InventoryProduct[];
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    summary: { total: number; low_stock: number; out_of_stock: number };
    filters: { q: string; stock: string };
};

const statusStyles: Record<ProductStatus, string> = {
    draft: 'bg-slate-100 text-slate-700',
    pending: 'bg-amber-50 text-amber-700',
    approved: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-rose-50 text-rose-700',
};

export default function SellerInventoryIndex({
    products,
    summary,
    filters,
}: InventoryProps) {
    const { flash } = usePage().props;
    const [q, setQ] = useState(filters.q);
    const [stockFilter, setStockFilter] = useState(filters.stock || '');
    const [selected, setSelected] = useState<InventoryProduct | null>(null);
    const [stock, setStock] = useState('0');
    const [stockError, setStockError] = useState<string>();
    const [processing, setProcessing] = useState(false);

    const openStockEditor = (product: InventoryProduct) => {
        setSelected(product);
        setStock(String(product.stock));
        setStockError(undefined);
    };

    const submitFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            inventoryIndex(),
            Object.fromEntries(
                Object.entries({
                    q,
                    stock: stockFilter === 'all' ? '' : stockFilter,
                }).filter(([, value]) => value),
            ),
            { preserveState: true, replace: true },
        );
    };

    const updateStock = (event: React.FormEvent) => {
        event.preventDefault();

        if (!selected) {
            return;
        }

        router.patch(
            inventoryUpdate(selected.id),
            { stock },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => setSelected(null),
                onError: (errors) => setStockError(errors.stock),
            },
        );
    };

    const summaries = [
        {
            label: 'Total Produk',
            value: summary.total,
            tone: 'text-blue-700',
            icon: Boxes,
        },
        {
            label: 'Stok Rendah',
            value: summary.low_stock,
            tone: 'text-amber-700',
            icon: AlertTriangle,
        },
        {
            label: 'Stok Habis',
            value: summary.out_of_stock,
            tone: 'text-rose-700',
            icon: PackageCheck,
        },
    ];

    return (
        <>
            <Head title="Inventori" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section>
                        <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                            <Boxes className="size-3.5" /> Seller Center
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Inventori
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pantau dan perbarui stok produk toko.
                        </p>
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

                    <section className="grid gap-4 sm:grid-cols-3">
                        {summaries.map(({ label, value, tone, icon: Icon }) => (
                            <Card
                                key={label}
                                className="rounded-[8px] border-slate-100 shadow-sm"
                            >
                                <CardContent className="flex items-center justify-between p-5">
                                    <div>
                                        <p className="text-sm text-slate-500">
                                            {label}
                                        </p>
                                        <p
                                            className={cn(
                                                'mt-1 text-2xl font-semibold',
                                                tone,
                                            )}
                                        >
                                            {value}
                                        </p>
                                    </div>
                                    <Icon className={cn('size-6', tone)} />
                                </CardContent>
                            </Card>
                        ))}
                    </section>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle>Daftar Stok</CardTitle>
                            <CardDescription>
                                {products.from ?? 0}-{products.to ?? 0} dari{' '}
                                {products.total} produk
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <form
                                onSubmit={submitFilters}
                                className="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-[1fr_12rem_auto]"
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
                                    <span className="sr-only">
                                        Kondisi stok
                                    </span>
                                    <Select
                                        value={stockFilter}
                                        onValueChange={setStockFilter}
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
                                        className="rounded-[8px]"
                                        onClick={() =>
                                            router.get(inventoryIndex())
                                        }
                                    >
                                        Reset
                                    </Button>
                                </div>
                            </form>

                            <div className="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow className="bg-slate-50 hover:bg-slate-50">
                                            {[
                                                'Produk',
                                                'Kategori',
                                                'Moderasi',
                                                'Stok',
                                                'Kondisi',
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
                                                    className="py-10 text-center text-slate-500"
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
                                                <TableCell className="px-5 text-lg font-semibold">
                                                    {product.stock}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <Badge
                                                        className={cn(
                                                            'rounded-full',
                                                            product.is_out_of_stock
                                                                ? 'bg-rose-50 text-rose-700'
                                                                : product.is_low_stock
                                                                  ? 'bg-amber-50 text-amber-700'
                                                                  : 'bg-emerald-50 text-emerald-700',
                                                        )}
                                                    >
                                                        {product.is_out_of_stock
                                                            ? 'Habis'
                                                            : product.is_low_stock
                                                              ? 'Rendah'
                                                              : 'Aman'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="px-5 text-right">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        className="rounded-[8px]"
                                                        onClick={() =>
                                                            openStockEditor(
                                                                product,
                                                            )
                                                        }
                                                    >
                                                        Edit stok
                                                    </Button>
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
                                    <div className="flex gap-2">
                                        <Button
                                            asChild={Boolean(
                                                products.prev_page_url,
                                            )}
                                            disabled={!products.prev_page_url}
                                            variant="outline"
                                            size="sm"
                                        >
                                            {products.prev_page_url ? (
                                                <Link
                                                    href={
                                                        products.prev_page_url
                                                    }
                                                >
                                                    Sebelumnya
                                                </Link>
                                            ) : (
                                                <span>Sebelumnya</span>
                                            )}
                                        </Button>
                                        <Button
                                            asChild={Boolean(
                                                products.next_page_url,
                                            )}
                                            disabled={!products.next_page_url}
                                            variant="outline"
                                            size="sm"
                                        >
                                            {products.next_page_url ? (
                                                <Link
                                                    href={
                                                        products.next_page_url
                                                    }
                                                >
                                                    Berikutnya
                                                </Link>
                                            ) : (
                                                <span>Berikutnya</span>
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </main>

            <Dialog
                open={Boolean(selected)}
                onOpenChange={(open) =>
                    !open && !processing && setSelected(null)
                }
            >
                <DialogContent
                    className="rounded-[12px]"
                    showCloseButton={!processing}
                >
                    <DialogHeader>
                        <DialogTitle>Edit stok</DialogTitle>
                        <DialogDescription>
                            Perbarui stok {selected?.name}. Status moderasi
                            produk tidak berubah.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={updateStock} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="stock">Jumlah stok</Label>
                            <Input
                                id="stock"
                                type="number"
                                min={0}
                                max={100000}
                                required
                                value={stock}
                                onChange={(event) =>
                                    setStock(event.target.value)
                                }
                                aria-invalid={Boolean(stockError)}
                                className="rounded-[8px] border-slate-200 bg-white"
                            />
                            <InputError message={stockError} />
                        </div>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={processing}
                                >
                                    Batal
                                </Button>
                            </DialogClose>
                            <Button type="submit" disabled={processing}>
                                {processing ? 'Menyimpan...' : 'Simpan stok'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

SellerInventoryIndex.layout = {
    breadcrumbs: [{ title: 'Inventori', href: inventoryIndex() }],
};
