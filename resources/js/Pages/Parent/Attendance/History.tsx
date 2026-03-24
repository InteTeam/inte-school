import { Head, Link } from '@inertiajs/react';
import ParentLayout from '@/layouts/ParentLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

interface AttendanceRecord {
    id: string;
    status: string;
    marked_via: string;
    register_date: string;
    created_at: string;
}

interface AttendanceStats {
    total: number;
    present: number;
    absent: number;
    late: number;
    percentage: number;
}

interface Props {
    student: { id: string; name: string };
    records: { data: AttendanceRecord[] };
    stats: AttendanceStats;
}

const STATUS_COLOURS: Record<string, string> = {
    present: 'bg-green-100 text-green-800 border-green-200',
    absent: 'bg-red-100 text-red-800 border-red-200',
    late: 'bg-amber-100 text-amber-800 border-amber-200',
};

export default function AttendanceHistory({ student, records, stats }: Props) {
    return (
        <ParentLayout>
            <Head title={`Attendance — ${student.name}`} />

            <div className="px-4 pt-4 pb-2 flex items-center gap-2">
                <Button variant="ghost" size="sm" asChild className="px-0">
                    <Link href={route('parent.dashboard')}>← Back</Link>
                </Button>
            </div>

            <div className="px-4 pb-6">
                <h1 className="text-xl font-bold mb-1">{student.name}</h1>
                <p className="text-sm text-muted-foreground mb-4">Attendance history</p>

                {/* Summary stats */}
                <div className="grid grid-cols-4 gap-2 mb-6">
                    <div className="rounded-md border p-3 text-center">
                        <p className="text-2xl font-bold text-green-600">{stats.percentage}%</p>
                        <p className="text-xs text-muted-foreground">Attendance</p>
                    </div>
                    <div className="rounded-md border p-3 text-center">
                        <p className="text-2xl font-bold">{stats.present}</p>
                        <p className="text-xs text-muted-foreground">Present</p>
                    </div>
                    <div className="rounded-md border p-3 text-center">
                        <p className="text-2xl font-bold text-red-600">{stats.absent}</p>
                        <p className="text-xs text-muted-foreground">Absent</p>
                    </div>
                    <div className="rounded-md border p-3 text-center">
                        <p className="text-2xl font-bold text-amber-600">{stats.late}</p>
                        <p className="text-xs text-muted-foreground">Late</p>
                    </div>
                </div>

                {/* Record log — ordered by created_at desc */}
                <div className="divide-y border rounded-md">
                    {records.data.map(record => (
                        <div key={record.id} className="flex items-center gap-3 px-4 py-3">
                            <div className="flex-1">
                                <p className="text-sm font-medium">{record.register_date}</p>
                                <p className="text-xs text-muted-foreground capitalize">{record.marked_via}</p>
                            </div>
                            <Badge variant="outline" className={STATUS_COLOURS[record.status] ?? ''}>
                                {record.status}
                            </Badge>
                        </div>
                    ))}

                    {records.data.length === 0 && (
                        <p className="px-4 py-8 text-sm text-center text-muted-foreground">
                            No attendance records yet.
                        </p>
                    )}
                </div>
            </div>
        </ParentLayout>
    );
}
