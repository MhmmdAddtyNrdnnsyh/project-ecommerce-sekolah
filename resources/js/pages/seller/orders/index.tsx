import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, Eye, Search, ShoppingCart } from 'lucide-react';
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
import {
    index as ordersIndex,
    show as ordersShow,
    updateStatus,
} from '@/routes/seller/orders';

type OrderStatus =
    'pending' | 'in_production' | 'ready' | 'packed' | 'sent' | 'completed';

type PaymentStatus = 'unpaid' | 'pending_confirmation' | 'paid' | 'rejected';

type SellerOrderItem = {
    id: number;
    source: 'online' | 'offline';
    code?: string;
    detail_url?: string;
    order_id: number | string;
    buyer: { id: number | null; name: string };
    product: { id: number; name: string; slug: string };
    managed_by_up_jurusan: boolean;
    is_pre_order: boolean;
    pre_order_estimate_days: number | null;
    pre_order_deadline: string | null;
    pre_order_min_quantity: number | null;
    pre_order_note: string | null;
    product_name: string;
    price: number;
    quantity: number;
    subtotal: number;
    status: { code: OrderStatus; label: string };
    payment: {
        status: { code: PaymentStatus; label: string };
        method: { code: string; label: string };
        confirmed_at: string | null;
        rejection_reason: string | null;
    };
    created_at: string;
};

type SellerOrdersProps = {
    orderItems: {
        data: SellerOrderItem[];
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { q: string; status: string };
};

const statusStyles: Record<OrderStatus, string> = {
    pending: 'bg-blue-50 text-blue-700',
    in_production: 'bg-violet-50 text-violet-700',
    ready: 'bg-cyan-50 text-cyan-700',
    packed: 'bg-amber-50 text-amber-700',
    sent: 'bg-indigo-50 text-indigo-700',
    completed: 'bg-emerald-50 text-emerald-700',
};

const paymentStatusStyles: Record<PaymentStatus, string> = {
    unpaid: 'bg-slate-100 text-slate-700',
    pending_confirmation: 'bg-amber-50 text-amber-700',
    paid: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-rose-50 text-rose-700',
};

const nextStatus: Record<
    Exclude<OrderStatus, 'sent' | 'completed'>,
    { code: OrderStatus; action: string }
> = {
    pending: { code: 'packed', action: 'Tandai dikemas' },
    in_production: { code: 'ready', action: 'Tandai siap' },
    ready: { code: 'sent', action: 'Tandai dikirim' },
    packed: { code: 'sent', action: 'Tandai dikirim' },
};

const nextActionFor = (item: SellerOrderItem) => {
    if (item.status.code === 'sent' || item.status.code === 'completed') {
        return null;
    }

    if (item.is_pre_order && item.status.code === 'pending') {
        return { code: 'in_production' as const, action: 'Mulai produksi' };
    }

    return nextStatus[item.status.code];
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatDate = (value: string) =>
    new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

export default function SellerOrdersIndex({
    orderItems,
    filters,
}: SellerOrdersProps) {
    const { flash } = usePage().props;
    const [q, setQ] = useState(filters.q);
    const [status, setStatus] = useState(filters.status || '');
    const [processingId, setProcessingId] = useState<number>();
    const [paymentProcessingId, setPaymentProcessingId] = useState<number>();
    const [statusError, setStatusError] = useState<string>();
    const [paymentError, setPaymentError] = useState<string>();

    const submitFilters = (event: React.FormEvent) => {
        event.preventDefault();
        router.get(
            ordersIndex(),
            Object.fromEntries(
                Object.entries({
                    q,
                    status: status === 'all' ? '' : status,
                }).filter(([, value]) => value),
            ),
            { preserveState: true, replace: true },
        );
    };

    const advanceStatus = (item: SellerOrderItem) => {
        if (
            item.source === 'offline' ||
            item.status.code === 'sent' ||
            item.status.code === 'completed'
        ) {
            return;
        }

        const action = nextActionFor(item);

        if (!action) {
            return;
        }

        setStatusError(undefined);

        router.put(
            updateStatus(item.id),
            { status: action.code },
            {
                preserveScroll: true,
                onStart: () => setProcessingId(item.id),
                onFinish: () => setProcessingId(undefined),
                onError: (errors) => setStatusError(errors.status),
            },
        );
    };

    const approvePayment = (item: SellerOrderItem) => {
        if (
            item.source !== 'online' ||
            item.managed_by_up_jurusan ||
            item.payment.status.code === 'paid'
        ) {
            return;
        }

        setPaymentError(undefined);

        router.post(
            `/seller/orders/${item.id}/payment/approve`,
            {},
            {
                preserveScroll: true,
                onStart: () => setPaymentProcessingId(item.id),
                onFinish: () => setPaymentProcessingId(undefined),
                onError: (errors) => setPaymentError(errors.payment),
            },
        );
    };

    return (
        <>
            <Head title="Pesanan Seller" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section>
                        <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                            <ShoppingCart className="size-3.5" />{' '}
                            {orderItems.total} item pesanan
                        </Badge>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Pesanan
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Proses pemenuhan produk yang dibeli dari toko Anda.
                        </p>
                    </section>

                    {(flash.success ||
                        flash.error ||
                        statusError ||
                        paymentError) && (
                        <div
                            role="status"
                            className={cn(
                                'rounded-[8px] border px-4 py-3 text-sm',
                                flash.error || statusError || paymentError
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            )}
                        >
                            {statusError ||
                                paymentError ||
                                flash.error ||
                                flash.success}
                        </div>
                    )}

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-5">
                            <CardTitle>Daftar Pesanan</CardTitle>
                            <CardDescription>
                                {orderItems.from ?? 0}-{orderItems.to ?? 0} dari{' '}
                                {orderItems.total} item
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <form
                                onSubmit={submitFilters}
                                className="grid gap-3 border-b border-slate-100 p-5 sm:grid-cols-[1fr_12rem_auto]"
                            >
                                <label className="relative">
                                    <span className="sr-only">
                                        Cari pesanan
                                    </span>
                                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                    <Input
                                        value={q}
                                        onChange={(event) =>
                                            setQ(event.target.value)
                                        }
                                        placeholder="Nomor order, pembeli, atau produk"
                                        className="rounded-[8px] border-slate-200 bg-white pl-9"
                                    />
                                </label>
                                <label>
                                    <span className="sr-only">
                                        Status pesanan
                                    </span>
                                    <Select
                                        value={status}
                                        onValueChange={setStatus}
                                    >
                                        <SelectTrigger className="w-full rounded-[8px] border-slate-200 bg-white">
                                            <SelectValue placeholder="Pilih status pesanan" />
                                        </SelectTrigger>
                                        <SelectContent className="rounded-[8px] bg-white text-slate-900 ring-slate-200">
                                            <SelectGroup>
                                                <SelectLabel>
                                                    Status pesanan
                                                </SelectLabel>
                                                <SelectItem value="all">
                                                    Semua status
                                                </SelectItem>
                                                <SelectItem value="pending">
                                                    Menunggu
                                                </SelectItem>
                                                <SelectItem value="in_production">
                                                    Diproduksi
                                                </SelectItem>
                                                <SelectItem value="ready">
                                                    Siap
                                                </SelectItem>
                                                <SelectItem value="packed">
                                                    Dikemas
                                                </SelectItem>
                                                <SelectItem value="sent">
                                                    Dikirim
                                                </SelectItem>
                                                <SelectItem value="completed">
                                                    Selesai
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
                                            router.get(ordersIndex())
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
                                                'Order',
                                                'Pembeli',
                                                'Produk',
                                                'Jumlah',
                                                'Subtotal',
                                                'Pembayaran',
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
                                        {orderItems.data.length === 0 && (
                                            <TableRow>
                                                <TableCell
                                                    colSpan={9}
                                                    className="py-10 text-center text-slate-500"
                                                >
                                                    Tidak ada pesanan yang
                                                    sesuai filter.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                        {orderItems.data.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="px-5 font-semibold">
                                                    {item.code ??
                                                        `#${item.order_id}`}
                                                    {item.source ===
                                                        'offline' && (
                                                        <Badge className="mt-1 block w-fit rounded-[6px] bg-emerald-50 text-emerald-700">
                                                            Offline/POS
                                                        </Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    {item.buyer.name}
                                                </TableCell>
                                                <TableCell className="min-w-52 px-5">
                                                    <div>
                                                        <p className="font-medium text-slate-950">
                                                            {item.product_name}
                                                        </p>
                                                        {item.is_pre_order && (
                                                            <p className="mt-1 text-xs text-blue-700">
                                                                PO{' '}
                                                                {
                                                                    item.pre_order_estimate_days
                                                                }{' '}
                                                                hari
                                                                {item.pre_order_deadline &&
                                                                    ` • Deadline ${item.pre_order_deadline}`}
                                                            </p>
                                                        )}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    {item.quantity}
                                                </TableCell>
                                                <TableCell className="px-5 font-medium">
                                                    {formatRupiah(
                                                        item.subtotal,
                                                    )}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <div className="space-y-1">
                                                        <Badge
                                                            className={cn(
                                                                'rounded-[6px]',
                                                                paymentStatusStyles[
                                                                    item.payment
                                                                        .status
                                                                        .code
                                                                ],
                                                            )}
                                                        >
                                                            {
                                                                item.payment
                                                                    .status
                                                                    .label
                                                            }
                                                        </Badge>
                                                        <p className="text-xs text-slate-500">
                                                            {
                                                                item.payment
                                                                    .method
                                                                    .label
                                                            }
                                                        </p>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <Badge
                                                        className={cn(
                                                            'rounded-full',
                                                            statusStyles[
                                                                item.status.code
                                                            ],
                                                        )}
                                                    >
                                                        {item.status.label}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="min-w-40 px-5 text-sm text-slate-500">
                                                    {formatDate(
                                                        item.created_at,
                                                    )}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            asChild
                                                            variant="outline"
                                                            size="sm"
                                                            className="rounded-[8px]"
                                                        >
                                                            {item.source ===
                                                            'online' ? (
                                                                <Link
                                                                    href={ordersShow(
                                                                        item.id,
                                                                    )}
                                                                >
                                                                    <Eye className="size-3.5" />{' '}
                                                                    Detail
                                                                </Link>
                                                            ) : (
                                                                <Link
                                                                    href={
                                                                        item.detail_url ??
                                                                        '#'
                                                                    }
                                                                >
                                                                    <Eye className="size-3.5" />{' '}
                                                                    Detail
                                                                </Link>
                                                            )}
                                                        </Button>
                                                        {item.source ===
                                                            'online' &&
                                                            !item.managed_by_up_jurusan &&
                                                            item.payment.status
                                                                .code !==
                                                                'paid' && (
                                                                <Button
                                                                    type="button"
                                                                    size="sm"
                                                                    variant="outline"
                                                                    className="rounded-[8px] border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                                                                    disabled={
                                                                        paymentProcessingId ===
                                                                        item.id
                                                                    }
                                                                    onClick={() =>
                                                                        approvePayment(
                                                                            item,
                                                                        )
                                                                    }
                                                                >
                                                                    <CheckCircle2 className="size-3.5" />{' '}
                                                                    {paymentProcessingId ===
                                                                    item.id
                                                                        ? 'Memproses...'
                                                                        : 'Tandai lunas'}
                                                                </Button>
                                                            )}
                                                        {item.managed_by_up_jurusan ? (
                                                            <Badge className="rounded-[6px] bg-slate-100 text-slate-700">
                                                                Dikelola UP
                                                                Jurusan
                                                            </Badge>
                                                        ) : nextActionFor(
                                                              item,
                                                          ) ? (
                                                            <Button
                                                                type="button"
                                                                size="sm"
                                                                className="rounded-[8px]"
                                                                disabled={
                                                                    processingId ===
                                                                    item.id
                                                                }
                                                                onClick={() =>
                                                                    advanceStatus(
                                                                        item,
                                                                    )
                                                                }
                                                            >
                                                                {processingId ===
                                                                item.id
                                                                    ? 'Memproses...'
                                                                    : nextActionFor(
                                                                          item,
                                                                      )?.action}
                                                            </Button>
                                                        ) : null}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {orderItems.last_page > 1 && (
                                <div className="flex items-center justify-between border-t border-slate-100 p-4">
                                    <span className="text-sm text-slate-500">
                                        Halaman {orderItems.current_page} dari{' '}
                                        {orderItems.last_page}
                                    </span>
                                    <div className="flex gap-2">
                                        <Button
                                            asChild={Boolean(
                                                orderItems.prev_page_url,
                                            )}
                                            disabled={!orderItems.prev_page_url}
                                            variant="outline"
                                            size="sm"
                                        >
                                            {orderItems.prev_page_url ? (
                                                <Link
                                                    href={
                                                        orderItems.prev_page_url
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
                                                orderItems.next_page_url,
                                            )}
                                            disabled={!orderItems.next_page_url}
                                            variant="outline"
                                            size="sm"
                                        >
                                            {orderItems.next_page_url ? (
                                                <Link
                                                    href={
                                                        orderItems.next_page_url
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
        </>
    );
}

SellerOrdersIndex.layout = {
    breadcrumbs: [{ title: 'Pesanan', href: ordersIndex() }],
};
