import { Head, router } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Badge } from '@/Components/ui/badge';

interface AttendanceStats {
    present: number;
    absent: number;
    late: number;
    total: number;
    attendance_rate: number;
}

interface MessageStats {
    sent: number;
    total_recipients: number;
    read: number;
    engagement_rate: number;
}

interface HomeworkStats {
    todo: number;
    in_progress: number;
    done: number;
    cancelled: number;
    completion_rate: number;
}

interface UserStats {
    admin: number;
    teacher: number;
    support: number;
    parent: number;
    student: number;
    total: number;
}

interface Stats {
    attendance: AttendanceStats;
    messages: MessageStats;
    homework: HomeworkStats;
    users: UserStats;
    period: string;
}

interface Props {
    stats: Stats;
    period: string;
}

const PERIODS = [
    { value: 'week', label: 'Last 7 days' },
    { value: 'month', label: 'Last 30 days' },
    { value: 'term', label: 'Last term (90 days)' },
];

function StatCard({ title, value, sub }: { title: string; value: string; sub?: string }) {
    return (
        <div className="rounded-lg border bg-card p-4">
            <p className="text-xs text-muted-foreground mb-1">{title}</p>
            <p className="text-2xl font-semibold">{value}</p>
            {sub && <p className="text-xs text-muted-foreground mt-1">{sub}</p>}
        </div>
    );
}

export default function StatisticsDashboard({ stats, period }: Props) {
    function changePeriod(p: string) {
        router.get(route('admin.statistics'), { period: p }, { preserveState: true });
    }

    return (
        <SchoolLayout>
            <Head title="Statistics" />

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-xl font-semibold">Statistics</h1>
                <div className="flex gap-2">
                    {PERIODS.map(p => (
                        <button
                            key={p.value}
                            onClick={() => changePeriod(p.value)}
                            className={`px-3 py-1.5 text-xs rounded-md border transition-colors ${
                                period === p.value
                                    ? 'bg-primary text-primary-foreground border-primary'
                                    : 'hover:bg-muted border-border'
                            }`}
                        >
                            {p.label}
                        </button>
                    ))}
                </div>
            </div>

            <div className="space-y-8">
                {/* Attendance */}
                <section>
                    <h2 className="text-sm font-medium text-muted-foreground mb-3 uppercase tracking-wide">
                        Attendance
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <StatCard
                            title="Attendance rate"
                            value={`${stats.attendance.attendance_rate}%`}
                            sub={`${stats.attendance.total} records`}
                        />
                        <StatCard
                            title="Present"
                            value={String(stats.attendance.present)}
                        />
                        <StatCard
                            title="Absent"
                            value={String(stats.attendance.absent)}
                        />
                        <StatCard
                            title="Late"
                            value={String(stats.attendance.late)}
                        />
                    </div>
                </section>

                {/* Messages */}
                <section>
                    <h2 className="text-sm font-medium text-muted-foreground mb-3 uppercase tracking-wide">
                        Messaging
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <StatCard
                            title="Engagement rate"
                            value={`${stats.messages.engagement_rate}%`}
                            sub={`${stats.messages.read} of ${stats.messages.total_recipients} read`}
                        />
                        <StatCard
                            title="Messages sent"
                            value={String(stats.messages.sent)}
                        />
                        <StatCard
                            title="Total recipients"
                            value={String(stats.messages.total_recipients)}
                        />
                    </div>
                </section>

                {/* Homework */}
                <section>
                    <h2 className="text-sm font-medium text-muted-foreground mb-3 uppercase tracking-wide">
                        Homework
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <StatCard
                            title="Completion rate"
                            value={`${stats.homework.completion_rate}%`}
                        />
                        <StatCard title="Completed" value={String(stats.homework.done)} />
                        <StatCard title="In progress" value={String(stats.homework.in_progress)} />
                        <StatCard title="To do" value={String(stats.homework.todo)} />
                    </div>
                </section>

                {/* Users */}
                <section>
                    <h2 className="text-sm font-medium text-muted-foreground mb-3 uppercase tracking-wide">
                        Users
                    </h2>
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-6">
                        <StatCard title="Total" value={String(stats.users.total)} />
                        <StatCard title="Students" value={String(stats.users.student)} />
                        <StatCard title="Parents" value={String(stats.users.parent)} />
                        <StatCard title="Teachers" value={String(stats.users.teacher)} />
                        <StatCard title="Support" value={String(stats.users.support)} />
                        <StatCard title="Admin" value={String(stats.users.admin)} />
                    </div>
                </section>
            </div>
        </SchoolLayout>
    );
}
