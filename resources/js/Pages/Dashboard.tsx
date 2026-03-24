import { Head } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="min-h-screen flex items-center justify-center bg-background">
                <p className="text-muted-foreground">Dashboard — implemented in P1.5</p>
            </div>
        </>
    );
}
