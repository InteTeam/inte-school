import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import RootAdminLayout from '@/layouts/RootAdminLayout';

interface Stats {
    school_count: number;
    active_school_count: number;
    user_count: number;
}

interface Props {
    stats: Stats;
}

export default function Dashboard({ stats }: Props) {
    const { t } = useTranslation();

    return (
        <RootAdminLayout>
            <Head title={t('root_admin.dashboard.title')} />
            <h1 className="mb-6 text-2xl font-bold">{t('root_admin.dashboard.title')}</h1>
            <div className="grid gap-4 sm:grid-cols-3">
                <div className="rounded-lg border bg-card p-4">
                    <p className="text-sm text-foreground/60">{t('root_admin.stats.total_schools')}</p>
                    <p className="mt-1 text-3xl font-bold">{stats.school_count}</p>
                </div>
                <div className="rounded-lg border bg-card p-4">
                    <p className="text-sm text-foreground/60">{t('root_admin.stats.active_schools')}</p>
                    <p className="mt-1 text-3xl font-bold">{stats.active_school_count}</p>
                </div>
                <div className="rounded-lg border bg-card p-4">
                    <p className="text-sm text-foreground/60">{t('root_admin.stats.total_users')}</p>
                    <p className="mt-1 text-3xl font-bold">{stats.user_count}</p>
                </div>
            </div>
        </RootAdminLayout>
    );
}
