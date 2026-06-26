import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Eye, Package, ShoppingCart } from 'lucide-react';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { home } from '@/routes';
import { index as ordersIndex, show as orderShow } from '@/routes/orders';

type BuyerOrder = {
    id: number;
    status: { code: string; label: string };
    total_price: number;
    items_count: number;
    items: {
        id: number;
        product_name: string;
        quantity: number;
        subtotal: number;
        status: { code: string; label: string };
        seller: { id: number; name: string };
    }[];
    created_at: string | null;
};

type Props = {
    orders: {
        data: BuyerOrder[];
        from: number | null;
        to: number | null;
        total: number;
    };
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatDate = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('id-ID', {
              dateStyle: 'medium',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';

export default function BuyerOrdersIndex({ orders }: Props) {
    return (
        <>
            <Head title="Orders Saya" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                                <ShoppingCart className="size-3.5" />
                                {orders.total} order
                            </Badge>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Orders Saya
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Riwayat pesanan dan status item yang dibeli.
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

                    <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-6">
                            <CardTitle>Daftar Order</CardTitle>
                            <CardDescription>
                                {orders.from ?? 0}-{orders.to ?? 0} dari{' '}
                                {orders.total} order
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="space-y-3 p-4 md:hidden">
                                {orders.data.length === 0 && (
                                    <div className="rounded-[8px] border border-dashed border-slate-300 bg-slate-50 px-4 py-10 text-center">
                                        <Package className="mx-auto size-8 text-slate-400" />
                                        <p className="mt-3 text-sm font-medium text-slate-700">
                                            Belum ada order.
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

                                {orders.data.map((order) => (
                                    <Link
                                        key={order.id}
                                        href={orderShow(order.id)}
                                        className="block rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-semibold text-slate-950">
                                                    Order #{order.id}
                                                </p>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    {formatDate(
                                                        order.created_at,
                                                    )}
                                                </p>
                                            </div>
                                            <Badge className="rounded-full bg-blue-50 text-blue-700">
                                                {order.status.label}
                                            </Badge>
                                        </div>
                                        <div className="mt-4 space-y-1">
                                            {order.items.map((item) => (
                                                <p
                                                    key={item.id}
                                                    className="line-clamp-1 text-sm text-slate-600"
                                                >
                                                    {item.product_name} x
                                                    {item.quantity}
                                                </p>
                                            ))}
                                            {order.items_count >
                                                order.items.length && (
                                                <p className="text-xs text-slate-500">
                                                    +
                                                    {order.items_count -
                                                        order.items.length}{' '}
                                                    item lain
                                                </p>
                                            )}
                                        </div>
                                        <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                                            <span className="text-sm text-slate-500">
                                                Total
                                            </span>
                                            <span className="font-semibold text-slate-950">
                                                {formatRupiah(
                                                    order.total_price,
                                                )}
                                            </span>
                                        </div>
                                    </Link>
                                ))}
                            </div>

                            <div className="hidden overflow-x-auto md:block">
                                <Table className="min-w-[760px]">
                                    <TableHeader>
                                        <TableRow className="bg-slate-50">
                                            {[
                                                'Order',
                                                'Item',
                                                'Total',
                                                'Status',
                                                'Waktu',
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
                                        {orders.data.length === 0 && (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={6}
                                                    className="py-12 text-center"
                                                >
                                                    <Package className="mx-auto size-8 text-slate-400" />
                                                    <p className="mt-3 text-sm font-medium text-slate-700">
                                                        Belum ada order.
                                                    </p>
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        className="mt-4 rounded-[8px]"
                                                    >
                                                        <Link href={home()}>
                                                            Lihat produk
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        )}
                                        {orders.data.map((order) => (
                                            <TableRow key={order.id}>
                                                <TableCell className="px-5 font-medium text-slate-950">
                                                    #{order.id}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <div className="space-y-1">
                                                        {order.items.map(
                                                            (item) => (
                                                                <div
                                                                    key={
                                                                        item.id
                                                                    }
                                                                    className="text-sm"
                                                                >
                                                                    {
                                                                        item.product_name
                                                                    }{' '}
                                                                    x
                                                                    {
                                                                        item.quantity
                                                                    }
                                                                </div>
                                                            ),
                                                        )}
                                                        {order.items_count >
                                                            order.items
                                                                .length && (
                                                            <div className="text-xs text-slate-500">
                                                                +
                                                                {order.items_count -
                                                                    order.items
                                                                        .length}{' '}
                                                                item lain
                                                            </div>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    {formatRupiah(
                                                        order.total_price,
                                                    )}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <Badge className="rounded-[6px] bg-blue-50 text-blue-700">
                                                        {order.status.label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    {formatDate(
                                                        order.created_at,
                                                    )}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="icon"
                                                        className="size-9 rounded-[8px] border-slate-200 bg-white"
                                                    >
                                                        <Link
                                                            href={orderShow(
                                                                order.id,
                                                            )}
                                                            aria-label={`Lihat order ${order.id}`}
                                                        >
                                                            <Eye className="size-4" />
                                                        </Link>
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

BuyerOrdersIndex.layout = {
    breadcrumbs: [{ title: 'Orders', href: ordersIndex() }],
};
