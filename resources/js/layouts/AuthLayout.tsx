import React from 'react';
import { Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
}

export default function AuthLayout({ children, title }: AuthLayoutProps) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={title} />
            <div className="min-h-screen flex items-center justify-center bg-muted py-12 px-4 sm:px-6 lg:px-8">
                <div className="w-full max-w-md space-y-8">
                    {/* Logo */}
                    <div className="flex justify-center">
                        <div className="flex items-center gap-3">
                            <div className="h-10 w-10 bg-primary rounded-xl flex items-center justify-center">
                                <span className="text-primary-foreground font-bold text-sm">IS</span>
                            </div>
                            <span className="text-xl font-semibold text-foreground">
                                {t('app.name')}
                            </span>
                        </div>
                    </div>

                    {children}
                </div>
            </div>
        </>
    );
}
