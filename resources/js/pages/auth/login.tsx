import { Form, Head, Link } from '@inertiajs/react';
import { ArrowRight, Lock, Mail } from 'lucide-react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

const fieldClassName = 'flex flex-col gap-1';
const labelClassName = 'text-xs leading-[1.4] font-medium text-[#334155]';
const iconClassName =
    'pointer-events-none absolute left-3 top-1/2 z-10 size-5 -translate-y-1/2 text-[#94A3B8]';
const inputClassName =
    'h-11 rounded-lg border-[#E2E8F0] bg-white pl-10 pr-4 text-base text-[#0F172A] shadow-none placeholder:text-[#94A3B8] focus-visible:border-[#0080FF] focus-visible:ring-2 focus-visible:ring-[#BCE0FF] md:text-base';
const errorClassName = 'pt-1 text-xs';

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Masuk" />

            <PasskeyVerify />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        {status && (
                            <div className="rounded-lg bg-[#ECFDF3] px-3 py-2 text-center text-sm font-medium text-[#16A34A]">
                                {status}
                            </div>
                        )}

                        <div className="flex flex-col gap-4">
                            <div className={fieldClassName}>
                                <Label
                                    htmlFor="email"
                                    className={labelClassName}
                                >
                                    Email
                                </Label>
                                <div className="relative">
                                    <Mail className={iconClassName} />
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        placeholder="contoh@email.com"
                                        className={inputClassName}
                                        aria-invalid={Boolean(errors.email)}
                                    />
                                </div>
                                <InputError
                                    message={errors.email}
                                    className={errorClassName}
                                />
                            </div>

                            <div className={fieldClassName}>
                                <div className="flex items-center gap-3">
                                    <Label
                                        htmlFor="password"
                                        className={labelClassName}
                                    >
                                        Kata Sandi
                                    </Label>
                                    {canResetPassword && (
                                        <Link
                                            href={request()}
                                            className="ml-auto text-xs font-semibold text-[#006FE0] transition-colors hover:text-[#0059B8]"
                                            tabIndex={5}
                                        >
                                            Lupa kata sandi?
                                        </Link>
                                    )}
                                </div>
                                <div className="relative">
                                    <Lock className={iconClassName} />
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        required
                                        tabIndex={2}
                                        autoComplete="current-password"
                                        placeholder="Masukkan kata sandi"
                                        className={inputClassName}
                                        aria-invalid={Boolean(errors.password)}
                                    />
                                </div>
                                <InputError
                                    message={errors.password}
                                    className={errorClassName}
                                />
                            </div>

                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                    className="border-[#E2E8F0] bg-white data-checked:border-[#0080FF] data-checked:bg-[#0080FF] data-checked:text-white"
                                />
                                <Label
                                    htmlFor="remember"
                                    className="text-sm font-medium text-[#475569]"
                                >
                                    Ingat saya
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 h-11 w-full rounded-lg bg-[#0080FF] text-base font-semibold text-white shadow-sm transition-colors hover:bg-[#006FE0] active:scale-[0.98]"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Masuk Sekarang
                                <ArrowRight
                                    className="size-4"
                                    data-icon="inline-end"
                                />
                            </Button>
                        </div>

                        <div className="mt-2 text-center text-sm leading-6 text-[#475569]">
                            Belum punya akun?{' '}
                            <Link
                                href={register()}
                                tabIndex={6}
                                className="font-semibold text-[#006FE0] transition-colors hover:text-[#0059B8]"
                            >
                                Daftar di sini
                            </Link>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    title: 'Masuk ke EduCart',
    description: 'Masuk untuk mulai jual beli di lingkungan sekolah.',
};
