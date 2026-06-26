import { Form, Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CircleDollarSign,
    ImagePlus,
    PackageCheck,
    Save,
    Tags,
} from 'lucide-react';
import { useState } from 'react';
import type { CSSProperties } from 'react';
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
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import {
    index as sellerProductsIndex,
    update as sellerProductsUpdate,
} from '@/routes/seller/products';

type ProductStatus = 'draft' | 'pending' | 'approved' | 'rejected';

type CategoryOption = {
    id: number;
    name: string;
    slug: string;
};

type SellerProduct = {
    id: number;
    name: string;
    slug: string;
    category_id: number;
    description: string;
    price: number;
    stock: number;
    image: string | null;
    status: {
        code: ProductStatus;
        label: string;
    };
};

type SellerProductEditProps = {
    categories: CategoryOption[];
    product: SellerProduct;
};

type SelectTheme = CSSProperties & Record<`--${string}`, string>;

const selectPortalTheme: SelectTheme = {
    '--foreground': '#0F172A',
    '--popover': '#FFFFFF',
    '--popover-foreground': '#0F172A',
    '--muted-foreground': '#64748B',
    '--accent': '#EFF6FF',
    '--accent-foreground': '#1D4ED8',
    '--border': '#E2E8F0',
};

const fieldClassName = 'grid gap-2';
const labelClassName = 'text-sm font-medium text-slate-700';
const inputClassName =
    'h-10 rounded-[8px] border-slate-200 bg-white text-slate-950 shadow-none placeholder:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-blue-100';
const selectTriggerClassName =
    'h-10 w-full rounded-[8px] border-slate-200 bg-white text-slate-950 shadow-none data-[placeholder]:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-blue-100 data-[size=default]:h-10';

const statusStyles: Record<ProductStatus, string> = {
    draft: 'bg-slate-100 text-slate-700',
    pending: 'bg-amber-50 text-amber-700',
    approved: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-rose-50 text-rose-700',
};

export default function SellerProductEdit({
    categories,
    product,
}: SellerProductEditProps) {
    const [categoryId, setCategoryId] = useState(String(product.category_id));

    return (
        <>
            <Head title={`Edit ${product.name}`} />
            <main className="min-h-[calc(100svh-4rem)] bg-slate-50 p-4 sm:p-6">
                <div className="mx-auto max-w-4xl space-y-6">
                    <section className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div>
                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                <Badge className="rounded-[6px] bg-blue-50 text-blue-700">
                                    <PackageCheck className="size-3.5" />
                                    Seller Center
                                </Badge>
                                <Badge
                                    variant="secondary"
                                    className={cn(
                                        'rounded-[6px]',
                                        statusStyles[product.status.code],
                                    )}
                                >
                                    {product.status.label}
                                </Badge>
                            </div>
                            <h1 className="text-2xl font-semibold text-slate-950">
                                Edit Produk
                            </h1>
                            <p className="mt-1 max-w-2xl text-sm text-slate-500">
                                Perbarui informasi produk toko.
                            </p>
                        </div>
                        <Button
                            asChild
                            variant="outline"
                            className="h-9 rounded-[8px] border-slate-200 bg-white"
                        >
                            <Link href={sellerProductsIndex()}>
                                <ArrowLeft className="size-4" />
                                Kembali
                            </Link>
                        </Button>
                    </section>

                    <Card className="gap-0 rounded-[8px] border border-slate-100 bg-white py-0 shadow-sm">
                        <CardHeader className="flex-row items-center border-b border-slate-100 p-6">
                            <div className="space-y-1">
                                <CardTitle className="text-xl font-semibold text-slate-950">
                                    Detail Produk
                                </CardTitle>
                                <CardDescription>
                                    {product.slug}
                                </CardDescription>
                            </div>
                            <CardAction>
                                <div className="flex size-10 items-center justify-center rounded-[8px] bg-slate-100 text-slate-600">
                                    <Tags className="size-5" />
                                </div>
                            </CardAction>
                        </CardHeader>
                        <CardContent className="p-6">
                            <Form
                                {...sellerProductsUpdate.form(product.id)}
                                disableWhileProcessing
                                className="space-y-6"
                            >
                                {({ processing, errors }) => (
                                    <>
                                        <div className="grid gap-5 md:grid-cols-2">
                                            <div className={fieldClassName}>
                                                <Label
                                                    htmlFor="name"
                                                    className={labelClassName}
                                                >
                                                    Nama Produk
                                                </Label>
                                                <Input
                                                    id="name"
                                                    name="name"
                                                    required
                                                    autoFocus
                                                    maxLength={120}
                                                    defaultValue={product.name}
                                                    className={inputClassName}
                                                    aria-invalid={Boolean(
                                                        errors.name,
                                                    )}
                                                />
                                                <InputError
                                                    message={errors.name}
                                                />
                                            </div>

                                            <div className={fieldClassName}>
                                                <Label
                                                    htmlFor="category_id"
                                                    className={labelClassName}
                                                >
                                                    Kategori
                                                </Label>
                                                <Select
                                                    name="category_id"
                                                    value={categoryId}
                                                    onValueChange={
                                                        setCategoryId
                                                    }
                                                    required
                                                >
                                                    <SelectTrigger
                                                        id="category_id"
                                                        className={
                                                            selectTriggerClassName
                                                        }
                                                        aria-invalid={Boolean(
                                                            errors.category_id,
                                                        )}
                                                    >
                                                        <SelectValue placeholder="Pilih kategori" />
                                                    </SelectTrigger>
                                                    <SelectContent
                                                        style={
                                                            selectPortalTheme
                                                        }
                                                    >
                                                        <SelectGroup>
                                                            <SelectLabel>
                                                                Kategori
                                                            </SelectLabel>
                                                            {categories.map(
                                                                (category) => (
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
                                                    message={errors.category_id}
                                                />
                                            </div>
                                        </div>

                                        <div className={fieldClassName}>
                                            <Label
                                                htmlFor="description"
                                                className={labelClassName}
                                            >
                                                Deskripsi
                                            </Label>
                                            <Textarea
                                                id="description"
                                                name="description"
                                                required
                                                minLength={10}
                                                maxLength={5000}
                                                defaultValue={
                                                    product.description
                                                }
                                                aria-invalid={Boolean(
                                                    errors.description,
                                                )}
                                            />
                                            <InputError
                                                message={errors.description}
                                            />
                                        </div>

                                        <div className={fieldClassName}>
                                            <Label
                                                htmlFor="price"
                                                className={labelClassName}
                                            >
                                                Harga
                                            </Label>
                                            <div className="relative">
                                                <CircleDollarSign className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                                <Input
                                                    id="price"
                                                    name="price"
                                                    type="number"
                                                    required
                                                    min={1}
                                                    max={100000000}
                                                    step={1}
                                                    inputMode="numeric"
                                                    defaultValue={
                                                        product.price
                                                    }
                                                    className={`${inputClassName} pl-9`}
                                                    aria-invalid={Boolean(
                                                        errors.price,
                                                    )}
                                                />
                                            </div>
                                            <InputError
                                                message={errors.price}
                                            />
                                        </div>

                                        <div className={fieldClassName}>
                                            <Label
                                                htmlFor="image"
                                                className={labelClassName}
                                            >
                                                Gambar Produk
                                            </Label>
                                            <div className="relative">
                                                <ImagePlus className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                                                <Input
                                                    id="image"
                                                    name="image"
                                                    type="file"
                                                    accept="image/jpeg,image/png,image/webp"
                                                    className={`${inputClassName} h-11 pl-9 file:mr-3 file:rounded-[6px] file:bg-slate-100 file:px-2 file:text-slate-700`}
                                                    aria-invalid={Boolean(
                                                        errors.image,
                                                    )}
                                                />
                                            </div>
                                            {product.image && (
                                                <p className="text-xs text-slate-500">
                                                    Gambar saat ini:{' '}
                                                    {product.image}
                                                </p>
                                            )}
                                            <InputError
                                                message={errors.image}
                                            />
                                        </div>

                                        <div className="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:justify-end">
                                            <Button
                                                asChild
                                                type="button"
                                                variant="outline"
                                                className="h-10 rounded-[8px] border-slate-200 bg-white"
                                            >
                                                <Link
                                                    href={sellerProductsIndex()}
                                                >
                                                    Batal
                                                </Link>
                                            </Button>
                                            <Button
                                                type="submit"
                                                className="h-10 rounded-[8px] bg-blue-600 px-4 text-white hover:bg-blue-700"
                                                disabled={processing}
                                            >
                                                {processing && <Spinner />}
                                                <Save className="size-4" />
                                                Simpan Perubahan
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}

SellerProductEdit.layout = {
    breadcrumbs: [
        {
            title: 'Produk Seller',
            href: sellerProductsIndex(),
        },
        {
            title: 'Edit Produk',
            href: sellerProductsIndex(),
        },
    ],
};
