import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Movement = {
    id: number;
    type: 'in' | 'out';
    quantity: number;
    gross_amount: number;
    commission_amount: number;
    seller_amount: number;
    picket_name: string;
    product_name: string;
    up_jurusan_name: string;
    created_at: string | null;
};

type Props = {
    filters: { date: string };
    summary: {
        in: number;
        out: number;
        gross_amount: number;
        commission_amount: number;
        seller_amount: number;
    };
    movements: Movement[];
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
    movements,
}: Props) {
    const [date, setDate] = useState(filters.date);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        router.get('/admin-jurusan/reports', { date }, { preserveState: true });
    };

    return (
        <>
            <Head title="Laporan UP Jurusan" />
            <main className="space-y-6 p-4 sm:p-6">
                <section className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Laporan UP Jurusan
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Catatan barang masuk dan keluar harian.
                        </p>
                    </div>
                    <form onSubmit={submit} className="flex gap-2">
                        <Input
                            type="date"
                            value={date}
                            onChange={(event) => setDate(event.target.value)}
                            className="w-40"
                        />
                        <Button type="submit">Filter</Button>
                    </form>
                </section>

                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div className="rounded-[8px] border border-slate-200 bg-white p-4">
                        <p className="text-sm text-slate-500">Barang Masuk</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-950">
                            {summary.in}
                        </p>
                    </div>
                    <div className="rounded-[8px] border border-slate-200 bg-white p-4">
                        <p className="text-sm text-slate-500">Barang Keluar</p>
                        <p className="mt-2 text-2xl font-semibold text-slate-950">
                            {summary.out}
                        </p>
                    </div>
                    <Summary label="Omzet" value={formatRupiah(summary.gross_amount)} />
                    <Summary
                        label="Komisi UP"
                        value={formatRupiah(summary.commission_amount)}
                    />
                    <Summary
                        label="Hak Seller"
                        value={formatRupiah(summary.seller_amount)}
                    />
                </section>

                <section className="overflow-hidden rounded-[8px] border border-slate-200 bg-white">
                    {movements.map((movement) => (
                        <div
                            key={movement.id}
                            className="grid gap-2 border-b border-slate-100 p-4 text-sm last:border-b-0 md:grid-cols-[1fr_1fr_auto] md:items-center"
                        >
                            <div>
                                <p className="font-medium text-slate-950">
                                    {movement.product_name}
                                </p>
                                <p className="text-slate-500">
                                    {movement.up_jurusan_name} -{' '}
                                    {movement.picket_name}
                                </p>
                            </div>
                            <p className="text-slate-600">
                                {movement.created_at
                                    ? new Date(
                                          movement.created_at,
                                      ).toLocaleString('id-ID')
                                    : '-'}
                            </p>
                            <span className="rounded-[6px] bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                {movement.type === 'in' ? 'Masuk' : 'Keluar'}{' '}
                                {movement.quantity}
                                {movement.type === 'out' &&
                                    ` - ${formatRupiah(movement.gross_amount)}`}
                            </span>
                        </div>
                    ))}
                    {movements.length === 0 && (
                        <div className="p-6 text-sm text-slate-500">
                            Belum ada laporan pada tanggal ini.
                        </div>
                    )}
                </section>
            </main>
        </>
    );
}

function Summary({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-[8px] border border-slate-200 bg-white p-4">
            <p className="text-sm text-slate-500">{label}</p>
            <p className="mt-2 text-lg font-semibold text-slate-950">
                {value}
            </p>
        </div>
    );
}
