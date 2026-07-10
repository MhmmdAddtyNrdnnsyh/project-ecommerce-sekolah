import { Form, Head, Link } from '@inertiajs/react';
import {
    ClipboardList,
    PackagePlus,
    ShieldCheck,
    UserPlus,
    Warehouse,
} from 'lucide-react';
import { useState } from 'react';
import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Props = {
    upJurusans: {
        id: number;
        name: string;
        description: string | null;
        picket_officers: {
            id: number;
            name: string;
            email: string;
            up_jurusan_id: number | null;
        }[];
        products: {
            id: number;
            name: string;
            category_name: string;
            price: number;
            stock: number;
            status: {
                code: string;
                label: string;
            };
        }[];
        revenue_chart: {
            day: string;
            revenue: number;
        }[];
        summary: {
            revenue_7_days: number;
            up_product_count: number;
            active_consignment_count: number;
            available_stock: number;
            picket_names: string[];
        };
    }[];
    picketOptions: {
        id: number;
        name: string;
        email: string;
        up_jurusan_id: number | null;
    }[];
    categories: {
        id: number;
        name: string;
    }[];
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

const formatNumber = (value: number) =>
    new Intl.NumberFormat('id-ID').format(value);

const revenueChartConfig = {
    revenue: {
        label: 'Omzet jurusan',
        color: '#2563eb',
    },
} satisfies ChartConfig;

export default function AdminJurusanUpJurusan({
    upJurusans,
    categories,
}: Props) {
    const hasUpJurusan = upJurusans.length > 0;

    return (
        <>
            <Head title="UP Jurusan" />
            <main className="min-h-dvh space-y-6 bg-slate-50 p-4 sm:p-6">
                <section className="rounded-[8px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex items-start gap-4">
                        <span className="grid size-11 shrink-0 place-items-center rounded-[8px] bg-blue-50 text-blue-700">
                            <Warehouse className="size-5" />
                        </span>
                        <div>
                            <p className="text-sm font-medium text-blue-700">
                                Master UP
                            </p>
                            <h1 className="mt-2 text-2xl font-semibold text-slate-950">
                                UP Jurusan
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                Buat unit, assign picket, dan tambah produk
                                milik jurusan.
                            </p>
                        </div>
                    </div>
                </section>

                {hasUpJurusan ? (
                    <div className="flex items-center gap-3 rounded-[8px] border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700">
                        <ShieldCheck className="size-5 shrink-0" />
                        Akun admin jurusan ini sudah memiliki UP Jurusan.
                    </div>
                ) : (
                    <Form
                        action="/admin-jurusan/up-jurusan"
                        method="post"
                        className="grid gap-3 rounded-[8px] border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_2fr_auto]"
                    >
                        <Input
                            name="name"
                            placeholder="Nama UP Jurusan"
                            required
                        />
                        <Input
                            name="description"
                            placeholder="Deskripsi singkat"
                        />
                        <Button type="submit">Buat UP</Button>
                    </Form>
                )}

                {upJurusans.length > 0 && (
                    <div className="space-y-4">
                        {upJurusans.map((up) => (
                            <UpJurusanOverview key={up.id} up={up} />
                        ))}
                    </div>
                )}

                <div className="overflow-hidden rounded-[8px] border border-slate-200 bg-white shadow-sm">
                    {upJurusans.map((up) => (
                        <UpJurusanItem
                            key={up.id}
                            up={up}
                            categories={categories}
                        />
                    ))}
                    {upJurusans.length === 0 && (
                        <div className="grid place-items-center p-10 text-center">
                            <span className="grid size-12 place-items-center rounded-[8px] bg-slate-100 text-slate-500">
                                <Warehouse className="size-6" />
                            </span>
                            <p className="mt-3 font-medium text-slate-950">
                                Belum ada UP Jurusan.
                            </p>
                            <p className="mt-1 text-sm text-slate-500">
                                Buat UP pertama untuk mulai kelola produk dan
                                picket.
                            </p>
                        </div>
                    )}
                </div>
            </main>
        </>
    );
}

function UpJurusanOverview({ up }: { up: Props['upJurusans'][number] }) {
    return (
        <section className="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.8fr)]">
            <UpJurusanRevenueChart up={up} />
            <UpJurusanSummary up={up} />
        </section>
    );
}

function UpJurusanRevenueChart({ up }: { up: Props['upJurusans'][number] }) {
    return (
        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
            <CardHeader className="p-5 pb-0">
                <CardTitle>Omzet Jurusan</CardTitle>
                <CardDescription>
                    {up.name} - gross produk UP dan komisi titipan selama 7
                    hari.
                </CardDescription>
            </CardHeader>
            <CardContent className="p-5">
                {up.revenue_chart.every((item) => item.revenue === 0) ? (
                    <div className="grid h-64 place-items-center text-sm text-slate-500">
                        Belum ada omzet jurusan dalam 7 hari terakhir.
                    </div>
                ) : (
                    <ChartContainer
                        config={revenueChartConfig}
                        className="aspect-auto h-64 w-full"
                    >
                        <BarChart
                            accessibilityLayer
                            data={up.revenue_chart}
                            barCategoryGap="34%"
                            margin={{
                                top: 12,
                                right: 12,
                                left: -18,
                                bottom: 0,
                            }}
                        >
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="day"
                                tickLine={false}
                                tickMargin={10}
                                axisLine={false}
                            />
                            <YAxis
                                tickLine={false}
                                axisLine={false}
                                tickMargin={10}
                                width={68}
                                tickFormatter={(value) =>
                                    `Rp ${formatNumber(Number(value) / 1000)}rb`
                                }
                            />
                            <ChartTooltip
                                cursor={false}
                                content={
                                    <ChartTooltipContent
                                        indicator="dot"
                                        formatter={(value) => (
                                            <div className="flex min-w-36 flex-1 items-center justify-between gap-3">
                                                <span className="text-muted-foreground">
                                                    Omzet jurusan
                                                </span>
                                                <span className="font-mono font-medium text-foreground tabular-nums">
                                                    {formatRupiah(
                                                        Number(value),
                                                    )}
                                                </span>
                                            </div>
                                        )}
                                        className="rounded-[8px] bg-white text-slate-900 ring-slate-200"
                                    />
                                }
                            />
                            <Bar
                                dataKey="revenue"
                                fill="var(--color-revenue)"
                                radius={[4, 4, 0, 0]}
                                maxBarSize={42}
                            />
                        </BarChart>
                    </ChartContainer>
                )}
            </CardContent>
        </Card>
    );
}

function UpJurusanSummary({ up }: { up: Props['upJurusans'][number] }) {
    const summaryItems = [
        {
            label: 'Omzet 7 hari',
            value: formatRupiah(up.summary.revenue_7_days),
        },
        {
            label: 'Produk UP aktif',
            value: `${formatNumber(up.summary.up_product_count)} produk`,
        },
        {
            label: 'Titipan aktif',
            value: `${formatNumber(up.summary.active_consignment_count)} titipan`,
        },
        {
            label: 'Stok tersedia',
            value: `${formatNumber(up.summary.available_stock)} item`,
        },
    ];

    return (
        <Card className="gap-0 rounded-[8px] border-slate-100 py-0 shadow-sm">
            <CardHeader className="p-5 pb-0">
                <CardTitle>Ringkasan {up.name}</CardTitle>
                <CardDescription>
                    Snapshot operasional UP Jurusan untuk demo dan monitoring.
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 p-5">
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                    {summaryItems.map((item) => (
                        <div
                            key={item.label}
                            className="rounded-[8px] border border-slate-100 bg-slate-50 p-3"
                        >
                            <p className="text-xs font-medium text-slate-500">
                                {item.label}
                            </p>
                            <p className="mt-1 text-base font-semibold text-slate-950 tabular-nums">
                                {item.value}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="rounded-[8px] border border-blue-100 bg-blue-50 p-3">
                    <p className="flex items-center gap-2 text-sm font-medium text-blue-800">
                        <ClipboardList className="size-4" />
                        Picket bertugas
                    </p>
                    <p className="mt-2 text-sm leading-6 text-blue-700">
                        {up.summary.picket_names.length > 0
                            ? up.summary.picket_names.join(', ')
                            : 'Belum ada picket officer.'}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}

function UpJurusanItem({
    up,
    categories,
}: {
    up: Props['upJurusans'][number];
    categories: Props['categories'];
}) {
    const hasPicket = up.picket_officers.length > 0;
    const [isProductDialogOpen, setIsProductDialogOpen] = useState(false);

    return (
        <div className="border-b border-slate-100 p-4 last:border-b-0">
            <div className="flex flex-col justify-between gap-3 md:flex-row md:items-start">
                <div>
                    <p className="font-medium text-slate-950">{up.name}</p>
                    <p className="mt-1 text-sm text-slate-500">
                        {up.description ?? '-'}
                    </p>
                </div>
                <span className="w-fit rounded-[6px] bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-100">
                    Aktif
                </span>
            </div>
            <div className="mt-4 space-y-3">
                <div>
                    <p className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <UserPlus className="size-4 text-slate-500" />
                        Picket Officer
                    </p>
                    <p className="mt-1 text-sm text-slate-500">
                        {hasPicket
                            ? up.picket_officers
                                  .map(
                                      (picket) =>
                                          `${picket.name} (${picket.email})`,
                                  )
                                  .join(', ')
                            : 'Belum ada picket officer.'}
                    </p>
                </div>

                {!hasPicket && (
                    <div className="rounded-[8px] border border-blue-100 bg-blue-50 p-3">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-sm text-blue-700">
                                Buat akun picket officer dari halaman khusus.
                                Akun baru akan otomatis ditugaskan ke UP ini.
                            </p>
                            <Button
                                asChild
                                className="w-full rounded-[8px] sm:w-auto"
                            >
                                <Link href="/admin-jurusan/picket-officer/create">
                                    <UserPlus className="size-4" />
                                    Buat Picket
                                </Link>
                            </Button>
                        </div>
                    </div>
                )}
            </div>

            <div className="mt-4 border-t border-slate-100 pt-4">
                <div className="mb-4">
                    <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <Warehouse className="size-4 text-slate-500" />
                            Produk Milik {up.name}
                        </div>
                        <Dialog
                            open={isProductDialogOpen}
                            onOpenChange={setIsProductDialogOpen}
                        >
                            <DialogTrigger asChild>
                                <Button size="sm" className="w-full sm:w-auto">
                                    <PackagePlus className="size-4" />
                                    Tambah Produk UP
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                                <DialogHeader>
                                    <DialogTitle>Tambah Produk UP</DialogTitle>
                                    <DialogDescription>
                                        Tambahkan produk milik {up.name} untuk
                                        dijual melalui POS UP Jurusan.
                                    </DialogDescription>
                                </DialogHeader>
                                <Form
                                    action="/admin-jurusan/products"
                                    method="post"
                                    resetOnSuccess
                                    onSuccess={() =>
                                        setIsProductDialogOpen(false)
                                    }
                                    className="space-y-4"
                                >
                                    {({ errors, processing }) => (
                                        <>
                                            <input
                                                type="hidden"
                                                name="up_jurusan_id"
                                                value={up.id}
                                                readOnly
                                            />
                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`product-name-${up.id}`}
                                                    >
                                                        Nama produk
                                                    </Label>
                                                    <Input
                                                        id={`product-name-${up.id}`}
                                                        name="name"
                                                        placeholder="Nama produk UP"
                                                        required
                                                        aria-invalid={Boolean(
                                                            errors.name,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={errors.name}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`product-category-${up.id}`}
                                                    >
                                                        Kategori
                                                    </Label>
                                                    <Select
                                                        name="category_id"
                                                        required
                                                    >
                                                        <SelectTrigger
                                                            id={`product-category-${up.id}`}
                                                            aria-invalid={Boolean(
                                                                errors.category_id,
                                                            )}
                                                            className="w-full rounded-[8px] border-slate-200 bg-white"
                                                        >
                                                            <SelectValue placeholder="Pilih kategori" />
                                                        </SelectTrigger>
                                                        <SelectContent className="rounded-[8px] bg-white text-slate-900 ring-slate-200">
                                                            <SelectGroup>
                                                                <SelectLabel>
                                                                    Kategori
                                                                </SelectLabel>
                                                                {categories.map(
                                                                    (
                                                                        category,
                                                                    ) => (
                                                                        <SelectItem
                                                                            key={
                                                                                category.id
                                                                            }
                                                                            value={String(
                                                                                category.id,
                                                                            )}
                                                                        >
                                                                            {
                                                                                category.name
                                                                            }
                                                                        </SelectItem>
                                                                    ),
                                                                )}
                                                            </SelectGroup>
                                                        </SelectContent>
                                                    </Select>
                                                    <InputError
                                                        message={
                                                            errors.category_id
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2 sm:col-span-2">
                                                    <Label
                                                        htmlFor={`product-description-${up.id}`}
                                                    >
                                                        Deskripsi
                                                    </Label>
                                                    <Input
                                                        id={`product-description-${up.id}`}
                                                        name="description"
                                                        placeholder="Deskripsi produk"
                                                        required
                                                        aria-invalid={Boolean(
                                                            errors.description,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={
                                                            errors.description
                                                        }
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`product-price-${up.id}`}
                                                    >
                                                        Harga
                                                    </Label>
                                                    <Input
                                                        id={`product-price-${up.id}`}
                                                        name="price"
                                                        type="number"
                                                        min="1"
                                                        placeholder="Harga"
                                                        required
                                                        aria-invalid={Boolean(
                                                            errors.price,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={errors.price}
                                                    />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label
                                                        htmlFor={`product-stock-${up.id}`}
                                                    >
                                                        Stok awal
                                                    </Label>
                                                    <Input
                                                        id={`product-stock-${up.id}`}
                                                        name="stock"
                                                        type="number"
                                                        min="0"
                                                        placeholder="Stok"
                                                        required
                                                        aria-invalid={Boolean(
                                                            errors.stock,
                                                        )}
                                                    />
                                                    <InputError
                                                        message={errors.stock}
                                                    />
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <DialogClose asChild>
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        disabled={processing}
                                                    >
                                                        Batal
                                                    </Button>
                                                </DialogClose>
                                                <Button
                                                    type="submit"
                                                    disabled={processing}
                                                >
                                                    {processing
                                                        ? 'Menyimpan...'
                                                        : 'Tambah Produk'}
                                                </Button>
                                            </DialogFooter>
                                        </>
                                    )}
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                    {up.products.length === 0 ? (
                        <div className="rounded-[8px] border border-dashed border-slate-200 p-4 text-sm text-slate-500">
                            Belum ada produk milik UP Jurusan.
                        </div>
                    ) : (
                        <div className="overflow-x-auto rounded-[8px] border border-slate-100">
                            <table className="min-w-full text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-medium text-slate-500">
                                    <tr>
                                        <th className="px-4 py-3">Produk</th>
                                        <th className="px-4 py-3">Kategori</th>
                                        <th className="px-4 py-3 text-right">
                                            Stok
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Harga
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {up.products.map((product) => (
                                        <tr
                                            key={product.id}
                                            className="bg-white"
                                        >
                                            <td className="px-4 py-3">
                                                <p className="font-medium text-slate-950">
                                                    {product.name}
                                                </p>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    Dikelola {up.name}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {product.category_name}
                                            </td>
                                            <td className="px-4 py-3 text-right tabular-nums">
                                                {product.stock}
                                            </td>
                                            <td className="px-4 py-3 text-right font-medium tabular-nums">
                                                {formatRupiah(product.price)}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <span className="rounded-[6px] bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
                                                    {product.status.label}
                                                </span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
