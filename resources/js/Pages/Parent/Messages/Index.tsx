import { Head, Link } from '@inertiajs/react';
import ParentLayout from '@/layouts/ParentLayout';
import { Badge } from '@/Components/ui/badge';

interface MessageThread {
    id: string;
    read_at: string | null;
    message: {
        id: string;
        type: string;
        body: string;
        sent_at: string;
        sender: { name: string } | null;
    } | null;
}

interface Props {
    inbox: { data: MessageThread[] };
}

const typeLabel: Record<string, string> = {
    announcement: 'Announcement',
    attendance_alert: 'Attendance',
    trip_permission: 'Permission',
    quick_reply: 'Reply',
};

export default function ParentMessagesIndex({ inbox }: Props) {
    return (
        <ParentLayout>
            <Head title="Messages" />

            <div className="px-4 pt-4 pb-2">
                <h1 className="text-xl font-bold">Messages</h1>
            </div>

            <div className="divide-y">
                {inbox.data.map(item => item.message && (
                    <Link
                        key={item.id}
                        href={route('messages.show', item.message.id)}
                        className="flex items-start gap-3 px-4 py-4 hover:bg-muted/40 transition-colors"
                    >
                        {!item.read_at && (
                            <span className="mt-1.5 h-2 w-2 rounded-full bg-primary shrink-0" />
                        )}
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2 mb-0.5">
                                <span className="text-sm font-medium">{item.message.sender?.name ?? 'School'}</span>
                                <Badge variant="outline" className="text-xs">{typeLabel[item.message.type] ?? item.message.type}</Badge>
                            </div>
                            <p className="text-sm text-muted-foreground truncate">{item.message.body}</p>
                        </div>
                    </Link>
                ))}
                {inbox.data.length === 0 && (
                    <p className="px-4 py-8 text-sm text-muted-foreground text-center">No messages yet.</p>
                )}
            </div>
        </ParentLayout>
    );
}
