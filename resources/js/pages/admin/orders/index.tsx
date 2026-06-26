import { Head, router } from '@inertiajs/react';
import { Search, ShoppingCart } from 'lucide-react';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type AdminOrder = {
    id: number;
    status: { code: string; label: string };
    total_price: number;
    items_count: number;
    buyer: { id: number; name: string; email: string };
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
        data: AdminOrder[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filters: { q: string };
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

export default function AdminOrdersIndex({ orders, filters }: Props) {
    const [q, setQ] = useState(filters.q);

    const submitFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            '/admin/orders',
            q ? { q } : {},
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Admin Orders" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section>
                        <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                            <ShoppingCart className="size-3.5" />{' '}
                            {orders.total} order
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Orders
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pantau transaksi buyer dan item fulfillment seller.
                        </p>
                    </section>

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle>Daftar Order</CardTitle>
                            <CardDescription>
                                {orders.from ?? 0}-{orders.to ?? 0} dari{' '}
                                {orders.total} order
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <form
                                onSubmit={submitFilters}
                                className="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-[1fr_auto]"
                            >
                                <label className="relative">
                                    <span className="sr-only">Cari order</span>
                                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        value={q}
                                        onChange={(event) =>
                                            setQ(event.target.value)
                                        }
                                        placeholder="Nomor, buyer, email, atau produk"
                                        className="rounded-[8px] border-slate-200 bg-white pl-9"
                                    />
                                </label>
                                <Button type="submit" className="rounded-[8px]">
                                    Terapkan
                                </Button>
                            </form>

                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-slate-50">
                                        {[
                                            'Order',
                                            'Buyer',
                                            'Item',
                                            'Total',
                                            'Status',
                                            'Waktu',
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
                                                className="py-10 text-center text-slate-500"
                                            >
                                                Tidak ada order.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {orders.data.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell className="px-5 font-medium text-slate-950">
                                                #{order.id}
                                            </TableCell>
                                            <TableCell className="px-5">
                                                <div>{order.buyer.name}</div>
                                                <div className="text-xs text-slate-500">
                                                    {order.buyer.email}
                                                </div>
                                            </TableCell>
                                            <TableCell className="px-5">
                                                <div className="space-y-1">
                                                    {order.items.map((item) => (
                                                        <div
                                                            key={item.id}
                                                            className="text-sm"
                                                        >
                                                            {item.product_name}{' '}
                                                            x{item.quantity}
                                                            <span className="ml-2 text-xs text-slate-500">
                                                                {item.seller.name}
                                                            </span>
                                                        </div>
                                                    ))}
                                                    {order.items_count >
                                                        order.items.length && (
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
                                                {formatDate(order.created_at)}
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

AdminOrdersIndex.layout = {
    breadcrumbs: [{ title: 'Orders', href: '/admin/orders' }],
};
