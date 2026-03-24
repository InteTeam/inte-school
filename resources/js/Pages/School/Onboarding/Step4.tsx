import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import WizardShell from '@/Components/Molecules/WizardShell';
import { Button } from '@/Components/ui/button';

const STEPS = [
    { number: 1, label: 'School details' },
    { number: 2, label: 'Logo & theme' },
    { number: 3, label: 'Legal documents' },
    { number: 4, label: 'Admin account' },
];

export default function Step4() {
    const { t } = useTranslation();
    const { post, processing } = useForm({});

    return (
        <WizardShell steps={STEPS} currentStep={4} title={t('onboarding.title')}>
            <Head title={t('onboarding.step4.title')} />
            <h2 className="mb-4 text-lg font-semibold">{t('onboarding.step4.heading')}</h2>
            <p className="mb-6 text-sm text-foreground/70">{t('onboarding.step4.description')}</p>
            <form onSubmit={(e) => { e.preventDefault(); post('/onboarding/complete'); }}>
                <div className="flex gap-3">
                    <Button type="button" variant="outline" onClick={() => history.back()}>
                        {t('common.back')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? t('onboarding.creating') : t('onboarding.create_school')}
                    </Button>
                </div>
            </form>
        </WizardShell>
    );
}
