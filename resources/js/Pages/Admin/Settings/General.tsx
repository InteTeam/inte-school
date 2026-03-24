import { Head, useForm } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';

interface ThemeConfig {
    primary_color?: string;
    dark_mode?: boolean;
}

interface Props {
    school: {
        id: string;
        name: string;
        slug: string;
        logo_url: string | null;
        theme_config: ThemeConfig;
    };
}

export default function SettingsGeneral({ school }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: school.name,
        logo: null as File | null,
        theme_config: {
            primary_color: school.theme_config.primary_color ?? '#2563eb',
            dark_mode: school.theme_config.dark_mode ?? false,
        },
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.settings.general.update'));
    }

    return (
        <SchoolLayout>
            <Head title="General Settings" />

            <div className="max-w-2xl">
                <h1 className="text-2xl font-bold mb-6">General Settings</h1>

                <form onSubmit={handleSubmit} className="grid gap-6">
                    <section className="rounded-md border p-4 grid gap-4">
                        <h2 className="font-semibold">School Details</h2>

                        <div className="grid gap-1">
                            <Label htmlFor="name">School Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={e => setData('name', e.target.value)}
                            />
                            {errors.name && <p className="text-destructive text-sm">{errors.name}</p>}
                        </div>

                        <div className="grid gap-1">
                            <Label>School Slug</Label>
                            <Input value={school.slug} disabled className="bg-muted" />
                            <p className="text-xs text-muted-foreground">Slug cannot be changed after setup.</p>
                        </div>
                    </section>

                    <section className="rounded-md border p-4 grid gap-4">
                        <h2 className="font-semibold">Logo</h2>

                        {school.logo_url && (
                            <img src={school.logo_url} alt="School logo" className="h-16 w-16 rounded object-contain border" />
                        )}

                        <div className="grid gap-1">
                            <Label htmlFor="logo">Upload Logo</Label>
                            <Input
                                id="logo"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={e => setData('logo', e.target.files?.[0] ?? null)}
                            />
                            <p className="text-xs text-muted-foreground">JPG, PNG or WebP. Max 2 MB.</p>
                            {errors.logo && <p className="text-destructive text-sm">{errors.logo}</p>}
                        </div>
                    </section>

                    <section className="rounded-md border p-4 grid gap-4">
                        <h2 className="font-semibold">Theme</h2>

                        <div className="grid gap-1">
                            <Label htmlFor="primary_color">Primary Colour</Label>
                            <div className="flex items-center gap-2">
                                <input
                                    id="primary_color"
                                    type="color"
                                    value={data.theme_config.primary_color}
                                    onChange={e => setData('theme_config', { ...data.theme_config, primary_color: e.target.value })}
                                    className="h-9 w-16 cursor-pointer rounded border"
                                />
                                <Input
                                    value={data.theme_config.primary_color}
                                    onChange={e => setData('theme_config', { ...data.theme_config, primary_color: e.target.value })}
                                    className="w-32 font-mono text-sm"
                                    maxLength={7}
                                />
                            </div>
                        </div>

                        <div className="flex items-center justify-between">
                            <div>
                                <Label>Dark Mode</Label>
                                <p className="text-xs text-muted-foreground">Enable dark theme for all users by default.</p>
                            </div>
                            <Switch
                                checked={data.theme_config.dark_mode}
                                onCheckedChange={checked => setData('theme_config', { ...data.theme_config, dark_mode: checked })}
                            />
                        </div>
                    </section>

                    <Button type="submit" disabled={processing} className="w-fit">
                        {processing ? 'Saving…' : 'Save Changes'}
                    </Button>
                </form>
            </div>
        </SchoolLayout>
    );
}
