import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface PageProps {
    auth: {
        user: {
            name: string;
            role: string;
        };
    };
}

export default function SchoolNavBar() {
    const { t } = useTranslation();
    const { auth } = usePage<PageProps>().props;

    return (
        <nav className="border-b bg-card px-6 py-3">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-6">
                    <div className="flex items-center gap-2">
                        <div className="h-8 w-8 rounded-lg bg-primary flex items-center justify-center">
                            <span className="text-primary-foreground font-bold text-xs">IS</span>
                        </div>
                        <span className="font-bold text-primary">{t('app.name')}</span>
                    </div>
                    <Link
                        href="/dashboard"
                        className="text-sm text-foreground/70 hover:text-foreground"
                    >
                        {t('nav.dashboard')}
                    </Link>
                </div>
                <div className="flex items-center gap-3">
                    <span className="text-sm text-foreground/60">{auth.user.name}</span>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        className="text-sm text-foreground/60 hover:text-foreground"
                    >
                        {t('nav.logout')}
                    </Link>
                </div>
            </div>
        </nav>
    );
}
