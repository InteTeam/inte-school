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

export default function Step3() {
    const { t } = useTranslation();
    const { post, processing } = useForm({});

    return (
        <WizardShell steps={STEPS} currentStep={3} title={t('onboarding.title')}>
            <Head title={t('onboarding.step3.title')} />
            <h2 className="mb-4 text-lg font-semibold">{t('onboarding.step3.heading')}</h2>
            <p className="mb-6 text-sm text-foreground/70">{t('onboarding.step3.description')}</p>
            <div className="mb-6 rounded-lg border bg-muted/30 p-4">
                <p className="text-sm font-medium">{t('onboarding.step3.pre_filled_note')}</p>
                <ul className="mt-2 list-inside list-disc text-sm text-foreground/70">
                    <li>{t('onboarding.step3.privacy_policy')}</li>
                    <li>{t('onboarding.step3.terms_conditions')}</li>
                </ul>
                <p className="mt-3 text-sm">{t('onboarding.step3.edit_after_note')}</p>
            </div>
            <form onSubmit={(e) => { e.preventDefault(); post('/onboarding/step-3'); }}>
                <div className="flex gap-3">
                    <Button type="button" variant="outline" onClick={() => history.back()}>
                        {t('common.back')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {t('common.next')}
                    </Button>
                </div>
            </form>
        </WizardShell>
    );
}
