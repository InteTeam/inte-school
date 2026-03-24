import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/layouts/AuthLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

interface Props {
    status?: string;
    errors?: Record<string, string>;
}

export default function Login({ status }: Props) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/login', { preserveScroll: true });
    };

    return (
        <AuthLayout title={t('auth.sign_in')}>
            <Card>
                <CardHeader>
                    <CardTitle>{t('auth.welcome_back')}</CardTitle>
                    <CardDescription>{t('auth.sign_in_description')}</CardDescription>
                </CardHeader>
                <CardContent>
                    {status && (
                        <p className="mb-4 text-sm text-green-600">{status}</p>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="email">{t('auth.email')}</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                autoComplete="email"
                                autoFocus
                                required
                            />
                            {errors.email && (
                                <p className="text-sm text-destructive">{errors.email}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password">{t('auth.password')}</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                autoComplete="current-password"
                                required
                            />
                            {errors.password && (
                                <p className="text-sm text-destructive">{errors.password}</p>
                            )}
                        </div>

                        <div className="flex items-center justify-between">
                            <label className="flex items-center gap-2 text-sm cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="rounded"
                                />
                                {t('auth.remember_me')}
                            </label>
                            <a href="/forgot-password" className="text-sm text-primary hover:underline">
                                {t('auth.forgot_password')}
                            </a>
                        </div>

                        <Button type="submit" disabled={processing} className="w-full">
                            {processing ? t('auth.signing_in') : t('auth.sign_in')}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </AuthLayout>
    );
}
