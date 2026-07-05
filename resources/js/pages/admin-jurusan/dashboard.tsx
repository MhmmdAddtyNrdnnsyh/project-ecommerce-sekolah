import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    ClipboardCheck,
    PackageCheck,
    Warehouse,
} from 'lucide-react';
import { Button } from '@/components/ui/button';

type Dashboard = {
    total_up_jurusans: number;
    pending_requests: number;
    approved_requests: number;
    active_stock: number;
    recent_requests: {
        id: number;
        seller_name: string;
        product_name: string;
        up_jurusan_name: string;
        requested_quantity: number;
        status: { code: string; label: string };
    }[];
};

type Props = {
    dashboard: Dashboard;
};

export default function AdminJurusanDashboard({ dashboard }: Props) {
    const stats = [
        {
            label: 'UP Jurusan',
            value: dashboard.total_up_jurusans,
            icon: Warehouse,
        },
        {
            label: 'Request Pending',
            value: dashboard.pending_requests,
            icon: ClipboardCheck,
        },
        {
            label: 'Request Disetujui',
            value: dashboard.approved_requests,
            icon: ClipboardCheck,
        },
        {
            label: 'Stok Titipan Aktif',
            value: dashboard.active_stock,
            icon: PackageCheck,
        },
    ];

    return (
        <>
            <Head title="Dashboard Admin Jurusan" />
            <main className="min-h-dvh space-y-6 bg-slate-50 p-4 sm:p-6">
                <section className="overflow-hidden rounded-[8px] border border-slate-200 bg-white">
                    <div className="grid gap-5 p-5 md:grid-cols-[1fr_auto] md:items-center">
                        <div>
                            <p className="text-sm font-medium text-blue-700">
                                Admin Jurusan
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-slate-950">
                                Dashboard UP Jurusan
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                Pantau request titip barang, stok aktif, dan
                                aktivitas terbaru dari satu tempat.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link href="/admin-jurusan/up-jurusan">
                                    UP Jurusan
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href="/admin-jurusan/consignments">
                                    Review Request
                                    <ArrowRight className="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map(({ label, value, icon: Icon }) => (
                        <div
                            key={label}
                            className="rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm font-medium text-slate-500">
                                    {label}
                                </p>
                                <span className="grid size-9 place-items-center rounded-[8px] bg-blue-50 text-blue-700">
                                    <Icon className="size-5" />
                                </span>
                            </div>
                            <p className="mt-4 text-3xl font-semibold text-slate-950 tabular-nums">
                                {value}
                            </p>
                        </div>
                    ))}
                </section>

                <section className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
                    <div className="flex items-center justify-between gap-4 border-b border-slate-100 p-4">
                        <div>
                            <h2 className="font-semibold text-slate-950">
                                Request Terbaru
                            </h2>
                            <p className="mt-1 text-sm text-slate-500">
                                Prioritaskan request yang masih menunggu review.
                            </p>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/admin-jurusan/consignments">
                                Lihat Semua
                            </Link>
                        </Button>
                    </div>
                    {dashboard.recent_requests.map((item) => (
                        <div
                            key={item.id}
                            className="grid gap-3 border-b border-slate-100 p-4 text-sm last:border-b-0 md:grid-cols-[1.4fr_1fr_auto] md:items-center"
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
                            <span className="w-fit rounded-[6px] bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-100">
                                {item.status.label}
                            </span>
                        </div>
                    ))}
                    {dashboard.recent_requests.length === 0 && (
                        <div className="p-6 text-sm text-slate-500">
                            Belum ada request titip barang.
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}
