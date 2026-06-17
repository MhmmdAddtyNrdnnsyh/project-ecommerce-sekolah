import { Head, Link } from '@inertiajs/react';
import { Boxes, Package, Pencil, Plus, Tags } from 'lucide-react';
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
    edit as sellerProductsEdit,
    index as sellerProductsIndex,
} from '@/routes/seller/products';

type ProductStatus = 'draft' | 'pending' | 'approved' | 'rejected';

type SellerProduct = {
    id: number;
    name: string;
    slug: string;
    category: {
        id: number;
        name: string;
        slug: string;
    };
    price: number;
    stock: number;
    status: {
        code: ProductStatus;
        label: string;
    };
};

type SellerProductsIndexProps = {
    products: SellerProduct[];
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
}: SellerProductsIndexProps) {
    return (
        <>
            <Head title="Produk Seller" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <Badge className="rounded-[6px] bg-blue-50 text-blue-700">
                                    <Package className="size-3.5" />
                                    Seller Center
                                </Badge>
                                <Badge className="rounded-[6px] bg-emerald-50 text-emerald-700">
                                    {products.length} produk
                                </Badge>
                            </div>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Produk Toko
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm text-slate-500">
                                Daftar produk yang terhubung dengan akun seller
                                ini.
                            </p>
                        </div>
                        <Button
                            asChild
                            className="h-9 rounded-[8px] bg-blue-600 px-3 text-white hover:bg-blue-700"
                        >
                            <Link href={sellerProductsCreate()}>
                                <Plus className="size-4" />
                                Tambah Produk
                            </Link>
                        </Button>
                    </section>

                    <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                        <CardHeader className="flex-row items-center border-b border-slate-100 p-6">
                            <div className="space-y-1">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Katalog Produk
                                </CardTitle>
                                <CardDescription>
                                    Nama, kategori, harga, stok, dan status
                                    moderasi.
                                </CardDescription>
                            </div>
                            <CardAction>
                                <div className="flex size-10 items-center justify-center rounded-[8px] bg-slate-100 text-slate-600">
                                    <Boxes className="size-5" />
                                </div>
                            </CardAction>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow className="border-slate-100 bg-slate-50 hover:bg-slate-50">
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
                                                className="h-11 px-6 text-xs font-semibold tracking-wide text-slate-500 uppercase"
                                            >
                                                {heading}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {products.length === 0 && (
                                        <TableRow className="border-slate-100">
                                            <TableCell
                                                colSpan={6}
                                                className="px-6 py-10 text-center text-sm text-slate-500"
                                            >
                                                Belum ada produk.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {products.map((product) => (
                                        <TableRow
                                            key={product.id}
                                            className="border-slate-100 hover:bg-slate-50/70"
                                        >
                                            <TableCell className="max-w-[18rem] px-6 py-4">
                                                <div className="flex min-w-0 items-center gap-3">
                                                    <div className="flex size-10 shrink-0 items-center justify-center rounded-[8px] bg-blue-50 text-blue-700">
                                                        <Package className="size-4" />
                                                    </div>
                                                    <div className="min-w-0">
                                                        <p className="truncate font-semibold text-slate-950">
                                                            {product.name}
                                                        </p>
                                                        <p className="truncate text-xs text-slate-500">
                                                            {product.slug}
                                                        </p>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="px-6 py-4 text-slate-600">
                                                <span className="inline-flex min-w-0 items-center gap-2">
                                                    <Tags className="size-4 shrink-0 text-slate-400" />
                                                    <span className="truncate">
                                                        {product.category.name}
                                                    </span>
                                                </span>
                                            </TableCell>
                                            <TableCell className="px-6 py-4 font-semibold text-slate-950">
                                                {formatRupiah(product.price)}
                                            </TableCell>
                                            <TableCell className="px-6 py-4 text-slate-600">
                                                {product.stock}
                                            </TableCell>
                                            <TableCell className="px-6 py-4">
                                                <Badge
                                                    variant="secondary"
                                                    className={cn(
                                                        'rounded-full px-2.5 py-0.5',
                                                        statusStyles[
                                                            product.status.code
                                                        ],
                                                    )}
                                                >
                                                    {product.status.label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="px-6 py-4 text-right">
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-8 rounded-[8px] border-slate-200 bg-white"
                                                >
                                                    <Link
                                                        href={sellerProductsEdit(
                                                            product.id,
                                                        )}
                                                    >
                                                        <Pencil className="size-3.5" />
                                                        Edit
                                                    </Link>
                                                </Button>
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

SellerProductsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Produk Seller',
            href: sellerProductsIndex(),
        },
    ],
};
