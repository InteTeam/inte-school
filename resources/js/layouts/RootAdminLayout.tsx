import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface Props {
    children: React.ReactNode;
}

export default function RootAdminLayout({ children }: Props) {
    const { t } = useTranslation();
    const { auth } = usePage<{ auth: { user: { name: string } } }>().props;

    return (
        <div className="min-h-screen bg-background">
            <nav className="border-b bg-card px-6 py-3">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-6">
                        <span className="font-bold text-primary">{t('app.name')} Root</span>
                        <Link
                            href="/root-admin"
                            className="text-sm text-foreground/70 hover:text-foreground"
                        >
                            {t('root_admin.nav.dashboard')}
                        </Link>
                        <Link
                            href="/root-admin/schools"
                            className="text-sm text-foreground/70 hover:text-foreground"
                        >
                            {t('root_admin.nav.schools')}
                        </Link>
                    </div>
                    <span className="text-sm text-foreground/60">{auth.user.name}</span>
                </div>
            </nav>
            <main className="p-6">{children}</main>
        </div>
    );
}
