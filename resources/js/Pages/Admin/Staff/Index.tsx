import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { useState } from 'react';

interface StaffMember {
    id: string;
    name: string;
    email: string;
    role: string;
    department_label: string | null;
    accepted_at: string | null;
    invited_at: string;
}

interface Props {
    staff: StaffMember[];
}

export default function StaffIndex({ staff }: Props) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        role: 'teacher',
        department_label: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(route('admin.staff.invite'), {
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    }

    return (
        <SchoolLayout>
            <Head title="Staff" />

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold">Staff</h1>
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogTrigger asChild>
                        <Button>Invite Staff</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Invite Staff Member</DialogTitle>
                        </DialogHeader>
                        <form onSubmit={handleSubmit} className="grid gap-4">
                            <div className="grid gap-1">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={e => setData('name', e.target.value)}
                                />
                                {errors.name && <p className="text-destructive text-sm">{errors.name}</p>}
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={e => setData('email', e.target.value)}
                                />
                                {errors.email && <p className="text-destructive text-sm">{errors.email}</p>}
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="role">Role</Label>
                                <Select value={data.role} onValueChange={v => setData('role', v)}>
                                    <SelectTrigger id="role">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="admin">Admin</SelectItem>
                                        <SelectItem value="teacher">Teacher</SelectItem>
                                        <SelectItem value="support">Support</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-1">
                                <Label htmlFor="department_label">Department (optional)</Label>
                                <Input
                                    id="department_label"
                                    value={data.department_label}
                                    onChange={e => setData('department_label', e.target.value)}
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                Send Invitation
                            </Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <div className="rounded-md border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b bg-muted/50">
                            <th className="px-4 py-3 text-left font-medium">Name</th>
                            <th className="px-4 py-3 text-left font-medium">Email</th>
                            <th className="px-4 py-3 text-left font-medium">Role</th>
                            <th className="px-4 py-3 text-left font-medium">Department</th>
                            <th className="px-4 py-3 text-left font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {staff.map(member => (
                            <tr key={member.id} className="border-b last:border-0">
                                <td className="px-4 py-3">{member.name}</td>
                                <td className="px-4 py-3 text-muted-foreground">{member.email}</td>
                                <td className="px-4 py-3">
                                    <Badge variant="outline">{member.role}</Badge>
                                </td>
                                <td className="px-4 py-3 text-muted-foreground">{member.department_label ?? '—'}</td>
                                <td className="px-4 py-3">
                                    {member.accepted_at ? (
                                        <Badge variant="default">Active</Badge>
                                    ) : (
                                        <Badge variant="secondary">Pending</Badge>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {staff.length === 0 && (
                            <tr>
                                <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                    No staff members yet. Invite your first staff member.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </SchoolLayout>
    );
}
