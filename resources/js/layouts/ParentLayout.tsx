import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface PageProps {
    auth: {
        user: {
            name: string;
        };
    };
}

interface Props {
    children: React.ReactNode;
}

export default function ParentLayout({ children }: Props) {
    const { t } = useTranslation();
    const { auth } = usePage<PageProps>().props;

    return (
        <div className="min-h-screen bg-background">
            <header className="border-b bg-card px-4 py-3">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <div className="h-7 w-7 rounded-lg bg-primary flex items-center justify-center">
                            <span className="text-primary-foreground font-bold text-xs">IS</span>
                        </div>
                        <span className="font-semibold text-sm text-primary">{t('app.name')}</span>
                    </div>
                    <div className="flex items-center gap-3">
                        <span className="text-xs text-foreground/60">{auth.user.name}</span>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="text-xs text-foreground/60 hover:text-foreground"
                        >
                            {t('nav.logout')}
                        </Link>
                    </div>
                </div>
            </header>
            <main className="px-4 py-5 max-w-lg mx-auto">{children}</main>
        </div>
    );
}
