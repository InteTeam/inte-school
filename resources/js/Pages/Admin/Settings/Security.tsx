import { Head, useForm } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import { Badge } from '@/Components/ui/badge';

interface SecurityPolicy {
    require_2fa?: boolean;
    session_timeout_minutes?: number;
}

interface Props {
    security_policy: SecurityPolicy;
    plan: string;
}

export default function SettingsSecurity({ security_policy, plan }: Props) {
    const isSecurityPlus = plan === 'security_plus' || plan === 'enterprise';

    const { data, setData, put, processing } = useForm({
        require_2fa: security_policy.require_2fa ?? false,
        session_timeout_minutes: security_policy.session_timeout_minutes ?? 480,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(route('admin.settings.security.update'));
    }

    return (
        <SchoolLayout>
            <Head title="Security Settings" />

            <div className="max-w-2xl">
                <div className="flex items-center gap-3 mb-6">
                    <h1 className="text-2xl font-bold">Security Settings</h1>
                    <Badge variant="outline">{plan}</Badge>
                </div>

                <form onSubmit={handleSubmit} className="grid gap-6">
                    <section className="rounded-md border p-4 grid gap-4">
                        <h2 className="font-semibold">Authentication</h2>

                        <div className="flex items-center justify-between">
                            <div>
                                <Label>Require Two-Factor Authentication</Label>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    All staff must enable 2FA before accessing the school.
                                </p>
                            </div>
                            <Switch
                                checked={data.require_2fa}
                                onCheckedChange={checked => setData('require_2fa', checked)}
                            />
                        </div>

                        <div className="grid gap-1">
                            <Label htmlFor="session_timeout">Session Timeout (minutes)</Label>
                            <Input
                                id="session_timeout"
                                type="number"
                                min={15}
                                max={1440}
                                value={data.session_timeout_minutes}
                                onChange={e => setData('session_timeout_minutes', parseInt(e.target.value, 10))}
                                className="w-32"
                            />
                            <p className="text-xs text-muted-foreground">
                                Between 15 minutes and 24 hours (1440 min).
                            </p>
                        </div>
                    </section>

                    {!isSecurityPlus && (
                        <section className="rounded-md border border-dashed p-4">
                            <div className="flex items-center gap-2 mb-1">
                                <h2 className="font-semibold">Security+ Features</h2>
                                <Badge variant="secondary">Security+ plan required</Badge>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Advanced IP allowlisting, audit logs, and SSO are available on the Security+ plan.
                                Contact your account manager to upgrade.
                            </p>
                        </section>
                    )}

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Saving…' : 'Save Changes'}
                    </Button>
                </form>
            </div>
        </SchoolLayout>
    );
}
