import { Head, Link, router } from '@inertiajs/react';
import { CalendarDays, PackageCheck, ReceiptText, Wallet } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type DailyReport = {
    id: number;
    picket_name: string;
    up_jurusan_name: string;
    total_sold: number;
    total_revenue: number;
    submitted_at: string | null;
};

type Props = {
    filters: { date: string };
    summary: {
        reports: number;
        pickets: number;
        items_sold: number;
        gross_amount: number;
    };
    reports: DailyReport[];
};

type SummaryProps = {
    label: string;
    value: string | number;
    icon: LucideIcon;
};

type EmptyStateProps = {
    title: string;
    description: string;
};

type ReportHeaderProps = {
    date: string;
    setDate: (date: string) => void;
    submit: (event: React.FormEvent) => void;
};

type ReportSummaryProps = {
    summary: Props['summary'];
};

type ReportsSectionProps = {
    date: string;
    reports: DailyReport[];
};

type DateTimeProps = {
    value: string | null;
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function AdminJurusanReports({
    filters,
    summary,
    reports,
}: Props) {
    const [date, setDate] = useState(filters.date);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        router.get('/admin-jurusan/reports', { date }, { preserveState: true });
    };

    return (
        <>
            <Head title="Laporan UP Jurusan" />
            <main className="min-h-dvh space-y-6 bg-slate-50 p-4 sm:p-6">
                <ReportHeader date={date} setDate={setDate} submit={submit} />
                <ReportSummary summary={summary} />
                <ReportsSection date={filters.date} reports={reports} />
            </main>
        </>
    );
}

function ReportHeader({ date, setDate, submit }: ReportHeaderProps) {
    return (
        <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <p className="text-sm font-medium text-blue-700">
                        Rekap Transaksi
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold text-slate-950">
                        Laporan UP Jurusan
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                        Cek laporan harian yang sudah dikirim picket officer.
                    </p>
                </div>
                <form
                    onSubmit={submit}
                    className="flex flex-col gap-2 sm:flex-row"
                >
                    <Input
                        type="date"
                        value={date}
                        onChange={(event) => setDate(event.target.value)}
                        className="w-full sm:w-40"
                    />
                    <Button type="submit">Filter</Button>
                </form>
            </div>
        </section>
    );
}

function ReportSummary({ summary }: ReportSummaryProps) {
    return (
        <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Summary
                label="Laporan masuk"
                value={summary.reports}
                icon={ReceiptText}
            />
            <Summary
                label="Picket melapor"
                value={summary.pickets}
                icon={ReceiptText}
            />
            <Summary
                label="Item Terjual"
                value={summary.items_sold}
                icon={PackageCheck}
            />
            <Summary
                label="Omzet"
                value={formatRupiah(summary.gross_amount)}
                icon={Wallet}
            />
        </section>
    );
}

function ReportsSection({ date, reports }: ReportsSectionProps) {
    return (
        <section className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col justify-between gap-2 border-b border-slate-100 p-4 sm:flex-row sm:items-center">
                <div>
                    <h2 className="font-semibold text-slate-950">
                        Laporan Masuk
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Laporan ini dikirim picket setelah selesai mencatat
                        transaksi hariannya.
                    </p>
                </div>
                <span className="inline-flex w-fit items-center gap-2 rounded-[6px] bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                    <CalendarDays className="size-3.5" />
                    {date}
                </span>
            </div>

            {reports.length === 0 ? (
                <EmptyState
                    title="Belum ada laporan masuk."
                    description={`Picket belum mengirim laporan pada ${date}.`}
                />
            ) : (
                <Table className="[&_tbody_tr:nth-child(even)]:bg-slate-50/70">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Picket</TableHead>
                            <TableHead>UP</TableHead>
                            <TableHead>Dikirim</TableHead>
                            <TableHead className="text-right">Item</TableHead>
                            <TableHead className="text-right">Omzet</TableHead>
                            <TableHead className="text-right">Detail</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {reports.map((report) => (
                            <TableRow
                                key={report.id}
                                className="hover:bg-blue-50/50"
                            >
                                <TableCell className="font-semibold text-slate-950">
                                    {report.picket_name}
                                </TableCell>
                                <TableCell>{report.up_jurusan_name}</TableCell>
                                <TableCell>
                                    <DateTime value={report.submitted_at} />
                                </TableCell>
                                <TableCell className="text-right tabular-nums">
                                    {report.total_sold} item
                                </TableCell>
                                <TableCell className="text-right font-semibold tabular-nums">
                                    {formatRupiah(report.total_revenue)}
                                </TableCell>
                                <TableCell className="text-right">
                                    <Button asChild variant="outline" size="sm">
                                        <Link
                                            href={`/admin-jurusan/reports/${report.id}`}
                                        >
                                            Detail
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            )}
        </section>
    );
}

function Summary({ label, value, icon: Icon }: SummaryProps) {
    return (
        <div className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm text-slate-500">{label}</p>
                <span className="grid size-8 place-items-center rounded-[8px] bg-slate-100 text-slate-600">
                    <Icon className="size-4" />
                </span>
            </div>
            <p className="mt-3 text-lg font-semibold text-slate-950 tabular-nums">
                {String(value)}
            </p>
        </div>
    );
}

function DateTime({ value }: DateTimeProps) {
    return value ? new Date(value).toLocaleString('id-ID') : '-';
}

function EmptyState({ title, description }: EmptyStateProps) {
    return (
        <div className="grid place-items-center p-10 text-center">
            <span className="grid size-12 place-items-center rounded-[8px] bg-slate-100 text-slate-500">
                <ReceiptText className="size-6" />
            </span>
            <p className="mt-3 font-medium text-slate-950">{title}</p>
            <p className="mt-1 text-sm text-slate-500">{description}</p>
        </div>
    );
}
