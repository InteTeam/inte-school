import { Head, router } from '@inertiajs/react';
import SchoolLayout from '@/layouts/SchoolLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/Components/ui/dialog';
import { useState } from 'react';
import axios from 'axios';

interface Guardian {
    id: string;
    name: string;
    email: string;
    accepted_at: string | null;
}

interface Props {
    guardians: Guardian[];
}

export default function GuardiansIndex({ guardians }: Props) {
    const [codeOpen, setCodeOpen] = useState(false);
    const [studentId, setStudentId] = useState('');
    const [generatedCode, setGeneratedCode] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    async function handleGenerateCode(e: React.FormEvent) {
        e.preventDefault();
        setLoading(true);
        try {
            const response = await axios.post<{ code: string }>(route('admin.guardians.generate-code'), { student_id: studentId });
            setGeneratedCode(response.data.code);
        } finally {
            setLoading(false);
        }
    }

    return (
        <SchoolLayout>
            <Head title="Guardians" />

            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold">Guardians</h1>
                <Dialog open={codeOpen} onOpenChange={open => { setCodeOpen(open); if (!open) { setGeneratedCode(null); setStudentId(''); } }}>
                    <DialogTrigger asChild>
                        <Button>Generate Invite Code</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Generate Guardian Invite Code</DialogTitle>
                        </DialogHeader>
                        {generatedCode ? (
                            <div className="grid gap-4 py-2">
                                <p className="text-sm text-muted-foreground">Share this code with the guardian:</p>
                                <div className="rounded-md bg-muted p-4 text-center text-2xl font-mono font-bold tracking-widest">
                                    {generatedCode}
                                </div>
                                <p className="text-xs text-muted-foreground text-center">Valid for 14 days</p>
                                <Button onClick={() => { setGeneratedCode(null); setStudentId(''); }}>Generate Another</Button>
                            </div>
                        ) : (
                            <form onSubmit={handleGenerateCode} className="grid gap-4">
                                <div className="grid gap-1">
                                    <Label htmlFor="student_id">Student ID</Label>
                                    <Input
                                        id="student_id"
                                        value={studentId}
                                        onChange={e => setStudentId(e.target.value)}
                                        placeholder="Paste student ULID"
                                    />
                                </div>
                                <Button type="submit" disabled={loading || !studentId}>
                                    {loading ? 'Generating…' : 'Generate Code'}
                                </Button>
                            </form>
                        )}
                    </DialogContent>
                </Dialog>
            </div>

            <div className="rounded-md border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b bg-muted/50">
                            <th className="px-4 py-3 text-left font-medium">Name</th>
                            <th className="px-4 py-3 text-left font-medium">Email</th>
                            <th className="px-4 py-3 text-left font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        {guardians.map(g => (
                            <tr key={g.id} className="border-b last:border-0">
                                <td className="px-4 py-3">{g.name}</td>
                                <td className="px-4 py-3 text-muted-foreground">{g.email}</td>
                                <td className="px-4 py-3">
                                    {g.accepted_at ? (
                                        <Badge variant="default">Active</Badge>
                                    ) : (
                                        <Badge variant="secondary">Pending</Badge>
                                    )}
                                </td>
                            </tr>
                        ))}
                        {guardians.length === 0 && (
                            <tr>
                                <td colSpan={3} className="px-4 py-8 text-center text-muted-foreground">
                                    No guardians yet. Generate an invite code to get started.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </SchoolLayout>
    );
}
