import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Banknote,
    ClipboardCheck,
    FileCheck2,
    PackageCheck,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Dashboard = {
    today_sales: number;
    pending_requests: number;
    awaiting_receive: number;
    report_status: {
        code: 'no_picket' | 'not_submitted' | 'submitted';
        label: string;
        picket_name: string | null;
        submitted_at: string | null;
    };
    recent_requests: {
        id: number;
        seller_name: string;
        product_name: string;
        up_jurusan_name: string;
        requested_quantity: number;
        href: string;
        status: { code: string; label: string };
    }[];
};

type Props = { dashboard: Dashboard };

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatTime = (value: string) =>
    new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));

const statusStyles: Record<string, string> = {
    pending_approval: 'bg-amber-50 text-amber-700 ring-amber-100',
    approved: 'bg-blue-50 text-blue-700 ring-blue-100',
    received: 'bg-blue-50 text-blue-700 ring-blue-100',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
    rejected: 'bg-rose-50 text-rose-700 ring-rose-100',
    cancelled: 'bg-slate-100 text-slate-600 ring-slate-200',
};

export default function AdminJurusanDashboard({ dashboard }: Props) {
    const stats = [
        {
            label: 'Penjualan UP Hari Ini',
            value: formatRupiah(dashboard.today_sales),
            icon: Banknote,
            href: '/admin-jurusan/reports',
        },
        {
            label: 'Titipan Menunggu Persetujuan',
            value: dashboard.pending_requests,
            icon: ClipboardCheck,
            href: '/admin-jurusan/consignments',
        },
        {
            label: 'Titipan Menunggu Diterima',
            value: dashboard.awaiting_receive,
            icon: PackageCheck,
            href: '/admin-jurusan/consignments',
        },
        {
            label: 'Laporan Hari Ini',
            value: dashboard.report_status.label,
            icon: FileCheck2,
            href: '/admin-jurusan/reports',
        },
    ];

    return (
        <>
            <Head title="Dashboard Admin Jurusan" />
            <main className="min-h-dvh space-y-6 bg-slate-50 p-4 sm:p-6">
                <header className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                        <div>
                            <p className="text-sm font-medium text-blue-700">
                                Admin Jurusan
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-slate-950">
                                Dashboard UP Jurusan
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                Tinjau penjualan hari ini, titipan, penerimaan,
                                dan laporan picket.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link href="/admin-jurusan/reports">
                                    Lihat Laporan
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href="/admin-jurusan/consignments">
                                    Tinjau Titipan
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </header>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map(({ label, value, icon: Icon, href }) => (
                        <Link
                            key={label}
                            href={href}
                            className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200 hover:bg-blue-50/40"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm font-medium text-slate-500">
                                    {label}
                                </p>
                                <span className="grid size-9 shrink-0 place-items-center rounded-[8px] bg-blue-50 text-blue-700">
                                    <Icon className="size-5" />
                                </span>
                            </div>
                            <p className="mt-4 text-2xl font-semibold break-words text-slate-950 tabular-nums">
                                {value}
                            </p>
                        </Link>
                    ))}
                </section>

                <section
                    className={cn(
                        'flex items-start gap-3 rounded-[8px] border p-4 text-sm',
                        dashboard.report_status.code === 'submitted'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                            : dashboard.report_status.code === 'not_submitted'
                              ? 'border-amber-200 bg-amber-50 text-amber-800'
                              : 'border-slate-200 bg-white text-slate-700',
                    )}
                >
                    <FileCheck2 className="mt-0.5 size-5 shrink-0" />
                    <div className="min-w-0 flex-1">
                        <p className="font-semibold">Laporan Hari Ini</p>
                        <p className="mt-1">
                            {dashboard.report_status.code === 'submitted' &&
                            dashboard.report_status.submitted_at
                                ? `${dashboard.report_status.picket_name} mengirim laporan pukul ${formatTime(dashboard.report_status.submitted_at)}.`
                                : dashboard.report_status.code ===
                                    'not_submitted'
                                  ? `${dashboard.report_status.picket_name} belum mengirim laporan hari ini.`
                                  : 'Belum ada picket yang ditugaskan ke UP Jurusan.'}
                        </p>
                    </div>
                    <Button asChild size="sm" variant="outline">
                        <Link href="/admin-jurusan/reports">Buka laporan</Link>
                    </Button>
                </section>

                <section className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between gap-4 border-b border-slate-100 p-4">
                        <div>
                            <h2 className="font-semibold text-slate-950">
                                Tindakan Titipan
                            </h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Request pending tertua ditampilkan lebih dahulu.
                            </p>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/admin-jurusan/consignments">
                                Lihat Semua
                            </Link>
                        </Button>
                    </div>
                    {dashboard.recent_requests.map((item) => (
                        <Link
                            key={item.id}
                            href={item.href}
                            className="grid gap-3 border-b border-slate-100 p-4 text-sm transition last:border-b-0 hover:bg-slate-50 md:grid-cols-[1.4fr_1fr_auto] md:items-center"
                        >
                            <div>
                                <p className="font-medium text-slate-950">
                                    {item.product_name}
                                </p>
                                <p className="text-slate-500">
                                    {item.seller_name} - {item.up_jurusan_name}
                                </p>
                            </div>
                            <p className="text-slate-600 tabular-nums">
                                {item.requested_quantity} item
                            </p>
                            <Badge
                                className={cn(
                                    'w-fit rounded-[6px] ring-1',
                                    statusStyles[item.status.code] ??
                                        'bg-slate-100 text-slate-600 ring-slate-200',
                                )}
                            >
                                {item.status.label}
                            </Badge>
                        </Link>
                    ))}
                    {dashboard.recent_requests.length === 0 && (
                        <div className="p-8 text-center text-sm text-slate-500">
                            Belum ada request titip barang.
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}
