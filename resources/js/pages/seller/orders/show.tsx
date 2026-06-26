import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, PackageCheck, ShoppingCart } from 'lucide-react';
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
import { cn } from '@/lib/utils';
import { index as ordersIndex, updateStatus } from '@/routes/seller/orders';

type OrderStatus = 'pending' | 'packed' | 'sent';

type OrderDetailProps = {
    orderItem: {
        id: number;
        order_id: number;
        buyer: { id: number; name: string };
        product: {
            id: number;
            name: string;
            slug: string;
            category: { id: number; name: string; slug: string };
        };
        managed_by_up_jurusan: boolean;
        product_name: string;
        price: number;
        quantity: number;
        subtotal: number;
        status: { code: OrderStatus; label: string };
        created_at: string;
    };
};

const statusStyles: Record<OrderStatus, string> = {
    pending: 'bg-blue-50 text-blue-700',
    packed: 'bg-amber-50 text-amber-700',
    sent: 'bg-emerald-50 text-emerald-700',
};

const nextStatus = {
    pending: { code: 'packed', action: 'Tandai sudah dikemas' },
    packed: { code: 'sent', action: 'Tandai sudah dikirim' },
} as const;

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function SellerOrdersShow({ orderItem }: OrderDetailProps) {
    const { flash } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [statusError, setStatusError] = useState<string>();

    const advanceStatus = () => {
        if (orderItem.status.code === 'sent') {
            return;
        }

        setStatusError(undefined);

        router.put(
            updateStatus(orderItem.id),
            { status: nextStatus[orderItem.status.code].code },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setStatusError(errors.status),
            },
        );
    };

    const details = [
        ['Nomor order', `#${orderItem.order_id}`],
        ['Pembeli', orderItem.buyer.name],
        ['Produk', orderItem.product_name],
        ['Kategori', orderItem.product.category.name],
        ['Harga satuan', formatRupiah(orderItem.price)],
        ['Jumlah', String(orderItem.quantity)],
        ['Subtotal', formatRupiah(orderItem.subtotal)],
        [
            'Waktu',
            new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'full',
                timeStyle: 'short',
            }).format(new Date(orderItem.created_at)),
        ],
    ];

    return (
        <>
            <Head title={`Pesanan #${orderItem.order_id}`} />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="mx-auto max-w-3xl space-y-6">
                    <section className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                                <ShoppingCart className="size-3.5" /> Detail
                                fulfillment
                            </Badge>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Pesanan #{orderItem.order_id}
                            </h1>
                        </div>
                        <Button
                            asChild
                            variant="outline"
                            className="rounded-[8px]"
                        >
                            <Link href={ordersIndex()}>
                                <ArrowLeft className="size-4" /> Kembali
                            </Link>
                        </Button>
                    </section>

                    {(flash.success || flash.error || statusError) && (
                        <div
                            role="status"
                            className={cn(
                                'rounded-[8px] border px-4 py-3 text-sm',
                                flash.error || statusError
                                    ? 'border-rose-200 bg-rose-50 text-rose-700'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700',
                            )}
                        >
                            {statusError || flash.error || flash.success}
                        </div>
                    )}

                    <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
                        <CardHeader className="flex-row items-center justify-between border-b border-slate-100 p-5">
                            <div>
                                <CardTitle>{orderItem.product_name}</CardTitle>
                                <CardDescription>
                                    {orderItem.product.slug}
                                </CardDescription>
                            </div>
                            <Badge
                                className={cn(
                                    'rounded-full',
                                    statusStyles[orderItem.status.code],
                                )}
                            >
                                {orderItem.status.label}
                            </Badge>
                        </CardHeader>
                        <CardContent className="p-5">
                            <dl className="grid gap-x-8 gap-y-5 sm:grid-cols-2">
                                {details.map(([label, value]) => (
                                    <div key={label}>
                                        <dt className="text-sm text-slate-500">
                                            {label}
                                        </dt>
                                        <dd className="mt-1 font-medium text-slate-950">
                                            {value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>

                            <div className="mt-6 border-t border-slate-100 pt-5">
                                {orderItem.managed_by_up_jurusan ? (
                                    <p className="text-sm font-medium text-slate-600">
                                        Status pengiriman dikelola oleh picket
                                        officer UP Jurusan. Seller hanya
                                        menerima notifikasi pesanan masuk.
                                    </p>
                                ) : orderItem.status.code === 'sent' ? (
                                    <p className="flex items-center gap-2 text-sm font-medium text-emerald-700">
                                        <PackageCheck className="size-4" />{' '}
                                        Fulfillment selesai.
                                    </p>
                                ) : (
                                    <Button
                                        type="button"
                                        disabled={processing}
                                        onClick={advanceStatus}
                                        className="rounded-[8px]"
                                    >
                                        {processing
                                            ? 'Memproses...'
                                            : nextStatus[orderItem.status.code]
                                                  .action}
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

SellerOrdersShow.layout = {
    breadcrumbs: [{ title: 'Pesanan', href: ordersIndex() }],
};
