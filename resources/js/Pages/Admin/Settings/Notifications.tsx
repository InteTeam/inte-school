import { Head, useForm } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

interface NotificationSettings {
    sms_fallback_enabled?: boolean;
    sms_timeout_seconds?: number;
}

interface Props {
    notification_settings: NotificationSettings;
}

export default function SettingsNotifications({ notification_settings }: Props) {
    const { data, setData, put, processing } = useForm({
        sms_fallback_enabled: notification_settings.sms_fallback_enabled ?? false,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(route('admin.settings.notifications.update'));
    }

    return (
        <SchoolLayout>
            <Head title="Notification Settings" />

            <div className="max-w-2xl">
                <h1 className="text-2xl font-bold mb-6">Notification Settings</h1>

                <form onSubmit={handleSubmit} className="grid gap-6">
                    <section className="rounded-md border p-4 grid gap-4">
                        <h2 className="font-semibold">SMS Fallback</h2>

                        <div className="flex items-center justify-between">
                            <div>
                                <Label>Enable SMS Fallback</Label>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    Send an SMS if a push notification is not read within 15 minutes.
                                    Requires an SMS provider to be configured.
                                </p>
                            </div>
                            <Switch
                                checked={data.sms_fallback_enabled}
                                onCheckedChange={checked => setData('sms_fallback_enabled', checked)}
                            />
                        </div>

                        {notification_settings.sms_timeout_seconds !== undefined && (
                            <p className="text-xs text-muted-foreground">
                                SMS fires after {notification_settings.sms_timeout_seconds / 60} minutes of no read receipt.
                            </p>
                        )}
                    </section>

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Saving…' : 'Save Changes'}
                    </Button>
                </form>
            </div>
        </SchoolLayout>
    );
}
