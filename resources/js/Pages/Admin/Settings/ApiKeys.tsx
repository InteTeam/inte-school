import { Head, router, useForm } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { useState, FormEvent } from 'react';

interface ApiKey {
    id: string;
    name: string;
    permissions: string[];
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
}

interface Props {
    keys: ApiKey[];
    generated_key: string | null;
}

const ALL_PERMISSIONS = ['attendance', 'messages', 'homework', 'users'] as const;

interface FormData {
    name: string;
    permissions: string[];
    expires_at: string;
    [key: string]: string | string[];
}

export default function ApiKeysPage({ keys, generated_key }: Props) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm<FormData>({
        name: '',
        permissions: ['attendance'],
        expires_at: '',
    });

    function togglePermission(perm: string) {
        const current = data.permissions;
        setData(
            'permissions',
            current.includes(perm) ? current.filter(p => p !== perm) : [...current, perm]
        );
    }

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(route('admin.settings.api-keys.store'), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    }

    function handleRevoke(id: string) {
        if (!confirm('Revoke this API key? External integrations using it will stop working.')) return;
        router.delete(route('admin.settings.api-keys.destroy', id));
    }

    return (
        <SchoolLayout>
            <Head title="API Keys" />

            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-xl font-semibold">API Keys</h1>
                    <p className="text-sm text-muted-foreground mt-0.5">
                        Allow external systems (e.g. local authority) to read school statistics.
                    </p>
                </div>
                <Button onClick={() => setShowForm(v => !v)} variant={showForm ? 'outline' : 'default'}>
                    {showForm ? 'Cancel' : 'Generate Key'}
                </Button>
            </div>

            {/* One-time generated key banner */}
            {generated_key && (
                <div className="mb-6 rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-950/30 p-4">
                    <p className="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">
                        Copy your API key now — it will not be shown again.
                    </p>
                    <code className="block font-mono text-sm bg-white dark:bg-black/20 rounded px-3 py-2 border break-all select-all">
                        {generated_key}
                    </code>
                </div>
            )}

            {/* Create form */}
            {showForm && (
                <form onSubmit={handleSubmit} className="mb-6 rounded-md border p-4 space-y-4 max-w-md">
                    <h2 className="text-sm font-semibold">New API Key</h2>

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium" htmlFor="key-name">Name</label>
                        <input
                            id="key-name"
                            type="text"
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            placeholder="e.g. Local Authority Integration"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                        />
                        {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                    </div>

                    <div className="space-y-2">
                        <p className="text-sm font-medium">Permissions</p>
                        <div className="flex flex-wrap gap-2">
                            {ALL_PERMISSIONS.map(perm => (
                                <label key={perm} className="flex items-center gap-1.5 text-sm cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.permissions.includes(perm)}
                                        onChange={() => togglePermission(perm)}
                                        className="rounded"
                                    />
                                    {perm}
                                </label>
                            ))}
                        </div>
                        {errors.permissions && <p className="text-xs text-destructive">{errors.permissions}</p>}
                    </div>

                    <div className="space-y-1.5">
                        <label className="text-sm font-medium" htmlFor="key-expires">
                            Expires <span className="text-muted-foreground">(optional)</span>
                        </label>
                        <input
                            id="key-expires"
                            type="date"
                            className="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            value={data.expires_at}
                            onChange={e => setData('expires_at', e.target.value)}
                        />
                    </div>

                    <Button type="submit" disabled={processing || data.permissions.length === 0}>
                        {processing ? 'Generating…' : 'Generate'}
                    </Button>
                </form>
            )}

            {/* Keys table */}
            {keys.length === 0 ? (
                <p className="text-sm text-muted-foreground">No API keys yet.</p>
            ) : (
                <div className="rounded-md border overflow-hidden">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="text-left px-4 py-2 font-medium">Name</th>
                                <th className="text-left px-4 py-2 font-medium">Permissions</th>
                                <th className="text-left px-4 py-2 font-medium">Last used</th>
                                <th className="text-left px-4 py-2 font-medium">Expires</th>
                                <th className="px-4 py-2" />
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {keys.map(key => (
                                <tr key={key.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3 font-medium">{key.name}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap gap-1">
                                            {key.permissions.map(p => (
                                                <Badge key={p} variant="outline" className="text-xs">{p}</Badge>
                                            ))}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {key.last_used_at
                                            ? new Date(key.last_used_at).toLocaleDateString()
                                            : 'Never'}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {key.expires_at
                                            ? new Date(key.expires_at).toLocaleDateString()
                                            : '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() => handleRevoke(key.id)}
                                        >
                                            Revoke
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </SchoolLayout>
    );
}
