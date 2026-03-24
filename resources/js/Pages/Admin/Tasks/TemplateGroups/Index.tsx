import { Head, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

interface TaskTemplate {
    id: string;
    name: string;
    sort_order: number;
    default_deadline_hours: number | null;
}

interface TaskTemplateGroup {
    id: string;
    name: string;
    task_type: string;
    department_label: string | null;
    templates: TaskTemplate[];
}

interface Props {
    groups: TaskTemplateGroup[];
}

export default function TemplateGroupsIndex({ groups }: Props) {
    return (
        <SchoolLayout>
            <Head title="Task Template Groups" />

            <div className="max-w-3xl">
                <div className="flex items-center justify-between mb-6">
                    <h1 className="text-2xl font-bold">Task Template Groups</h1>
                    <Button size="sm" asChild>
                        <Link href={route('admin.tasks.template-groups.store')}>+ New Group</Link>
                    </Button>
                </div>

                <div className="space-y-4">
                    {groups.map(group => (
                        <div key={group.id} className="border rounded-lg overflow-hidden">
                            <div className="flex items-center gap-3 px-4 py-3 bg-muted/20">
                                <div className="flex-1">
                                    <h2 className="font-medium text-sm">{group.name}</h2>
                                    {group.department_label && (
                                        <p className="text-xs text-muted-foreground">{group.department_label}</p>
                                    )}
                                </div>
                                <Badge variant="outline" className="text-xs">{group.task_type}</Badge>
                            </div>

                            {group.templates.length > 0 && (
                                <ul className="divide-y">
                                    {group.templates.map((tpl, index) => (
                                        <li key={tpl.id} className="flex items-center gap-3 px-4 py-2">
                                            <span className="text-xs text-muted-foreground w-5">{index + 1}.</span>
                                            <span className="flex-1 text-sm">{tpl.name}</span>
                                            {tpl.default_deadline_hours && (
                                                <span className="text-xs text-muted-foreground">
                                                    +{tpl.default_deadline_hours}h
                                                </span>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}

                            {group.templates.length === 0 && (
                                <p className="px-4 py-3 text-xs text-muted-foreground">No items in this group.</p>
                            )}
                        </div>
                    ))}

                    {groups.length === 0 && (
                        <p className="text-sm text-muted-foreground text-center py-12">
                            No template groups yet.
                        </p>
                    )}
                </div>
            </div>
        </SchoolLayout>
    );
}
