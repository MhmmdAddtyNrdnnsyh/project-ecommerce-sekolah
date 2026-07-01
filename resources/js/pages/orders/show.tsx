import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CheckCircle2,
    ExternalLink,
    ShoppingCart,
    Store,
} from 'lucide-react';
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
import { index as ordersIndex } from '@/routes/orders';

type BuyerOrder = {
    id: number;
    code: string;
    status: { code: string; label: string };
    can_complete: boolean;
    payment: {
        status: { code: string; label: string };
        method: { code: string; label: string };
        proof_url: string | null;
        confirmed_at: string | null;
        rejection_reason: string | null;
    };
    total_price: number;
    items: {
        id: number;
        product_name: string;
        price: number;
        quantity: number;
        subtotal: number;
        is_pre_order: boolean;
        pre_order_estimate_days: number | null;
        pre_order_deadline: string | null;
        pre_order_min_quantity: number | null;
        pre_order_note: string | null;
        status: { code: string; label: string };
        seller: { id: number; name: string };
    }[];
    created_at: string | null;
};

type Props = {
    order: BuyerOrder;
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

export default function BuyerOrdersShow({ order }: Props) {
    const { flash } = usePage().props;

    return (
        <>
            <Head title={`Order ${order.code}`} />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                                <ShoppingCart className="size-3.5" />
                                {order.code}
                            </Badge>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Detail Order
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Dibuat pada {formatDate(order.created_at)}.
                            </p>
                        </div>
                        <Button
                            asChild
                            variant="outline"
                            className="h-9 w-fit rounded-[8px] border-slate-200 bg-white"
                        >
                            <Link href={ordersIndex()}>
                                <ArrowLeft className="size-4" />
                                Orders
                            </Link>
                        </Button>
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

                    <Card className="rounded-[8px] border border-slate-100 bg-white shadow-sm">
                        <CardHeader>
                            <CardTitle>Ringkasan</CardTitle>
                            <CardDescription>
                                Status order dan total transaksi.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-4">
                            <div>
                                <p className="text-sm text-slate-500">Status</p>
                                <Badge className="mt-2 rounded-[6px] bg-blue-50 text-blue-700">
                                    {order.status.label}
                                </Badge>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">
                                    Total item
                                </p>
                                <p className="mt-1 text-lg font-semibold text-slate-950">
                                    {order.items.length}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">
                                    Pembayaran
                                </p>
                                <Badge
                                    className={
                                        paymentStatusClass[
                                            order.payment.status.code
                                        ] ??
                                        'mt-2 rounded-[6px] bg-slate-100 text-slate-700'
                                    }
                                >
                                    {order.payment.status.label}
                                </Badge>
                                <p className="mt-2 text-xs text-slate-500">
                                    {order.payment.method.label}
                                </p>
                                {order.payment.proof_url && (
                                    <a
                                        href={order.payment.proof_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="mt-2 inline-flex items-center gap-1 text-xs font-medium text-blue-700 hover:text-blue-800"
                                    >
                                        <ExternalLink className="size-3.5" />
                                        Bukti bayar
                                    </a>
                                )}
                                {order.payment.rejection_reason && (
                                    <p className="mt-2 text-xs text-rose-600">
                                        {order.payment.rejection_reason}
                                    </p>
                                )}
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">
                                    Total harga
                                </p>
                                <p className="mt-1 text-lg font-semibold text-slate-950">
                                    {formatRupiah(order.total_price)}
                                </p>
                            </div>
                        </CardContent>
                        {order.can_complete && (
                            <div className="border-t border-slate-100 px-6 pb-6">
                                <Form
                                    action={`/orders/${order.id}/complete`}
                                    method="post"
                                    className="mt-5"
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="w-fit bg-emerald-600 hover:bg-emerald-700"
                                        >
                                            <CheckCircle2 className="size-4" />
                                            {processing
                                                ? 'Memproses...'
                                                : 'Pesanan diterima'}
                                        </Button>
                                    )}
                                </Form>
                                <p className="mt-2 text-xs text-slate-500">
                                    Klik setelah barang sudah diterima agar
                                    status pesanan menjadi selesai.
                                </p>
                            </div>
                        )}
                    </Card>

                    <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                        <CardHeader className="border-b border-slate-100 p-6">
                            <CardTitle>Item Order</CardTitle>
                            <CardDescription>
                                Produk yang dibeli dalam order ini.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="space-y-3 p-4 md:hidden">
                                {order.items.map((item) => (
                                    <div
                                        key={item.id}
                                        className="rounded-[8px] border border-slate-200 bg-white p-4"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="line-clamp-2 font-semibold text-slate-950">
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
                                                <p className="mt-1 inline-flex items-center gap-1 text-sm text-slate-500">
                                                    <Store className="size-3.5" />
                                                    {item.seller.name}
                                                </p>
                                            </div>
                                            <Badge className="shrink-0 rounded-full bg-emerald-50 text-emerald-700">
                                                {item.status.label}
                                            </Badge>
                                        </div>
                                        <div className="mt-4 grid grid-cols-3 gap-3 border-t border-slate-100 pt-3 text-sm">
                                            <div>
                                                <p className="text-xs text-slate-500">
                                                    Harga
                                                </p>
                                                <p className="mt-1 font-medium text-slate-950">
                                                    {formatRupiah(item.price)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-slate-500">
                                                    Qty
                                                </p>
                                                <p className="mt-1 font-medium text-slate-950">
                                                    {item.quantity}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-slate-500">
                                                    Subtotal
                                                </p>
                                                <p className="mt-1 font-semibold text-slate-950">
                                                    {formatRupiah(
                                                        item.subtotal,
                                                    )}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="hidden overflow-x-auto md:block">
                                <Table className="min-w-[760px]">
                                    <TableHeader>
                                        <TableRow className="bg-slate-50">
                                            {[
                                                'Produk',
                                                'Seller',
                                                'Harga',
                                                'Qty',
                                                'Subtotal',
                                                'Status',
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
                                        {order.items.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell className="px-5 font-medium text-slate-950">
                                                    <div>
                                                        <p>
                                                            {item.product_name}
                                                        </p>
                                                        {item.is_pre_order && (
                                                            <p className="mt-1 text-xs font-normal text-blue-700">
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
                                                    <span className="inline-flex items-center gap-1 text-sm text-slate-600">
                                                        <Store className="size-3.5" />
                                                        {item.seller.name}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    {formatRupiah(item.price)}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    {item.quantity}
                                                </TableCell>
                                                <TableCell className="px-5 font-semibold text-slate-950">
                                                    {formatRupiah(
                                                        item.subtotal,
                                                    )}
                                                </TableCell>
                                                <TableCell className="px-5">
                                                    <Badge className="rounded-[6px] bg-emerald-50 text-emerald-700">
                                                        {item.status.label}
                                                    </Badge>
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

BuyerOrdersShow.layout = {
    breadcrumbs: [{ title: 'Orders', href: ordersIndex() }],
};

const paymentStatusClass: Record<string, string> = {
    unpaid: 'mt-2 rounded-[6px] bg-slate-100 text-slate-700',
    pending_confirmation: 'mt-2 rounded-[6px] bg-amber-50 text-amber-700',
    paid: 'mt-2 rounded-[6px] bg-emerald-50 text-emerald-700',
    rejected: 'mt-2 rounded-[6px] bg-rose-50 text-rose-700',
};
