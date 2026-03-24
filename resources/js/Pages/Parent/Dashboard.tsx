import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import ParentLayout from '@/layouts/ParentLayout';

export default function Dashboard() {
    const { t } = useTranslation();

    return (
        <ParentLayout>
            <Head title={t('parent.dashboard.title')} />
            <h1 className="text-2xl font-bold">{t('parent.dashboard.title')}</h1>
        </ParentLayout>
    );
}
