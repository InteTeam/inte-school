import { useForm } from '@inertiajs/react';
import { FormEvent, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import AuthLayout from '@/layouts/AuthLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

export default function DeviceRegistration() {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        device_name: '',
        device_fingerprint: '',
    });

    useEffect(() => {
        // Build a simple stable fingerprint from browser properties
        const fingerprint = [
            navigator.userAgent,
            navigator.language,
            screen.width,
            screen.height,
            new Date().getTimezoneOffset(),
        ].join('|');

        // Hash it with a simple approach (crypto.subtle would be better, but requires async)
        let hash = 0;
        for (let i = 0; i < fingerprint.length; i++) {
            hash = Math.imul(31, hash) + fingerprint.charCodeAt(i) | 0;
        }
        setData('device_fingerprint', Math.abs(hash).toString(16).padStart(8, '0'));
    }, []);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post('/device-registration');
    };

    return (
        <AuthLayout title={t('auth.register_device')}>
            <Card>
                <CardHeader>
                    <CardTitle>{t('auth.register_device')}</CardTitle>
                    <CardDescription>{t('auth.register_device_description')}</CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="device_name">{t('auth.device_name')}</Label>
                            <Input
                                id="device_name"
                                type="text"
                                value={data.device_name}
                                onChange={(e) => setData('device_name', e.target.value)}
                                placeholder={t('auth.device_name_placeholder')}
                                autoFocus
                                required
                            />
                            {errors.device_name && (
                                <p className="text-sm text-destructive">{errors.device_name}</p>
                            )}
                        </div>

                        <Button type="submit" disabled={processing} className="w-full">
                            {processing ? t('auth.registering') : t('auth.register_device')}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </AuthLayout>
    );
}
