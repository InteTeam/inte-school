import { Head, useForm } from '@inertiajs/react';
import AuthLayout from '@/layouts/AuthLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

interface Props {
    token: string;
}

export default function AcceptInvitation({ token }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        token,
        name: '',
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('invitation.accept'));
    }

    return (
        <AuthLayout>
            <Head title="Accept Invitation" />

            <div className="grid gap-6">
                <div>
                    <h1 className="text-2xl font-bold">Set up your account</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        You've been invited to join the school. Create your account to get started.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="grid gap-4">
                    <div className="grid gap-1">
                        <Label htmlFor="name">Your Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                            autoComplete="name"
                        />
                        {errors.name && <p className="text-destructive text-sm">{errors.name}</p>}
                    </div>

                    <div className="grid gap-1">
                        <Label htmlFor="password">Password</Label>
                        <Input
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={e => setData('password', e.target.value)}
                            autoComplete="new-password"
                        />
                        {errors.password && <p className="text-destructive text-sm">{errors.password}</p>}
                        <p className="text-xs text-muted-foreground">
                            Minimum 12 characters with mixed case and a number.
                        </p>
                    </div>

                    <div className="grid gap-1">
                        <Label htmlFor="password_confirmation">Confirm Password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={e => setData('password_confirmation', e.target.value)}
                            autoComplete="new-password"
                        />
                    </div>

                    {errors.token && <p className="text-destructive text-sm">{errors.token}</p>}

                    <Button type="submit" disabled={processing} className="w-full">
                        {processing ? 'Setting up…' : 'Create Account'}
                    </Button>
                </form>
            </div>
        </AuthLayout>
    );
}
