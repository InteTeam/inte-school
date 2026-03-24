import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import WizardShell from '@/Components/Molecules/WizardShell';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

const STEPS = [
    { number: 1, label: 'School details' },
    { number: 2, label: 'Logo & theme' },
    { number: 3, label: 'Legal documents' },
    { number: 4, label: 'Admin account' },
];

export default function Step2() {
    const { t } = useTranslation();
    const { data, setData, post, errors, processing } = useForm({
        logo: null as File | null,
        theme_primary_color: '',
    });

    return (
        <WizardShell steps={STEPS} currentStep={2} title={t('onboarding.title')}>
            <Head title={t('onboarding.step2.title')} />
            <h2 className="mb-4 text-lg font-semibold">{t('onboarding.step2.heading')}</h2>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    post('/onboarding/step-2');
                }}
                encType="multipart/form-data"
            >
                <div className="flex flex-col gap-4">
                    <div>
                        <Label htmlFor="logo">{t('onboarding.step2.logo')}</Label>
                        <Input
                            id="logo"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(e) => setData('logo', e.target.files?.[0] ?? null)}
                            className="mt-1"
                        />
                        {errors.logo && <p className="mt-1 text-sm text-destructive">{errors.logo}</p>}
                    </div>
                    <div>
                        <Label htmlFor="theme_primary_color">{t('onboarding.step2.primary_color')}</Label>
                        <div className="mt-1 flex items-center gap-2">
                            <input
                                type="color"
                                id="theme_primary_color"
                                value={data.theme_primary_color || '#1e3a5f'}
                                onChange={(e) => setData('theme_primary_color', e.target.value)}
                                className="h-10 w-16 cursor-pointer rounded border"
                            />
                            <Input
                                value={data.theme_primary_color}
                                onChange={(e) => setData('theme_primary_color', e.target.value)}
                                placeholder="#1e3a5f"
                                className="flex-1"
                            />
                        </div>
                    </div>
                    <div className="mt-2 flex gap-3">
                        <Button type="button" variant="outline" onClick={() => history.back()}>
                            {t('common.back')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {t('common.next')}
                        </Button>
                    </div>
                </div>
            </form>
        </WizardShell>
    );
}
