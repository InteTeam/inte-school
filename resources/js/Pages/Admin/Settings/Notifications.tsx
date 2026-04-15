import { Head, useForm } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { useState } from 'react';

interface SmsUsage {
    sent_this_year: number;
    free_remaining: number;
    free_allowance: number;
    approaching_limit: boolean;
}

interface NotificationSettings {
    sms_fallback_enabled?: boolean;
    sms_timeout_seconds?: number;
    govuk_notify_api_key?: string;
    govuk_notify_template_id?: string;
    sms_fallback_types?: string[];
}

interface Props {
    notification_settings: NotificationSettings;
    has_notify_key: boolean;
    has_notify_template: boolean;
    sms_usage: SmsUsage;
}

const ALL_SMS_TYPES = [
    { value: 'attendance_alert', label: 'Absence Alerts' },
    { value: 'trip_permission', label: 'Trip Permissions' },
    { value: 'announcement', label: 'Announcements' },
] as const;

export default function SettingsNotifications({
    notification_settings,
    has_notify_key,
    has_notify_template,
    sms_usage,
}: Props) {
    const isNotifyConfigured = has_notify_key && has_notify_template;
    const currentTypes = notification_settings.sms_fallback_types ?? ['attendance_alert', 'trip_permission'];

    const { data, setData, put, processing, errors } = useForm({
        sms_fallback_enabled: notification_settings.sms_fallback_enabled ?? false,
        govuk_notify_api_key: '',
        govuk_notify_template_id: notification_settings.govuk_notify_template_id ?? '',
        sms_fallback_types: currentTypes,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(route('admin.settings.notifications.update'));
    }

    function toggleType(type: string) {
        const types = data.sms_fallback_types.includes(type)
            ? data.sms_fallback_types.filter(t => t !== type)
            : [...data.sms_fallback_types, type];
        setData('sms_fallback_types', types);
    }

    return (
        <SchoolLayout>
            <Head title="Notification Settings" />

            <div className="max-w-2xl">
                <h1 className="text-2xl font-bold mb-6">Notification Settings</h1>

                <form onSubmit={handleSubmit} className="grid gap-6">

                    {/* GOV.UK Notify Configuration */}
                    <section className="rounded-md border p-4 grid gap-4">
                        <div className="flex items-center justify-between">
                            <h2 className="font-semibold">GOV.UK Notify (SMS)</h2>
                            <div className="flex items-center gap-2">
                                {isNotifyConfigured ? (
                                    <Badge variant="default" className="bg-green-600">Configured</Badge>
                                ) : (
                                    <Badge variant="secondary">Not configured</Badge>
                                )}
                                <NotifySetupHelp />
                            </div>
                        </div>

                        <div className="grid gap-3">
                            <div>
                                <Label htmlFor="govuk_notify_api_key">
                                    API Key
                                    {has_notify_key && (
                                        <span className="text-xs text-muted-foreground ml-2">(saved, leave blank to keep current)</span>
                                    )}
                                </Label>
                                <Input
                                    id="govuk_notify_api_key"
                                    type="password"
                                    placeholder={has_notify_key ? '••••••••••••••••' : 'Paste your GOV.UK Notify API key'}
                                    value={data.govuk_notify_api_key}
                                    onChange={e => setData('govuk_notify_api_key', e.target.value)}
                                    autoComplete="off"
                                />
                                {errors.govuk_notify_api_key && (
                                    <p className="text-xs text-red-600 mt-1">{errors.govuk_notify_api_key}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="govuk_notify_template_id">SMS Template ID</Label>
                                <Input
                                    id="govuk_notify_template_id"
                                    placeholder="e.g. a1b2c3d4-e5f6-7890-abcd-ef1234567890"
                                    value={data.govuk_notify_template_id}
                                    onChange={e => setData('govuk_notify_template_id', e.target.value)}
                                />
                                {errors.govuk_notify_template_id && (
                                    <p className="text-xs text-red-600 mt-1">{errors.govuk_notify_template_id}</p>
                                )}
                            </div>
                        </div>
                    </section>

                    {/* SMS Usage */}
                    {isNotifyConfigured && (
                        <section className="rounded-md border p-4 grid gap-3">
                            <h2 className="font-semibold">SMS Usage This Year</h2>
                            <div className="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <p className="text-2xl font-bold">{sms_usage.sent_this_year.toLocaleString()}</p>
                                    <p className="text-xs text-muted-foreground">Texts sent</p>
                                </div>
                                <div>
                                    <p className={`text-2xl font-bold ${sms_usage.approaching_limit ? 'text-orange-600' : ''}`}>
                                        {sms_usage.free_remaining.toLocaleString()}
                                    </p>
                                    <p className="text-xs text-muted-foreground">Free remaining</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">{sms_usage.free_allowance.toLocaleString()}</p>
                                    <p className="text-xs text-muted-foreground">Annual allowance</p>
                                </div>
                            </div>
                            {sms_usage.approaching_limit && (
                                <p className="text-sm text-orange-600 font-medium">
                                    You are approaching your free SMS limit. Additional texts cost 2.4p + VAT each.
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">Allowance resets on 1 April each year. Powered by GOV.UK Notify.</p>
                        </section>
                    )}

                    {/* SMS Fallback Toggle */}
                    <section className="rounded-md border p-4 grid gap-4">
                        <h2 className="font-semibold">SMS Fallback</h2>

                        <div className="flex items-center justify-between">
                            <div>
                                <Label>Enable SMS Fallback</Label>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    Send an SMS if a push notification is not read within {(notification_settings.sms_timeout_seconds ?? 900) / 60} minutes.
                                </p>
                            </div>
                            <Switch
                                checked={data.sms_fallback_enabled}
                                onCheckedChange={checked => setData('sms_fallback_enabled', checked)}
                                disabled={!isNotifyConfigured}
                            />
                        </div>

                        {!isNotifyConfigured && data.sms_fallback_enabled === false && (
                            <p className="text-xs text-muted-foreground">
                                Configure your GOV.UK Notify API key above to enable SMS fallback.
                            </p>
                        )}
                    </section>

                    {/* SMS Fallback Types */}
                    {data.sms_fallback_enabled && isNotifyConfigured && (
                        <section className="rounded-md border p-4 grid gap-3">
                            <h2 className="font-semibold">Message Types That Trigger SMS</h2>
                            <p className="text-xs text-muted-foreground">
                                Choose which message types can fall back to SMS. Keep this selective to preserve your free allowance.
                            </p>
                            <div className="grid gap-2">
                                {ALL_SMS_TYPES.map(type => (
                                    <label key={type.value} className="flex items-center gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={data.sms_fallback_types.includes(type.value)}
                                            onChange={() => toggleType(type.value)}
                                            className="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                        />
                                        <span className="text-sm">{type.label}</span>
                                    </label>
                                ))}
                            </div>
                        </section>
                    )}

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Saving\u2026' : 'Save Changes'}
                    </Button>
                </form>
            </div>
        </SchoolLayout>
    );
}

function NotifySetupHelp() {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <button
                    type="button"
                    className="w-6 h-6 rounded-full bg-purple-100 text-purple-700 text-xs font-bold hover:bg-purple-200 transition-colors flex items-center justify-center"
                    title="How to set up GOV.UK Notify"
                >
                    ?
                </button>
            </DialogTrigger>
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Setting Up GOV.UK Notify</DialogTitle>
                    <DialogDescription>
                        Follow these steps to enable free SMS for your school. Takes about 10 minutes.
                    </DialogDescription>
                </DialogHeader>

                <ol className="grid gap-4 text-sm mt-2">
                    <li className="flex gap-3">
                        <span className="flex-shrink-0 w-6 h-6 rounded-full bg-purple-700 text-white text-xs font-bold flex items-center justify-center">1</span>
                        <div>
                            <p className="font-medium">Create a GOV.UK Notify account</p>
                            <p className="text-muted-foreground mt-0.5">
                                Go to{' '}
                                <a
                                    href="https://www.notifications.service.gov.uk"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-purple-700 underline"
                                >
                                    notifications.service.gov.uk
                                </a>{' '}
                                and register with your school email address.
                            </p>
                        </div>
                    </li>

                    <li className="flex gap-3">
                        <span className="flex-shrink-0 w-6 h-6 rounded-full bg-purple-700 text-white text-xs font-bold flex items-center justify-center">2</span>
                        <div>
                            <p className="font-medium">Create an SMS template</p>
                            <p className="text-muted-foreground mt-0.5">
                                In the Notify dashboard, go to <strong>Templates &rarr; Add template &rarr; Text message</strong>.
                                Set the content to exactly: <code className="bg-slate-100 px-1.5 py-0.5 rounded text-xs">((body))</code>
                            </p>
                        </div>
                    </li>

                    <li className="flex gap-3">
                        <span className="flex-shrink-0 w-6 h-6 rounded-full bg-purple-700 text-white text-xs font-bold flex items-center justify-center">3</span>
                        <div>
                            <p className="font-medium">Copy your Template ID</p>
                            <p className="text-muted-foreground mt-0.5">
                                Open the template you just created. The ID is in the URL or shown on the template page. Paste it into the <strong>SMS Template ID</strong> field below.
                            </p>
                        </div>
                    </li>

                    <li className="flex gap-3">
                        <span className="flex-shrink-0 w-6 h-6 rounded-full bg-purple-700 text-white text-xs font-bold flex items-center justify-center">4</span>
                        <div>
                            <p className="font-medium">Generate an API key</p>
                            <p className="text-muted-foreground mt-0.5">
                                Go to <strong>API integration &rarr; API keys &rarr; Create an API key</strong>.
                                Choose <strong>"Team and guest list"</strong> for testing, or <strong>"Live"</strong> for production.
                                Paste it into the <strong>API Key</strong> field below.
                            </p>
                        </div>
                    </li>

                    <li className="flex gap-3">
                        <span className="flex-shrink-0 w-6 h-6 rounded-full bg-purple-700 text-white text-xs font-bold flex items-center justify-center">5</span>
                        <div>
                            <p className="font-medium">Save and enable</p>
                            <p className="text-muted-foreground mt-0.5">
                                Click <strong>Save Changes</strong>, then turn on <strong>Enable SMS Fallback</strong>.
                                Your school gets <strong>5,000 free texts per year</strong> from GOV.UK — the same service used by the NHS.
                            </p>
                        </div>
                    </li>
                </ol>

                <div className="mt-4 p-3 bg-slate-50 rounded-md text-xs text-muted-foreground">
                    <p className="font-medium text-slate-700 mb-1">Your API key is safe</p>
                    <p>It is encrypted before storage and never shown again. Only you and GOV.UK can read it.</p>
                </div>
            </DialogContent>
        </Dialog>
    );
}
