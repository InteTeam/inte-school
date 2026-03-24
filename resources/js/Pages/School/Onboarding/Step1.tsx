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

export default function Step1() {
    const { t } = useTranslation();
    const { data, setData, post, errors, processing } = useForm({
        name: '',
        slug: '',
    });

    const handleNameChange = (value: string) => {
        setData('name', value);
        if (!data.slug) {
            setData('slug', value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, ''));
        }
    };

    return (
        <WizardShell steps={STEPS} currentStep={1} title={t('onboarding.title')}>
            <Head title={t('onboarding.step1.title')} />
            <h2 className="mb-4 text-lg font-semibold">{t('onboarding.step1.heading')}</h2>
            <form onSubmit={(e) => { e.preventDefault(); post('/onboarding/step-1'); }}>
                <div className="flex flex-col gap-4">
                    <div>
                        <Label htmlFor="name">{t('onboarding.step1.school_name')}</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => handleNameChange(e.target.value)}
                            placeholder={t('onboarding.step1.school_name_placeholder')}
                            className="mt-1"
                        />
                        {errors.name && <p className="mt-1 text-sm text-destructive">{errors.name}</p>}
                    </div>
                    <div>
                        <Label htmlFor="slug">{t('onboarding.step1.slug')}</Label>
                        <Input
                            id="slug"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                            placeholder="my-school"
                            className="mt-1"
                        />
                        {errors.slug && <p className="mt-1 text-sm text-destructive">{errors.slug}</p>}
                    </div>
                    <Button type="submit" disabled={processing} className="mt-2">
                        {t('common.next')}
                    </Button>
                </div>
            </form>
        </WizardShell>
    );
}
