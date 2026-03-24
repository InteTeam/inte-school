import { Head, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import TodoList, { type TodoItem } from '@/Components/Organisms/TodoList';

interface TaskItem extends TodoItem {}

interface Task {
    id: string;
    type: string;
    title: string;
    description: string | null;
    status: string;
    priority: string | null;
    due_at: string | null;
    items: TaskItem[];
}

interface Props {
    tasks: Task[];
}

const STATUS_COLOURS: Record<string, string> = {
    todo: 'bg-slate-100 text-slate-700',
    in_progress: 'bg-blue-100 text-blue-700',
    done: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
};

const PRIORITY_COLOURS: Record<string, string> = {
    low: 'text-slate-500',
    medium: 'text-amber-600',
    high: 'text-orange-600',
    urgent: 'text-red-600',
};

export default function TeacherTasksIndex({ tasks }: Props) {
    return (
        <SchoolLayout>
            <Head title="Tasks" />

            <div className="max-w-3xl">
                <div className="flex items-center justify-between mb-6">
                    <h1 className="text-2xl font-bold">Tasks</h1>
                    <div className="flex gap-2">
                        <Button size="sm" asChild>
                            <Link href={route('teacher.tasks.homework.create')}>+ Homework</Link>
                        </Button>
                        <Button size="sm" variant="outline" asChild>
                            <Link href={route('teacher.tasks.store')}>+ Task</Link>
                        </Button>
                    </div>
                </div>

                <div className="space-y-4">
                    {tasks.map(task => (
                        <div key={task.id} className="border rounded-lg overflow-hidden">
                            <div className="flex items-center gap-3 px-4 py-3 bg-muted/20">
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 flex-wrap">
                                        <span className="font-medium text-sm">{task.title}</span>
                                        <Badge variant="outline" className={`text-xs ${STATUS_COLOURS[task.status] ?? ''}`}>
                                            {task.status}
                                        </Badge>
                                        {task.type === 'homework' && (
                                            <Badge variant="outline" className="text-xs">homework</Badge>
                                        )}
                                        {task.priority && (
                                            <span className={`text-xs font-medium ${PRIORITY_COLOURS[task.priority] ?? ''}`}>
                                                {task.priority}
                                            </span>
                                        )}
                                    </div>
                                    {task.due_at && (
                                        <p className="text-xs text-muted-foreground mt-0.5">
                                            Due {new Date(task.due_at).toLocaleDateString()}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {task.items.length > 0 && (
                                <div className="px-2 py-1 border-t">
                                    <TodoList taskId={task.id} items={task.items} />
                                </div>
                            )}
                        </div>
                    ))}

                    {tasks.length === 0 && (
                        <p className="text-sm text-muted-foreground text-center py-12">
                            No tasks yet.
                        </p>
                    )}
                </div>
            </div>
        </SchoolLayout>
    );
}
