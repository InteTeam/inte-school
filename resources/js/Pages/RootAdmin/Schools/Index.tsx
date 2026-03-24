import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import RootAdminLayout from '@/layouts/RootAdminLayout';
import { Badge } from '@/Components/ui/badge';

interface School {
    id: string;
    name: string;
    slug: string;
    plan: string;
    is_active: boolean;
    rag_enabled: boolean;
    created_at: string;
    deleted_at: string | null;
}

interface Props {
    schools: School[];
}

export default function SchoolsIndex({ schools }: Props) {
    const { t } = useTranslation();

    return (
        <RootAdminLayout>
            <Head title={t('root_admin.schools.title')} />
            <h1 className="mb-6 text-2xl font-bold">{t('root_admin.schools.title')}</h1>
            <div className="rounded-lg border bg-card">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b">
                            <th className="px-4 py-3 text-left font-medium">{t('root_admin.schools.name')}</th>
                            <th className="px-4 py-3 text-left font-medium">{t('root_admin.schools.slug')}</th>
                            <th className="px-4 py-3 text-left font-medium">{t('root_admin.schools.plan')}</th>
                            <th className="px-4 py-3 text-left font-medium">{t('root_admin.schools.status')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {schools.map((school) => (
                            <tr key={school.id} className="border-b last:border-0">
                                <td className="px-4 py-3 font-medium">{school.name}</td>
                                <td className="px-4 py-3 text-foreground/60">{school.slug}</td>
                                <td className="px-4 py-3">{school.plan}</td>
                                <td className="px-4 py-3">
                                    {school.deleted_at ? (
                                        <Badge variant="destructive">{t('common.deleted')}</Badge>
                                    ) : school.is_active ? (
                                        <Badge variant="default">{t('common.active')}</Badge>
                                    ) : (
                                        <Badge variant="secondary">{t('common.inactive')}</Badge>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {schools.length === 0 && (
                            <tr>
                                <td colSpan={4} className="px-4 py-8 text-center text-foreground/40">
                                    {t('root_admin.schools.empty')}
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </RootAdminLayout>
    );
}
