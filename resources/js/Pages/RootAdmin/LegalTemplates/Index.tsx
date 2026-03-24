import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import RootAdminLayout from '@/layouts/RootAdminLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

interface Template {
    id: string;
    type: string;
    name: string;
    is_active: boolean;
    updated_at: string;
}

interface Props {
    templates: Template[];
}

export default function LegalTemplatesIndex({ templates }: Props) {
    const { t } = useTranslation();

    return (
        <RootAdminLayout>
            <Head title={t('root_admin.legal_templates.title')} />
            <h1 className="mb-6 text-2xl font-bold">{t('root_admin.legal_templates.title')}</h1>
            <div className="rounded-lg border bg-card">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b">
                            <th className="px-4 py-3 text-left font-medium">{t('legal.document_name')}</th>
                            <th className="px-4 py-3 text-left font-medium">{t('legal.type')}</th>
                            <th className="px-4 py-3 text-left font-medium">{t('common.status')}</th>
                            <th className="px-4 py-3 text-left font-medium">{t('common.actions')}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {templates.map((template) => (
                            <tr key={template.id} className="border-b last:border-0">
                                <td className="px-4 py-3 font-medium">{template.name}</td>
                                <td className="px-4 py-3">{t(`legal.types.${template.type}`)}</td>
                                <td className="px-4 py-3">
                                    {template.is_active ? (
                                        <Badge variant="default">{t('common.active')}</Badge>
                                    ) : (
                                        <Badge variant="secondary">{t('common.inactive')}</Badge>
                                    )}
                                </td>
                                <td className="px-4 py-3">
                                    <Link href={`/root-admin/legal-templates/${template.id}/edit`}>
                                        <Button variant="ghost" size="sm">{t('common.edit')}</Button>
                                    </Link>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </RootAdminLayout>
    );
}
