import React from 'react';
import SchoolNavBar from '@/Components/Organisms/SchoolNavBar';

interface Props {
    children: React.ReactNode;
}

export default function SchoolLayout({ children }: Props) {
    return (
        <div className="min-h-screen bg-background">
            <SchoolNavBar />
            <main className="p-6">{children}</main>
        </div>
    );
}
