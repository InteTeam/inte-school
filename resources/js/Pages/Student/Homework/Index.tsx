import { Head } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Badge } from '@/Components/ui/badge';

interface HomeworkTask {
    id: string;
    title: string;
    description: string | null;
    status: string;
    due_at: string | null;
    assigned_by: { name: string } | null;
}

interface Props {
    homework: { data: HomeworkTask[] };
}

const STATUS_COLOURS: Record<string, string> = {
    todo: 'bg-slate-100 text-slate-700 border-slate-200',
    in_progress: 'bg-blue-100 text-blue-700 border-blue-200',
    done: 'bg-green-100 text-green-700 border-green-200',
    cancelled: 'bg-red-100 text-red-700 border-red-200',
};

export default function StudentHomeworkIndex({ homework }: Props) {
    // NOTE: Ordered by due_at ASC (upcoming homework first) — documented exception to default desc.
    return (
        <SchoolLayout>
            <Head title="Homework" />

            <div className="max-w-2xl">
                <h1 className="text-2xl font-bold mb-6">Homework</h1>

                <div className="space-y-3">
                    {homework.data.map(task => (
                        <div key={task.id} className="border rounded-lg px-4 py-3">
                            <div className="flex items-center gap-2 mb-1">
                                <span className="font-medium text-sm">{task.title}</span>
                                <Badge variant="outline" className={`text-xs ${STATUS_COLOURS[task.status] ?? ''}`}>
                                    {task.status}
                                </Badge>
                            </div>
                            {task.description && (
                                <p className="text-sm text-muted-foreground mb-1">{task.description}</p>
                            )}
                            <div className="flex items-center gap-3 text-xs text-muted-foreground">
                                {task.assigned_by && <span>Set by {task.assigned_by.name}</span>}
                                {task.due_at && (
                                    <span className="font-medium text-foreground">
                                        Due {new Date(task.due_at).toLocaleDateString()}
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}

                    {homework.data.length === 0 && (
                        <p className="text-sm text-muted-foreground text-center py-12">
                            No homework assigned yet.
                        </p>
                    )}
                </div>
            </div>
        </SchoolLayout>
    );
}
