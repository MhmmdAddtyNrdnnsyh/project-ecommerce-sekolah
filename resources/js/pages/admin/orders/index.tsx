import { Head, router, usePage } from '@inertiajs/react';
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
    code: string;
    status: { code: string; label: string };
    total_price: number;
    payment: {
        status: { code: string; label: string };
        method: { code: string; label: string };
        proof_url: string | null;
        confirmed_at: string | null;
        rejection_reason: string | null;
    };
    items_count: number;
    buyer: { id: number; name: string; email: string };
    items: {
        id: number;
        product_name: string;
        quantity: number;
        subtotal: number;
        status: { code: string; label: string };
        payment: {
            status: { code: string; label: string };
            method: { code: string; label: string };
        };
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
    const { flash } = usePage().props;
    const [q, setQ] = useState(filters.q);

    const submitFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get('/admin/orders', q ? { q } : {}, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Admin Orders" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section>
                        <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                            <ShoppingCart className="size-3.5" /> {orders.total}{' '}
                            order
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Orders
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pantau transaksi buyer dan item fulfillment seller.
                            Admin memantau status pembayaran dan fulfillment.
                        </p>
                    </section>

                    {(flash.success || flash.error) && (
                        <div
                            role="status"
                            className={`rounded-[8px] border px-4 py-3 text-sm ${
                                flash.error
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                            }`}
                        >
                            {flash.error || flash.success}
                        </div>
                    )}

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
                                            'Payment',
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
                                                colSpan={7}
                                                className="py-10 text-center text-slate-500"
                                            >
                                                Tidak ada order.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                    {orders.data.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell className="px-5 font-medium text-slate-950">
                                                {order.code}
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
                                                                {
                                                                    item.seller
                                                                        .name
                                                                }
                                                            </span>
                                                            <Badge
                                                                className={
                                                                    paymentStatusClass[
                                                                        item
                                                                            .payment
                                                                            .status
                                                                            .code
                                                                    ] ??
                                                                    'rounded-[6px] bg-slate-100 text-slate-700'
                                                                }
                                                            >
                                                                {
                                                                    item
                                                                        .payment
                                                                        .status
                                                                        .label
                                                                }
                                                            </Badge>
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
                                            <TableCell className="min-w-64 px-5">
                                                <div className="space-y-2">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        <Badge
                                                            className={
                                                                paymentStatusClass[
                                                                    order
                                                                        .payment
                                                                        .status
                                                                        .code
                                                                ] ??
                                                                'rounded-[6px] bg-slate-100 text-slate-700'
                                                            }
                                                        >
                                                            {
                                                                order.payment
                                                                    .status
                                                                    .label
                                                            }
                                                        </Badge>
                                                        <span className="text-xs font-medium text-slate-500">
                                                            {
                                                                order.payment
                                                                    .method
                                                                    .label
                                                            }
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-slate-500">
                                                        Pembayaran tunai
                                                        dikonfirmasi oleh seller
                                                        atau picket.
                                                    </p>
                                                </div>
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

const paymentStatusClass: Record<string, string> = {
    unpaid: 'rounded-[6px] bg-slate-100 text-slate-700',
    pending_confirmation: 'rounded-[6px] bg-amber-50 text-amber-700',
    paid: 'rounded-[6px] bg-emerald-50 text-emerald-700',
    rejected: 'rounded-[6px] bg-rose-50 text-rose-700',
};
