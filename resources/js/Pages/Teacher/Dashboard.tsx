import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import SchoolLayout from '@/layouts/SchoolLayout';

export default function Dashboard() {
    const { t } = useTranslation();

    return (
        <SchoolLayout>
            <Head title={t('teacher.dashboard.title')} />
            <h1 className="text-2xl font-bold">{t('teacher.dashboard.title')}</h1>
        </SchoolLayout>
    );
}
