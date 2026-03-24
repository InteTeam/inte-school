import { Head, useForm, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';
import ParentLayout from '@/layouts/ParentLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import axios from 'axios';

interface Recipient {
    id: string;
    recipient: { name: string } | null;
    read_at: string | null;
    quick_reply: string | null;
}

interface Attachment {
    id: string;
    file_name: string;
}

interface MessageDetail {
    id: string;
    type: string;
    body: string;
    sent_at: string;
    requires_read_receipt: boolean;
    sender: { name: string } | null;
    recipients: Recipient[];
    attachments: Attachment[];
    thread: MessageDetail[];
}

interface Props {
    message: MessageDetail;
}

const QUICK_REPLIES = ['Acknowledged', 'Yes, I consent', 'No, I do not consent', 'Please call me'];

export default function ParentThread({ message }: Props) {
    const { post, processing } = useForm({});

    // Mark as read on mount
    useEffect(() => {
        axios.post(route('messages.read', message.id)).catch(() => {});
    }, [message.id]);

    function sendQuickReply(reply: string) {
        router.post(route('messages.reply', message.id), { reply });
    }

    return (
        <ParentLayout>
            <Head title={`Message from ${message.sender?.name ?? 'School'}`} />

            <div className="px-4 pt-4 pb-2 flex items-center gap-2">
                <Button variant="ghost" size="sm" asChild className="px-0">
                    <Link href={route('parent.messages.index')}>← Back</Link>
                </Button>
            </div>

            <div className="px-4 pb-6">
                <div className="mb-4">
                    <div className="flex items-center gap-2 mb-1">
                        <span className="font-semibold">{message.sender?.name ?? 'School'}</span>
                        <Badge variant="outline" className="text-xs">{message.type}</Badge>
                    </div>
                    <p className="text-sm text-muted-foreground">{message.sent_at}</p>
                </div>

                <p className="text-sm mb-4 whitespace-pre-wrap">{message.body}</p>

                {message.attachments.length > 0 && (
                    <div className="mb-4">
                        <p className="text-xs font-medium text-muted-foreground mb-2">Attachments</p>
                        <div className="flex flex-wrap gap-2">
                            {message.attachments.map(att => (
                                <a
                                    key={att.id}
                                    href={route('messages.attachments.download', att.id)}
                                    className="text-sm text-primary underline"
                                >
                                    {att.file_name}
                                </a>
                            ))}
                        </div>
                    </div>
                )}

                {message.requires_read_receipt && (
                    <div className="rounded-md border p-4 mt-4">
                        <p className="text-sm font-medium mb-3">Quick Reply</p>
                        <div className="flex flex-wrap gap-2">
                            {QUICK_REPLIES.map(reply => (
                                <Button
                                    key={reply}
                                    variant="outline"
                                    size="sm"
                                    onClick={() => sendQuickReply(reply)}
                                    disabled={processing}
                                >
                                    {reply}
                                </Button>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </ParentLayout>
    );
}
