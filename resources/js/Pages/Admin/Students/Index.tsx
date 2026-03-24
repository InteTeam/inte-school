import { Head, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

interface Student {
    id: string;
    name: string;
    email: string;
    accepted_at: string | null;
}

interface Props {
    students: Student[];
}

export default function StudentsIndex({ students }: Props) {
    return (
        <SchoolLayout>
            <Head title="Students" />

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold">Students</h1>
                <div className="flex gap-2">
                    <Button variant="outline" asChild>
                        <Link href={route('admin.students.export-template')}>
                            Download Template
                        </Link>
                    </Button>
                    <Button asChild>
                        <Link href={route('admin.students.import')}>
                            Bulk Import
                        </Link>
                    </Button>
                </div>
            </div>

            <div className="rounded-md border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b bg-muted/50">
                            <th className="px-4 py-3 text-left font-medium">Name</th>
                            <th className="px-4 py-3 text-left font-medium">Email</th>
                            <th className="px-4 py-3 text-left font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {students.map(student => (
                            <tr key={student.id} className="border-b last:border-0">
                                <td className="px-4 py-3">{student.name}</td>
                                <td className="px-4 py-3 text-muted-foreground">{student.email}</td>
                                <td className="px-4 py-3">
                                    {student.accepted_at ? (
                                        <Badge variant="default">Active</Badge>
                                    ) : (
                                        <Badge variant="secondary">Pending</Badge>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {students.length === 0 && (
                            <tr>
                                <td colSpan={3} className="px-4 py-8 text-center text-muted-foreground">
                                    No students yet. Enrol your first student or use bulk import.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </SchoolLayout>
    );
}
