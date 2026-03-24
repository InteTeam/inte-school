import { Head, Link, router } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { useState } from 'react';

interface Student {
    id: string;
    name: string;
}

interface AttendanceRecord {
    student_id: string;
    status: string;
    notes: string | null;
    pre_notified: boolean;
}

interface SchoolClass {
    id: string;
    name: string;
    year_group: string;
    students: Student[];
}

interface Register {
    id: string;
    register_date: string;
    period: string | null;
    school_class: SchoolClass;
    records: AttendanceRecord[];
}

interface Props {
    register: Register;
}

const STATUS_COLOURS: Record<string, string> = {
    present: 'bg-green-100 text-green-800 border-green-200',
    absent: 'bg-red-100 text-red-800 border-red-200',
    late: 'bg-amber-100 text-amber-800 border-amber-200',
};

export default function AttendanceRegisterPage({ register }: Props) {
    const [processing, setProcessing] = useState(false);

    const getRecord = (studentId: string) =>
        register.records.find(r => r.student_id === studentId);

    function mark(studentId: string, status: string) {
        if (processing) return;
        setProcessing(true);
        router.post(
            route('teacher.attendance.mark'),
            { register_id: register.id, student_id: studentId, status },
            { onFinish: () => setProcessing(false) },
        );
    }

    return (
        <SchoolLayout>
            <Head title={`Register — ${register.school_class.name}`} />

            <div className="max-w-2xl">
                <div className="flex items-center gap-2 mb-6">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('teacher.attendance.index')}>← Back</Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold">{register.school_class.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {register.register_date}
                            {register.period && ` · ${register.period}`}
                        </p>
                    </div>
                </div>

                <div className="divide-y border rounded-md">
                    {register.school_class.students.map(student => {
                        const record = getRecord(student.id);
                        const status = record?.status;

                        return (
                            <div key={student.id} className="flex items-center gap-3 px-4 py-3">
                                <span className="flex-1 text-sm font-medium">{student.name}</span>

                                {status && (
                                    <Badge variant="outline" className={STATUS_COLOURS[status] ?? ''}>
                                        {status}
                                    </Badge>
                                )}

                                <div className="flex gap-1">
                                    {(['present', 'absent', 'late'] as const).map(s => (
                                        <Button
                                            key={s}
                                            size="sm"
                                            variant={status === s ? 'default' : 'outline'}
                                            disabled={processing}
                                            onClick={() => mark(student.id, s)}
                                            className="capitalize"
                                        >
                                            {s}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        );
                    })}

                    {register.school_class.students.length === 0 && (
                        <p className="px-4 py-8 text-sm text-center text-muted-foreground">
                            No students enrolled in this class.
                        </p>
                    )}
                </div>
            </div>
        </SchoolLayout>
    );
}
