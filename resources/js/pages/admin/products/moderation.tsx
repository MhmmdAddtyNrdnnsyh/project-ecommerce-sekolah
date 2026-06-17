import { Form, Head } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock3,
    PackageCheck,
    Store,
    XCircle,
} from 'lucide-react';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    approve as approveProduct,
    index as moderationIndex,
    reject as rejectProduct,
} from '@/routes/admin/products/moderation';

type PendingProduct = {
    id: number;
    name: string;
    slug: string;
    description: string;
    price: number;
    stock: number;
    submitted_at: string;
    seller: {
        id: number;
        name: string;
        email: string;
    };
    category: {
        id: number;
        name: string;
        slug: string;
    };
};

type AdminProductModerationProps = {
    products: PendingProduct[];
};

const formatRupiah = (value: number) =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(value);

export default function AdminProductModeration({
    products,
}: AdminProductModerationProps) {
    return (
        <>
            <Head title="Moderasi Produk" />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <Badge className="rounded-[6px] bg-blue-50 text-blue-700">
                                    <PackageCheck className="size-3.5" />
                                    Admin Center
                                </Badge>
                                <Badge className="rounded-[6px] bg-amber-50 text-amber-700">
                                    {products.length} pending
                                </Badge>
                            </div>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Moderasi Produk
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm text-slate-500">
                                Tinjau produk seller sebelum tampil di katalog
                                buyer.
                            </p>
                        </div>
                    </section>

                    <section className="grid grid-cols-1 gap-4">
                        {products.length === 0 && (
                            <Card className="rounded-[8px] border border-slate-100 bg-white shadow-sm">
                                <CardContent className="p-8 text-center text-sm text-slate-500">
                                    Tidak ada produk pending.
                                </CardContent>
                            </Card>
                        )}

                        {products.map((product) => (
                            <Card
                                key={product.id}
                                className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm"
                            >
                                <CardHeader className="flex-row items-start border-b border-slate-100 p-6">
                                    <div className="min-w-0 space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge className="rounded-[6px] bg-amber-50 text-amber-700">
                                                <Clock3 className="size-3.5" />
                                                Pending
                                            </Badge>
                                            <Badge className="rounded-[6px] bg-slate-100 text-slate-700">
                                                {product.category.name}
                                            </Badge>
                                        </div>
                                        <CardTitle className="text-xl font-semibold text-slate-950">
                                            {product.name}
                                        </CardTitle>
                                        <CardDescription>
                                            {product.slug}
                                        </CardDescription>
                                    </div>
                                    <CardAction>
                                        <div className="text-right">
                                            <p className="text-base font-semibold text-slate-950">
                                                {formatRupiah(product.price)}
                                            </p>
                                            <p className="text-xs text-slate-500">
                                                Stok {product.stock}
                                            </p>
                                        </div>
                                    </CardAction>
                                </CardHeader>
                                <CardContent className="space-y-5 p-6">
                                    <p className="text-sm leading-6 text-slate-600">
                                        {product.description}
                                    </p>

                                    <div className="flex flex-wrap items-center gap-3 rounded-[8px] bg-slate-50 p-3 text-sm text-slate-600">
                                        <Store className="size-4 text-slate-400" />
                                        <span className="font-medium text-slate-800">
                                            {product.seller.name}
                                        </span>
                                        <span>{product.seller.email}</span>
                                        <span className="text-slate-400">
                                            {product.submitted_at}
                                        </span>
                                    </div>

                                    <div className="grid gap-3 lg:grid-cols-[auto_1fr]">
                                        <Form
                                            {...approveProduct.form(product.id)}
                                            disableWhileProcessing
                                        >
                                            {({ processing }) => (
                                                <Button
                                                    type="submit"
                                                    className="h-10 w-full rounded-[8px] bg-emerald-600 px-4 text-white hover:bg-emerald-700 lg:w-auto"
                                                    disabled={processing}
                                                >
                                                    {processing ? (
                                                        <Spinner />
                                                    ) : (
                                                        <CheckCircle2 className="size-4" />
                                                    )}
                                                    Approve
                                                </Button>
                                            )}
                                        </Form>

                                        <Form
                                            {...rejectProduct.form(product.id)}
                                            disableWhileProcessing
                                            className="grid gap-2 md:grid-cols-[1fr_auto]"
                                        >
                                            {({ processing, errors }) => (
                                                <>
                                                    <div>
                                                        <Textarea
                                                            name="reason"
                                                            rows={2}
                                                            placeholder="Alasan penolakan opsional"
                                                            aria-invalid={Boolean(
                                                                errors.reason,
                                                            )}
                                                            className="min-h-10"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors.reason
                                                            }
                                                        />
                                                    </div>
                                                    <Button
                                                        type="submit"
                                                        variant="outline"
                                                        className="h-10 rounded-[8px] border-rose-200 bg-white text-rose-700 hover:bg-rose-50 hover:text-rose-800"
                                                        disabled={processing}
                                                    >
                                                        {processing ? (
                                                            <Spinner />
                                                        ) : (
                                                            <XCircle className="size-4" />
                                                        )}
                                                        Reject
                                                    </Button>
                                                </>
                                            )}
                                        </Form>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </section>
                </div>
            </main>
        </>
    );
}

AdminProductModeration.layout = {
    breadcrumbs: [
        {
            title: 'Moderasi Produk',
            href: moderationIndex(),
        },
    ],
};
