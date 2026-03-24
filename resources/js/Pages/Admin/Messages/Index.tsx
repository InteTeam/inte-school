import { Head, Link } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

interface RecipientSummary {
    id: string;
    recipient: { name: string } | null;
    read_at: string | null;
}

interface OutboxMessage {
    id: string;
    type: string;
    body: string;
    sent_at: string;
    requires_read_receipt: boolean;
    recipients: RecipientSummary[];
}

interface InboxItem {
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
    outbox: { data: OutboxMessage[] };
    inbox: { data: InboxItem[] };
}

const typeColour: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    announcement: 'outline',
    attendance_alert: 'destructive',
    trip_permission: 'default',
    quick_reply: 'secondary',
};

export default function MessagesIndex({ outbox, inbox }: Props) {
    return (
        <SchoolLayout>
            <Head title="Messages" />

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold">Messages</h1>
                <Button asChild>
                    <Link href={route('messages.compose')}>Compose</Link>
                </Button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <section>
                    <h2 className="font-semibold mb-3">Inbox</h2>
                    <div className="rounded-md border divide-y">
                        {inbox.data.map(item => item.message && (
                            <Link key={item.id} href={route('messages.show', item.message.id)} className="flex items-start gap-3 p-4 hover:bg-muted/50 transition-colors">
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 mb-1">
                                        <span className="font-medium text-sm">{item.message.sender?.name ?? 'Unknown'}</span>
                                        <Badge variant={typeColour[item.message.type] ?? 'outline'} className="text-xs">{item.message.type}</Badge>
                                        {!item.read_at && <span className="h-2 w-2 rounded-full bg-primary shrink-0" />}
                                    </div>
                                    <p className="text-sm text-muted-foreground truncate">{item.message.body}</p>
                                </div>
                            </Link>
                        ))}
                        {inbox.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">Your inbox is empty.</p>
                        )}
                    </div>
                </section>

                <section>
                    <h2 className="font-semibold mb-3">Sent</h2>
                    <div className="rounded-md border divide-y">
                        {outbox.data.map(msg => (
                            <Link key={msg.id} href={route('messages.show', msg.id)} className="flex items-start gap-3 p-4 hover:bg-muted/50 transition-colors">
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center gap-2 mb-1">
                                        <Badge variant={typeColour[msg.type] ?? 'outline'} className="text-xs">{msg.type}</Badge>
                                        {msg.requires_read_receipt && (
                                            <span className="text-xs text-muted-foreground">
                                                {msg.recipients.filter(r => r.read_at).length}/{msg.recipients.length} read
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-sm text-muted-foreground truncate">{msg.body}</p>
                                </div>
                            </Link>
                        ))}
                        {outbox.data.length === 0 && (
                            <p className="p-4 text-sm text-muted-foreground">No sent messages.</p>
                        )}
                    </div>
                </section>
            </div>
        </SchoolLayout>
    );
}
