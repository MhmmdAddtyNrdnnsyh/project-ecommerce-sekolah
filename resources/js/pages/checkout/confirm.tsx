import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CreditCard,
    MapPin,
    Package,
    ShoppingCart,
    Store,
    Truck,
    User,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { checkout, home } from '@/routes';
import { index as cartIndex } from '@/routes/cart';
import { show as catalogShow } from '@/routes/catalog';
import type { Auth } from '@/types';

type CheckoutItem = {
    id: number;
    source: 'cart' | 'buy_now';
    quantity: number;
    subtotal: number;
    product: {
        id: number;
        name: string;
        slug: string;
        price: number;
        stock: number;
        is_pre_order: boolean;
        pre_order_estimate_days: number | null;
        pre_order_deadline: string | null;
        pre_order_min_quantity: number | null;
        pre_order_note: string | null;
        image: string | null;
        seller: {
            id: number;
            name: string;
        };
        category: {
            id: number;
            name: string;
            slug: string;
        };
        pickup_place: {
            id: number;
            name: string;
        } | null;
    };
};

type Props = {
    items: CheckoutItem[];
    summary: {
        total_items: number;
        total_price: number;
    };
};

type PageProps = {
    auth: Auth;
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const imageSource = (image: string | null) => {
    if (!image) {
        return null;
    }

    return image.startsWith('http') || image.startsWith('/')
        ? image
        : `/storage/${image}`;
};

export default function CheckoutConfirm({ items, summary }: Props) {
    const { auth } = usePage<PageProps>().props;
    const [pickupMethod, setPickupMethod] = useState<'pickup' | 'delivery'>(
        'pickup',
    );
    const [leaveDialogOpen, setLeaveDialogOpen] = useState(false);
    const [pendingLeaveUrl, setPendingLeaveUrl] = useState<string | null>(null);
    const allowNavigationRef = useRef(false);
    const checkoutSubmittingRef = useRef(false);
    const buyer = auth.user;
    const hasInvalidStock = items.some(
        (item) =>
            !item.product.is_pre_order &&
            (item.product.stock <= 0 || item.quantity > item.product.stock),
    );
    const hasPendingCheckout = items.length > 0;

    useEffect(() => {
        if (!hasPendingCheckout) {
            return;
        }

        const warnBeforeLeave = (event: BeforeUnloadEvent) => {
            if (checkoutSubmittingRef.current) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        };

        window.addEventListener('beforeunload', warnBeforeLeave);

        return () =>
            window.removeEventListener('beforeunload', warnBeforeLeave);
    }, [hasPendingCheckout]);

    useEffect(() => {
        if (!hasPendingCheckout) {
            return;
        }

        return router.on('before', (event) => {
            if (allowNavigationRef.current || checkoutSubmittingRef.current) {
                return;
            }

            if (event.detail.visit.method !== 'get') {
                return;
            }

            if (event.detail.visit.prefetch) {
                return;
            }

            event.preventDefault();
            setPendingLeaveUrl(event.detail.visit.url.href);
            setLeaveDialogOpen(true);
        });
    }, [hasPendingCheckout]);

    useEffect(() => {
        return router.on('finish', () => {
            checkoutSubmittingRef.current = false;
        });
    }, []);

    const continuePendingNavigation = () => {
        if (!pendingLeaveUrl) {
            setLeaveDialogOpen(false);

            return;
        }

        allowNavigationRef.current = true;
        setLeaveDialogOpen(false);
        router.visit(pendingLeaveUrl);
        window.setTimeout(() => {
            allowNavigationRef.current = false;
        }, 0);
    };

    return (
        <>
            <Head title="Konfirmasi Pembayaran" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
                <div className="mx-auto grid w-full max-w-7xl gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <section className="space-y-5">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <Badge className="mb-3 rounded-full bg-blue-50 text-blue-700">
                                    <CreditCard className="size-3.5" />
                                    Checkout
                                </Badge>
                                <h1 className="text-2xl font-semibold text-slate-950">
                                    Konfirmasi Pembayaran
                                </h1>
                                <p className="mt-1 text-sm text-slate-500">
                                    Cek item sebelum pesanan dibuat.
                                </p>
                            </div>
                            <Button
                                asChild
                                variant="outline"
                                className="h-10 w-fit rounded-full border-slate-200 bg-white"
                            >
                                <Link href={cartIndex()}>
                                    <ArrowLeft className="size-4" />
                                    Cart
                                </Link>
                            </Button>
                        </div>

                        <Card className="rounded-[8px] border-slate-200 bg-white shadow-sm">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg font-semibold text-slate-950">
                                    <User className="size-5 text-blue-700" />
                                    Informasi pembeli
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 sm:grid-cols-2">
                                <div className="rounded-[8px] border border-slate-200 bg-slate-50 p-3">
                                    <p className="text-xs font-medium text-slate-500">
                                        Nama
                                    </p>
                                    <p className="mt-1 font-semibold text-slate-950">
                                        {buyer?.name ?? '-'}
                                    </p>
                                </div>
                                <div className="rounded-[8px] border border-slate-200 bg-slate-50 p-3">
                                    <p className="text-xs font-medium text-slate-500">
                                        Email
                                    </p>
                                    <p className="mt-1 font-semibold break-all text-slate-950">
                                        {buyer?.email ?? '-'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        {items.length === 0 ? (
                            <div className="rounded-[8px] border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
                                <div className="mx-auto flex size-12 items-center justify-center rounded-[8px] bg-blue-50 text-blue-700">
                                    <ShoppingCart className="size-5" />
                                </div>
                                <h2 className="mt-4 text-lg font-semibold text-slate-950">
                                    Cart masih kosong
                                </h2>
                                <p className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                                    Tambahkan produk dulu sebelum konfirmasi
                                    pembayaran.
                                </p>
                                <Button asChild className="mt-5 h-10">
                                    <Link href={home()}>Lihat produk</Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {items.map((item) => {
                                    const src = imageSource(item.product.image);
                                    const stockIssue =
                                        !item.product.is_pre_order &&
                                        (item.product.stock <= 0 ||
                                            item.quantity >
                                                item.product.stock);

                                    return (
                                        <Link
                                            key={item.id}
                                            href={catalogShow(
                                                item.product.slug,
                                            )}
                                            className="flex gap-3 rounded-[8px] border border-slate-200 bg-white p-3 shadow-sm"
                                        >
                                            <div className="size-20 shrink-0 overflow-hidden rounded-[8px] bg-blue-50 text-blue-700">
                                                {src ? (
                                                    <img
                                                        src={src}
                                                        alt={item.product.name}
                                                        className="size-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="flex size-full items-center justify-center">
                                                        <Package className="size-6" />
                                                    </div>
                                                )}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="line-clamp-2 font-semibold text-slate-950">
                                                    {item.product.name}
                                                </p>
                                                <p className="mt-1 flex items-center gap-1 text-xs text-slate-500">
                                                    <Store className="size-3.5" />
                                                    {item.product.seller.name}
                                                </p>
                                                <p className="mt-1 flex items-center gap-1 text-xs text-slate-500">
                                                    <MapPin className="size-3.5" />
                                                    Ambil di{' '}
                                                    {item.product.pickup_place
                                                        ?.name ??
                                                        'titik pickup sekolah'}
                                                </p>
                                                <div className="mt-3 flex items-center justify-between gap-3">
                                                    <span
                                                        className={`text-xs ${
                                                            stockIssue
                                                                ? 'text-rose-600'
                                                                : 'text-slate-500'
                                                        }`}
                                                    >
                                                        {item.quantity} item
                                                        {item.product
                                                            .is_pre_order &&
                                                            ` • PO ${item.product.pre_order_estimate_days} hari`}
                                                    </span>
                                                    <span className="font-semibold text-slate-950">
                                                        {formatRupiah(
                                                            item.subtotal,
                                                        )}
                                                    </span>
                                                </div>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        )}
                    </section>

                    <Card className="h-fit rounded-[8px] border-slate-200 bg-white shadow-sm lg:sticky lg:top-24">
                        <CardHeader>
                            <CardTitle className="text-xl font-semibold text-slate-950">
                                Ringkasan pembayaran
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex justify-between text-sm text-slate-600">
                                <span>Total item</span>
                                <span className="font-semibold text-slate-950">
                                    {summary.total_items}
                                </span>
                            </div>
                            <div className="flex justify-between border-t border-slate-100 pt-4">
                                <span className="text-sm font-medium text-slate-600">
                                    Total bayar
                                </span>
                                <span className="text-xl font-semibold text-slate-950">
                                    {formatRupiah(summary.total_price)}
                                </span>
                            </div>
                            <Form
                                {...checkout.form()}
                                disableWhileProcessing
                                onSubmit={() => {
                                    checkoutSubmittingRef.current = true;
                                }}
                            >
                                {({ processing, errors }) => (
                                    <div className="space-y-5">
                                        {items.map((item) => (
                                            <div key={item.id}>
                                                {item.source === 'buy_now' ? (
                                                    <>
                                                        <input
                                                            type="hidden"
                                                            name="buy_now_product_id"
                                                            value={
                                                                item.product.id
                                                            }
                                                            readOnly
                                                        />
                                                        <input
                                                            type="hidden"
                                                            name="buy_now_quantity"
                                                            value={
                                                                item.quantity
                                                            }
                                                            readOnly
                                                        />
                                                    </>
                                                ) : (
                                                    <input
                                                        type="hidden"
                                                        name="selected_cart_item_ids[]"
                                                        value={item.id}
                                                        readOnly
                                                    />
                                                )}
                                            </div>
                                        ))}
                                        <div className="space-y-3">
                                            <div>
                                                <h2 className="text-sm font-semibold text-slate-800">
                                                    Pickup method
                                                </h2>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    Pilih cara menerima pesanan.
                                                </p>
                                            </div>
                                            <div className="grid gap-2">
                                                <Label className="flex cursor-pointer items-start gap-3 rounded-[8px] border border-slate-200 bg-white p-3 transition hover:bg-slate-50 has-checked:border-blue-300 has-checked:bg-blue-50">
                                                    <input
                                                        type="radio"
                                                        name="pickup_method"
                                                        value="delivery"
                                                        checked={
                                                            pickupMethod ===
                                                            'delivery'
                                                        }
                                                        onChange={() =>
                                                            setPickupMethod(
                                                                'delivery',
                                                            )
                                                        }
                                                        className="mt-1"
                                                    />
                                                    <span className="flex min-w-0 gap-2">
                                                        <Truck className="mt-0.5 size-4 shrink-0 text-blue-700" />
                                                        <span>
                                                            <span className="block text-sm font-semibold text-slate-950">
                                                                Diantar
                                                            </span>
                                                            <span className="block text-xs leading-5 text-slate-500">
                                                                Tulis lokasi
                                                                penitipan yang
                                                                mudah ditemukan.
                                                            </span>
                                                        </span>
                                                    </span>
                                                </Label>

                                                <Label className="flex cursor-pointer items-start gap-3 rounded-[8px] border border-slate-200 bg-white p-3 transition hover:bg-slate-50 has-checked:border-blue-300 has-checked:bg-blue-50">
                                                    <input
                                                        type="radio"
                                                        name="pickup_method"
                                                        value="pickup"
                                                        checked={
                                                            pickupMethod ===
                                                            'pickup'
                                                        }
                                                        onChange={() =>
                                                            setPickupMethod(
                                                                'pickup',
                                                            )
                                                        }
                                                        className="mt-1"
                                                    />
                                                    <span className="flex min-w-0 gap-2">
                                                        <MapPin className="mt-0.5 size-4 shrink-0 text-blue-700" />
                                                        <span>
                                                            <span className="block text-sm font-semibold text-slate-950">
                                                                Ambil di tempat
                                                            </span>
                                                            <span className="block text-xs leading-5 text-slate-500">
                                                                Ambil pesanan di
                                                                titik pickup
                                                                sekolah.
                                                            </span>
                                                        </span>
                                                    </span>
                                                </Label>
                                            </div>
                                            <InputError
                                                message={errors.pickup_method}
                                            />

                                            {pickupMethod === 'delivery' && (
                                                <div className="space-y-2">
                                                    <Label htmlFor="pickup_location">
                                                        Mau ditaruh dimana?
                                                    </Label>
                                                    <Textarea
                                                        id="pickup_location"
                                                        name="pickup_location"
                                                        className="min-h-24 rounded-[8px] border-slate-200 bg-white"
                                                        placeholder="Contoh: titip di meja piket, depan kelas XI RPL 1, atau dekat koperasi."
                                                        required
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.pickup_location
                                                        }
                                                    />
                                                </div>
                                            )}
                                        </div>

                                        <div className="space-y-3">
                                            <div>
                                                <h2 className="text-sm font-semibold text-slate-800">
                                                    Metode pembayaran
                                                </h2>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    Untuk MVP saat ini, metode
                                                    pembayaran hanya tunai.
                                                </p>
                                            </div>
                                            <input
                                                type="hidden"
                                                name="payment_method"
                                                value="cash"
                                                readOnly
                                            />
                                            <div className="flex items-start gap-3 rounded-[8px] border border-blue-200 bg-blue-50 p-3">
                                                <CreditCard className="mt-0.5 size-4 shrink-0 text-blue-700" />
                                                <div>
                                                    <p className="text-sm font-semibold text-slate-950">
                                                        Tunai
                                                    </p>
                                                    <p className="mt-1 text-xs leading-5 text-slate-500">
                                                        Bayar langsung saat
                                                        mengambil atau menerima
                                                        pesanan. Admin dapat
                                                        menandai pembayaran
                                                        sebagai lunas setelah
                                                        diterima.
                                                    </p>
                                                </div>
                                            </div>
                                            <InputError
                                                message={errors.payment_method}
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={
                                                processing ||
                                                items.length === 0 ||
                                                hasInvalidStock
                                            }
                                            className="h-11 w-full"
                                        >
                                            {processing && <Spinner />}
                                            Bayar sekarang
                                        </Button>
                                        {hasInvalidStock && (
                                            <p className="text-xs text-rose-600">
                                                Ada item dengan stok tidak
                                                cukup. Update cart dulu.
                                            </p>
                                        )}
                                        <InputError message={errors.cart} />
                                    </div>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </main>
            <AlertDialog
                open={leaveDialogOpen}
                onOpenChange={(open) => {
                    setLeaveDialogOpen(open);

                    if (!open) {
                        setPendingLeaveUrl(null);
                    }
                }}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            Tinggalkan konfirmasi pembayaran?
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            Pesanan belum dibuat. Jika keluar dari halaman ini,
                            buyer perlu membuka checkout lagi dari cart atau
                            produk.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel asChild>
                            <Button
                                type="button"
                                variant="outline"
                                className="rounded-[8px]"
                            >
                                Tetap di halaman
                            </Button>
                        </AlertDialogCancel>
                        <AlertDialogAction asChild>
                            <Button
                                type="button"
                                className="rounded-[8px] bg-rose-600 text-white hover:bg-rose-700"
                                onClick={continuePendingNavigation}
                            >
                                Tinggalkan
                            </Button>
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
