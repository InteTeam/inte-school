import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Shield, Key } from 'lucide-react';
import AuthLayout from '@/layouts/AuthLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

export default function TwoFactor() {
    const { t } = useTranslation();
    const [useRecoveryCode, setUseRecoveryCode] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        code: '',
        recovery_code: '',
        remember_device: false,
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/two-factor-challenge', { preserveScroll: true });
    };

    return (
        <AuthLayout title={t('auth.two_factor')}>
            <Card>
                <CardHeader>
                    <CardTitle>{t('auth.two_factor')}</CardTitle>
                    <CardDescription>
                        {useRecoveryCode ? t('auth.enter_recovery_code') : t('auth.enter_auth_code')}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        {(errors.code || errors.recovery_code) && (
                            <p className="text-sm text-destructive">
                                {errors.code ?? errors.recovery_code}
                            </p>
                        )}

                        {!useRecoveryCode ? (
                            <div className="space-y-2">
                                <Label htmlFor="code" className="flex items-center gap-2">
                                    <Shield className="h-4 w-4" />
                                    {t('auth.auth_code')}
                                </Label>
                                <Input
                                    id="code"
                                    type="text"
                                    inputMode="numeric"
                                    pattern="[0-9]*"
                                    maxLength={6}
                                    value={data.code}
                                    onChange={(e) => setData('code', e.target.value.replace(/\D/g, ''))}
                                    placeholder="000000"
                                    className="text-center text-2xl tracking-widest font-mono"
                                    autoFocus
                                    autoComplete="one-time-code"
                                />
                            </div>
                        ) : (
                            <div className="space-y-2">
                                <Label htmlFor="recovery_code" className="flex items-center gap-2">
                                    <Key className="h-4 w-4" />
                                    {t('auth.recovery_code')}
                                </Label>
                                <Input
                                    id="recovery_code"
                                    type="text"
                                    value={data.recovery_code}
                                    onChange={(e) => setData('recovery_code', e.target.value.toUpperCase())}
                                    placeholder="XXXX-XXXX"
                                    className="text-center text-xl tracking-widest font-mono uppercase"
                                    autoFocus
                                />
                            </div>
                        )}

                        <label className="flex items-center gap-2 text-sm cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.remember_device}
                                onChange={(e) => setData('remember_device', e.target.checked)}
                                className="rounded"
                            />
                            {t('auth.remember_device')}
                        </label>

                        <Button
                            type="submit"
                            disabled={processing || (!useRecoveryCode && data.code.length !== 6)}
                            className="w-full"
                        >
                            {processing ? t('auth.verifying') : t('auth.verify')}
                        </Button>

                        <div className="text-center space-y-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setUseRecoveryCode(!useRecoveryCode);
                                    setData('code', '');
                                    setData('recovery_code', '');
                                }}
                                className="text-sm text-primary hover:underline"
                            >
                                {useRecoveryCode ? t('auth.use_auth_app') : t('auth.use_recovery_code')}
                            </button>
                            <br />
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
