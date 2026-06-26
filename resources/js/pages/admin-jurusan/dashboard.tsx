import { Head, Link } from '@inertiajs/react';
import { ClipboardCheck, PackageCheck, Warehouse } from 'lucide-react';
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
            <main className="space-y-6 p-4 sm:p-6">
                <section className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Dashboard Admin Jurusan
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Ringkasan UP Jurusan dan request titip barang.
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href="/admin-jurusan/up-jurusan">
                                UP Jurusan
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href="/admin-jurusan/consignments">
                                Request Titip
                            </Link>
                        </Button>
                    </div>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {stats.map(({ label, value, icon: Icon }) => (
                        <div
                            key={label}
                            className="rounded-[8px] border border-slate-200 bg-white p-4"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <p className="text-sm font-medium text-slate-500">
                                    {label}
                                </p>
                                <Icon className="size-5 text-blue-600" />
                            </div>
                            <p className="mt-3 text-2xl font-semibold text-slate-950">
                                {value}
                            </p>
                        </div>
                    ))}
                </section>

                <section className="overflow-hidden rounded-[8px] border border-slate-200 bg-white">
                    <div className="border-b border-slate-100 p-4">
                        <h2 className="font-semibold text-slate-950">
                            Request Terbaru
                        </h2>
                    </div>
                    {dashboard.recent_requests.map((item) => (
                        <div
                            key={item.id}
                            className="grid gap-2 border-b border-slate-100 p-4 text-sm last:border-b-0 md:grid-cols-[1fr_1fr_auto] md:items-center"
                        >
                            <div>
                                <p className="font-medium text-slate-950">
                                    {item.product_name}
                                </p>
                                <p className="text-slate-500">
                                    {item.seller_name} - {item.up_jurusan_name}
                                </p>
                            </div>
                            <p className="text-slate-600">
                                {item.requested_quantity} item
                            </p>
                            <span className="rounded-[6px] bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
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
