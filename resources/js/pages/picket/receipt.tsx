import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer, ReceiptText } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type ReceiptItem = {
    id: number;
    product_name: string;
    source: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
};

type Props = {
    sale: {
        id: number;
        code: string;
        sold_at: string | null;
        total_quantity: number;
        total_amount: number;
        up_jurusan: { id: number; name: string };
        picket: { id: number; name: string };
        items: ReceiptItem[];
    };
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatDateTime = (value: string | null) =>
    value
        ? new Intl.DateTimeFormat('id-ID', {
              dateStyle: 'full',
              timeStyle: 'short',
          }).format(new Date(value))
        : '-';

export default function PicketReceipt({ sale }: Props) {
    return (
        <>
            <Head title={`Nota ${sale.code}`} />
            <main className="min-h-dvh bg-slate-50 p-4 text-slate-950 sm:p-6 print:min-h-0 print:bg-white print:p-0">
                <div className="mx-auto max-w-3xl space-y-4">
                    <section className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center print:hidden">
                        <div>
                            <Badge className="mb-2 rounded-[6px] bg-blue-50 text-blue-700">
                                <ReceiptText className="size-3.5" />
                                Nota POS
                            </Badge>
                            <h1 className="text-2xl font-semibold">
                                {sale.code}
                            </h1>
                        </div>
                        <div className="flex gap-2">
                            <Button asChild variant="outline">
                                <Link href="/picket/pos">
                                    <ArrowLeft className="size-4" />
                                    POS
                                </Link>
                            </Button>
                            <Button
                                type="button"
                                onClick={() => window.print()}
                            >
                                <Printer className="size-4" />
                                Print
                            </Button>
                        </div>
                    </section>

                    <section className="rounded-[8px] border border-slate-100 bg-white p-6 shadow-sm print:rounded-none print:border-0 print:p-0 print:shadow-none">
                        <header className="border-b border-slate-200 pb-5">
                            <div className="flex flex-col justify-between gap-4 sm:flex-row">
                                <div>
                                    <p className="text-sm font-medium text-slate-500">
                                        EduCart POS
                                    </p>
                                    <h2 className="mt-1 text-xl font-semibold">
                                        {sale.up_jurusan.name}
                                    </h2>
                                </div>
                                <div className="text-left sm:text-right">
                                    <p className="font-semibold">{sale.code}</p>
                                    <p className="mt-1 text-sm text-slate-500">
                                        {formatDateTime(sale.sold_at)}
                                    </p>
                                </div>
                            </div>
                            <div className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <span className="text-slate-500">
                                        Picket
                                    </span>
                                    <p className="font-medium">
                                        {sale.picket.name}
                                    </p>
                                </div>
                                <div className="sm:text-right">
                                    <span className="text-slate-500">
                                        Metode bayar
                                    </span>
                                    <p className="font-medium">Tunai</p>
                                </div>
                                <div className="sm:text-right">
                                    <span className="text-slate-500">
                                        Total item
                                    </span>
                                    <p className="font-medium">
                                        {sale.total_quantity} item
                                    </p>
                                </div>
                            </div>
                        </header>

                        <div className="mt-5">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Produk</TableHead>
                                        <TableHead className="text-right">
                                            Qty
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Harga
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Subtotal
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sale.items.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell className="whitespace-normal">
                                                <p className="font-medium text-slate-950">
                                                    {item.product_name}
                                                </p>
                                                <p className="text-xs text-slate-500">
                                                    {item.source}
                                                </p>
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {item.quantity}
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {formatRupiah(item.unit_price)}
                                            </TableCell>
                                            <TableCell className="text-right font-medium tabular-nums">
                                                {formatRupiah(item.subtotal)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <footer className="mt-5 border-t border-slate-200 pt-5">
                            <div className="flex justify-between text-lg font-semibold">
                                <span>Total</span>
                                <span className="tabular-nums">
                                    {formatRupiah(sale.total_amount)}
                                </span>
                            </div>
                            <p className="mt-5 text-center text-xs text-slate-500">
                                Simpan nota ini sebagai bukti transaksi POS UP
                                Jurusan.
                            </p>
                        </footer>
                    </section>
                </div>
            </main>
        </>
    );
}
