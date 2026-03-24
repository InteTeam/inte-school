import { Head } from '@inertiajs/react';
import ParentLayout from '@/layouts/ParentLayout';
import { Badge } from '@/Components/ui/badge';

interface HomeworkTask {
    id: string;
    title: string;
    description: string | null;
    status: string;
    due_at: string | null;
    created_at: string;
}

interface Props {
    homework: { data: HomeworkTask[] };
    student: { id: string; name: string };
}

const STATUS_COLOURS: Record<string, string> = {
    todo: 'bg-slate-100 text-slate-700 border-slate-200',
    in_progress: 'bg-blue-100 text-blue-700 border-blue-200',
    done: 'bg-green-100 text-green-700 border-green-200',
    cancelled: 'bg-red-100 text-red-700 border-red-200',
};

export default function ParentHomeworkIndex({ homework, student }: Props) {
    // NOTE: Ordered by created_at DESC (most recently assigned first).
    return (
        <ParentLayout>
            <Head title={`Homework — ${student.name}`} />

            <div className="px-4 pt-4 pb-2">
                <h1 className="text-xl font-bold">{student.name}'s Homework</h1>
            </div>

            <div className="divide-y">
                {homework.data.map(task => (
                    <div key={task.id} className="px-4 py-3">
                        <div className="flex items-center gap-2 mb-0.5">
                            <span className="text-sm font-medium">{task.title}</span>
                            <Badge variant="outline" className={`text-xs ${STATUS_COLOURS[task.status] ?? ''}`}>
                                {task.status}
                            </Badge>
                        </div>
                        {task.due_at && (
                            <p className="text-xs text-muted-foreground">
                                Due {new Date(task.due_at).toLocaleDateString()}
                            </p>
                        )}
                    </div>
                ))}

                {homework.data.length === 0 && (
                    <p className="px-4 py-8 text-sm text-muted-foreground text-center">
                        No homework assigned yet.
                    </p>
                )}
            </div>
        </ParentLayout>
    );
}
