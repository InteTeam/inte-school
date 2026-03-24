import { Head, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';

interface Student {
    id: string;
    name: string;
    email: string;
}

interface SchoolClassDetail {
    id: string;
    name: string;
    year_group: string;
    teacher: { id: string; name: string } | null;
    students: Student[];
}

interface Props {
    class: SchoolClassDetail;
}

export default function ClassShow({ class: schoolClass }: Props) {
    return (
        <SchoolLayout>
            <Head title={schoolClass.name} />

            <div className="flex items-center gap-2 mb-6">
                <Button variant="ghost" size="sm" asChild>
                    <Link href={route('admin.classes.index')}>← Classes</Link>
                </Button>
                <h1 className="text-2xl font-bold">{schoolClass.name}</h1>
                <span className="text-muted-foreground text-sm">· {schoolClass.year_group}</span>
            </div>

            <div className="mb-4 text-sm text-muted-foreground">
                Teacher: <span className="text-foreground">{schoolClass.teacher?.name ?? 'Not assigned'}</span>
            </div>

            <div className="rounded-md border">
                <div className="px-4 py-3 border-b bg-muted/50 font-medium text-sm flex items-center justify-between">
                    <span>Students ({schoolClass.students.length})</span>
                </div>
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b bg-muted/30">
                            <th className="px-4 py-2 text-left font-medium">Name</th>
                            <th className="px-4 py-2 text-left font-medium">Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        {schoolClass.students.map(s => (
                            <tr key={s.id} className="border-b last:border-0">
                                <td className="px-4 py-3">{s.name}</td>
                                <td className="px-4 py-3 text-muted-foreground">{s.email}</td>
                            </tr>
                        ))}
                        {schoolClass.students.length === 0 && (
                            <tr>
                                <td colSpan={2} className="px-4 py-8 text-center text-muted-foreground">
                                    No students enrolled in this class.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </SchoolLayout>
    );
}
