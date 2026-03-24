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
}

export default function ForgotPassword({ status }: Props) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/forgot-password', { preserveScroll: true });
    };

    return (
        <AuthLayout title={t('auth.forgot_password')}>
            <Card>
                <CardHeader>
                    <CardTitle>{t('auth.forgot_password')}</CardTitle>
                    <CardDescription>{t('auth.forgot_password_description')}</CardDescription>
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

                        <Button type="submit" disabled={processing} className="w-full">
                            {processing ? t('auth.sending') : t('auth.send_reset_link')}
                        </Button>

                        <div className="text-center">
                            <a href="/login" className="text-sm text-muted-foreground hover:text-foreground">
                                ← {t('auth.back_to_login')}
                            </a>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </AuthLayout>
    );
}
