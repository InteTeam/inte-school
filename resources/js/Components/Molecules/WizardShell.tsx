interface Step {
    label: string;
    number: number;
}

interface Props {
    steps: Step[];
    currentStep: number;
    children: React.ReactNode;
    title: string;
}

export default function WizardShell({ steps, currentStep, children, title }: Props) {
    return (
        <div className="min-h-screen bg-background px-4 py-8">
            <div className="mx-auto max-w-2xl">
                <h1 className="mb-6 text-2xl font-bold">{title}</h1>

                {/* Progress indicator */}
                <div className="mb-8 flex items-center gap-2">
                    {steps.map((step, index) => (
                        <div key={step.number} className="flex items-center gap-2">
                            <div
                                className={`flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium ${
                                    step.number < currentStep
                                        ? 'bg-primary text-white'
                                        : step.number === currentStep
                                          ? 'border-2 border-primary text-primary'
                                          : 'border-2 border-border text-foreground/40'
                                }`}
                            >
                                {step.number}
                            </div>
                            <span
                                className={`text-sm ${step.number === currentStep ? 'font-medium' : 'text-foreground/40'}`}
                            >
                                {step.label}
                            </span>
                            {index < steps.length - 1 && (
                                <div className="mx-2 h-px w-8 bg-border" />
                            )}
                        </div>
                    ))}
                </div>

                <div className="rounded-lg border bg-card p-6">{children}</div>
            </div>
        </div>
    );
}
