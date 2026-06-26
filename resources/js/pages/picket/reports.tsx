import { Form, Head } from '@inertiajs/react';
import { FileText, ReceiptText } from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type DailyReportItem = {
    product_name: string;
    quantity: number;
    subtotal: number;
};

type Props = {
    errors?: {
        report?: string;
    };
    daily_report: {
        date: string;
        total_sold: number;
        total_revenue: number;
        items: DailyReportItem[];
    };
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function PicketReports({ errors, daily_report }: Props) {
    return (
        <>
            <Head title="Reports Picket" />
            <main className="min-h-dvh space-y-4 bg-slate-100 p-3 text-slate-950 sm:p-5">
                <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
                    <Badge className="mb-3 rounded-[6px] bg-blue-50 text-blue-700">
                        <FileText className="size-3.5" />
                        {daily_report.date}
                    </Badge>
                    <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <h1 className="text-2xl font-semibold">
                                Laporan Penjualan
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                Rekap penjualan picket hari ini.
                            </p>
                        </div>
                        <Form action="/picket/up-jurusan/report" method="post">
                            {({ processing }) => (
                                <>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="h-10 rounded-[8px] bg-blue-600 text-white hover:bg-blue-700"
                                    >
                                        <ReceiptText className="size-4" />
                                        Buat laporan
                                    </Button>
                                    <InputError message={errors?.report} />
                                </>
                            )}
                        </Form>
                    </div>
                </section>

                <section className="grid gap-3 md:grid-cols-2">
                    <Summary
                        label="Total item terjual"
                        value={daily_report.total_sold}
                    />
                    <Summary
                        label="Total omzet"
                        value={formatRupiah(daily_report.total_revenue)}
                    />
                </section>

                <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="font-semibold">Detail laporan</h2>
                    <div className="mt-4 divide-y divide-slate-100">
                        {daily_report.items.length === 0 ? (
                            <p className="py-8 text-center text-sm text-slate-500">
                                Belum ada data untuk dilaporkan.
                            </p>
                        ) : (
                            daily_report.items.map((item) => (
                                <div
                                    key={item.product_name}
                                    className="grid gap-2 py-4 text-sm sm:grid-cols-[1fr_auto_auto] sm:items-center"
                                >
                                    <span className="font-semibold">
                                        {item.product_name}
                                    </span>
                                    <span className="text-slate-500">
                                        {item.quantity} item
                                    </span>
                                    <span className="font-semibold">
                                        {formatRupiah(item.subtotal)}
                                    </span>
                                </div>
                            ))
                        )}
                    </div>
                </section>
            </main>
        </>
    );
}

function Summary({ label, value }: { label: string; value: string | number }) {
    return (
        <div className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 text-xl font-semibold">{value}</p>
        </div>
    );
}
