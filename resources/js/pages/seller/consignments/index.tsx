import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { create as sellerProductsCreate } from '@/routes/seller/products';

type Props = {
    consignments: {
        id: number;
        product_name: string;
        up_jurusan_name: string;
        requested_quantity: number;
        received_quantity: number;
        sold_quantity: number;
        commission_rate: number;
        seller_earnings: number;
        paid_amount: number;
        unpaid_amount: number;
        status: { label: string };
    }[];
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function SellerConsignments({ consignments }: Props) {
    return (
        <>
            <Head title="Titip Barang" />
            <main className="space-y-6 p-4 sm:p-6">
                <section className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold text-slate-950">
                            Titip Barang UP Jurusan
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Pantau status produk yang dijual lewat UP Jurusan.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={sellerProductsCreate()}>
                            Tambah Produk Titipan
                        </Link>
                    </Button>
                </section>

                <div className="overflow-hidden rounded-[8px] border border-slate-200 bg-white">
                    {consignments.map((item) => (
                        <div
                            key={item.id}
                            className="grid gap-2 border-b border-slate-100 p-4 text-sm last:border-b-0 md:grid-cols-6"
                        >
                            <span className="font-medium text-slate-950">
                                {item.product_name}
                            </span>
                            <span>{item.up_jurusan_name}</span>
                            <span>Request {item.requested_quantity}</span>
                            <span>
                                Diterima {item.received_quantity} / Keluar{' '}
                                {item.sold_quantity}
                            </span>
                            <span>
                                Saldo {formatRupiah(item.unpaid_amount)}
                            </span>
                            <span>{item.status.label}</span>
                        </div>
                    ))}
                    {consignments.length === 0 && (
                        <div className="p-6 text-sm text-slate-500">
                            Belum ada request titip barang.
                        </div>
                    )}
                </div>
            </main>
        </>
    );
}
