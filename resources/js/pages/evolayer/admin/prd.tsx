import { Head } from '@inertiajs/react';
import { AlertCircle, FileText, LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent, ReactElement } from 'react';
import { generate } from '@/actions/Xuple/EvoLayer/Base/Http/Controllers/Admin/PrdController';
import { AiTextField } from '@/blocks/ai-text-field';
import { VoiceInput } from '@/blocks/voice-input';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';

type PrdForm = {
    product_context: string;
    audience: string;
    constraints: string;
    tone: 'concise' | 'detailed' | 'technical';
};

type PrdRequirement = {
    label: string;
    description: string;
    priority: 'must' | 'should' | 'could';
    acceptance_criteria: string[];
};

type PrdUserStory = {
    persona: string;
    need: string;
    benefit: string;
};

type PrdResult = {
    title: string;
    summary: string;
    problem: string;
    audience: string;
    goals: string[];
    non_goals: string[];
    user_stories: PrdUserStory[];
    requirements: PrdRequirement[];
    risks: string[];
    success_metrics: string[];
    open_questions: string[];
};

type ValidationErrors = Partial<Record<keyof PrdForm, string>>;

function getXsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.split('=').slice(1).join('=')) : '';
}

function priorityVariant(priority: PrdRequirement['priority']) {
    return priority === 'must'
        ? 'default'
        : priority === 'should'
          ? 'secondary'
          : 'outline';
}

type PrdPageProps = {
    aiTextAssistUrl: string | null;
    voiceInputUrl: string | null;
};

export default function PrdPage({
    aiTextAssistUrl,
    voiceInputUrl,
}: PrdPageProps): ReactElement {
    const [form, setForm] = useState<PrdForm>({
        product_context: '',
        audience: '',
        constraints: '',
        tone: 'concise',
    });
    const [errors, setErrors] = useState<ValidationErrors>({});
    const [message, setMessage] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);
    const [prd, setPrd] = useState<PrdResult | null>(null);

    function update<K extends keyof PrdForm>(key: K, value: PrdForm[K]): void {
        setForm((current) => ({ ...current, [key]: value }));
        setErrors((current) => ({ ...current, [key]: undefined }));
    }

    async function submit(event: FormEvent<HTMLFormElement>): Promise<void> {
        event.preventDefault();
        setProcessing(true);
        setErrors({});
        setMessage(null);

        try {
            const response = await fetch(generate.url(), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                body: JSON.stringify(form),
                credentials: 'same-origin',
            });

            const json = (await response.json().catch(() => null)) as {
                prd?: PrdResult;
                message?: string;
                errors?: Record<string, string[]>;
            } | null;

            if (response.status === 422 && json?.errors) {
                setErrors(
                    Object.fromEntries(
                        Object.entries(json.errors).map(([key, value]) => [
                            key,
                            value[0],
                        ]),
                    ) as ValidationErrors,
                );

                return;
            }

            if (!response.ok || !json?.prd) {
                setMessage(
                    json?.message ??
                        'The PRD generator is unavailable right now. Try again in a moment.',
                );

                return;
            }

            setPrd(json.prd);
        } catch {
            setMessage('The network connection was lost. Try again.');
        } finally {
            setProcessing(false);
        }
    }

    return (
        <>
            <Head title="PRD Studio" />

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 overflow-y-auto px-6 py-8 md:px-10 lg:px-12">
                <div className="flex flex-col gap-2">
                    <div className="flex items-center gap-2 text-sm font-medium text-neutral-500 dark:text-neutral-400">
                        <FileText className="size-4" />
                        Product planning
                    </div>
                    <h1 className="text-2xl font-semibold text-neutral-950 dark:text-neutral-50">
                        PRD Studio
                    </h1>
                    <p className="max-w-2xl text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                        Turn rough product notes into a scoped product
                        requirements draft with requirements, risks, metrics,
                        and open questions.
                    </p>
                </div>

                <div className="grid gap-6 xl:grid-cols-[420px_minmax(0,1fr)]">
                    <Card className="h-fit border-neutral-200/80 shadow-sm dark:border-neutral-800">
                        <CardHeader>
                            <CardTitle>Input</CardTitle>
                            <CardDescription>
                                Provide the raw product context and constraints.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form className="space-y-5" onSubmit={submit}>
                                {aiTextAssistUrl ? (
                                    <>
                                        <AiTextField
                                            id="product_context"
                                            label="Product context"
                                            value={form.product_context}
                                            onChange={(value) => {
                                                update(
                                                    'product_context',
                                                    value,
                                                );
                                            }}
                                            suggestUrl={aiTextAssistUrl}
                                            buildPayload={() => ({
                                                field_hint:
                                                    'Product context for a PRD document — describe what the product does, who it is for, and the core problem it solves',
                                                context:
                                                    [
                                                        form.audience &&
                                                            `Audience: ${form.audience}`,
                                                        form.constraints &&
                                                            `Constraints: ${form.constraints}`,
                                                    ]
                                                        .filter(Boolean)
                                                        .join('\n') ||
                                                    undefined,
                                            })}
                                            rows={8}
                                            placeholder="Describe the product, workflow, customer pain, business goal, and any existing system context."
                                            disabled={processing}
                                            labelActions={
                                                voiceInputUrl ? (
                                                    <VoiceInput
                                                        size="sm"
                                                        label="Dictate"
                                                        disabled={processing}
                                                        transcribeUrl={
                                                            voiceInputUrl
                                                        }
                                                        onTranscribed={(
                                                            text,
                                                        ) => {
                                                            setForm(
                                                                (current) => ({
                                                                    ...current,
                                                                    product_context:
                                                                        current.product_context
                                                                            ? `${current.product_context.trimEnd()} ${text}`
                                                                            : text,
                                                                }),
                                                            );
                                                            setErrors(
                                                                (current) => ({
                                                                    ...current,
                                                                    product_context:
                                                                        undefined,
                                                                }),
                                                            );
                                                        }}
                                                        onError={(error) =>
                                                            setMessage(error)
                                                        }
                                                    />
                                                ) : undefined
                                            }
                                        />
                                        <InputError
                                            message={errors.product_context}
                                        />
                                    </>
                                ) : (
                                    <div className="grid gap-2">
                                        <div className="flex items-center justify-between gap-3">
                                            <Label htmlFor="product_context">
                                                Product context
                                            </Label>
                                            {voiceInputUrl && (
                                                <VoiceInput
                                                    size="sm"
                                                    label="Dictate"
                                                    disabled={processing}
                                                    transcribeUrl={
                                                        voiceInputUrl
                                                    }
                                                    onTranscribed={(text) => {
                                                        setForm((current) => ({
                                                            ...current,
                                                            product_context:
                                                                current.product_context
                                                                    ? `${current.product_context.trimEnd()} ${text}`
                                                                    : text,
                                                        }));
                                                        setErrors(
                                                            (current) => ({
                                                                ...current,
                                                                product_context:
                                                                    undefined,
                                                            }),
                                                        );
                                                    }}
                                                    onError={(error) =>
                                                        setMessage(error)
                                                    }
                                                />
                                            )}
                                        </div>
                                        <textarea
                                            id="product_context"
                                            value={form.product_context}
                                            onChange={(event) =>
                                                update(
                                                    'product_context',
                                                    event.target.value,
                                                )
                                            }
                                            rows={8}
                                            className="min-h-40 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                            placeholder="Describe the product, workflow, customer pain, business goal, and any existing system context."
                                        />
                                        <InputError
                                            message={errors.product_context}
                                        />
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="audience">Audience</Label>
                                    <Input
                                        id="audience"
                                        value={form.audience}
                                        onChange={(event) =>
                                            update(
                                                'audience',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="e.g. solo Laravel founders, support teams, internal operators"
                                    />
                                    <InputError message={errors.audience} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="constraints">
                                        Constraints
                                    </Label>
                                    <Input
                                        id="constraints"
                                        value={form.constraints}
                                        onChange={(event) =>
                                            update(
                                                'constraints',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="e.g. admin-only, no persistence yet, SQLite fallback required"
                                    />
                                    <InputError message={errors.constraints} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="tone">Tone</Label>
                                    <select
                                        id="tone"
                                        value={form.tone}
                                        onChange={(event) =>
                                            update(
                                                'tone',
                                                event.target
                                                    .value as PrdForm['tone'],
                                            )
                                        }
                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    >
                                        <option value="concise">Concise</option>
                                        <option value="detailed">
                                            Detailed
                                        </option>
                                        <option value="technical">
                                            Technical
                                        </option>
                                    </select>
                                    <InputError message={errors.tone} />
                                </div>

                                {message && (
                                    <Alert variant="destructive">
                                        <AlertCircle className="size-4" />
                                        <AlertDescription>
                                            {message}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={processing}
                                >
                                    {processing && (
                                        <LoaderCircle className="size-4 animate-spin" />
                                    )}
                                    Generate PRD
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="min-w-0">
                        {processing && !prd ? (
                            <PrdSkeleton />
                        ) : prd ? (
                            <PrdResultView prd={prd} />
                        ) : (
                            <div className="flex min-h-[560px] items-center justify-center rounded-xl border border-dashed border-neutral-300 bg-neutral-50/60 p-8 text-center dark:border-neutral-800 dark:bg-neutral-950/40">
                                <div className="max-w-sm">
                                    <FileText className="mx-auto size-8 text-neutral-400" />
                                    <h2 className="mt-4 text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        No PRD generated yet
                                    </h2>
                                    <p className="mt-2 text-sm leading-6 text-neutral-500 dark:text-neutral-400">
                                        Generated sections will appear here
                                        after the request completes.
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function PrdResultView({ prd }: { prd: PrdResult }): ReactElement {
    return (
        <div className="space-y-5">
            <Card className="border-neutral-200/80 shadow-sm dark:border-neutral-800">
                <CardHeader>
                    <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <CardTitle>{prd.title}</CardTitle>
                            <CardDescription className="mt-2 max-w-3xl leading-6">
                                {prd.summary}
                            </CardDescription>
                        </div>
                        <Badge variant="outline">Draft PRD</Badge>
                    </div>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-2">
                    <Section title="Problem" items={[prd.problem]} />
                    <Section title="Audience" items={[prd.audience]} />
                </CardContent>
            </Card>

            <div className="grid gap-5 lg:grid-cols-2">
                <SectionCard title="Goals" items={prd.goals} />
                <SectionCard title="Non-goals" items={prd.non_goals} />
            </div>

            <Card className="border-neutral-200/80 shadow-sm dark:border-neutral-800">
                <CardHeader>
                    <CardTitle>User stories</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    {prd.user_stories.map((story, index) => (
                        <div
                            key={`${story.persona}-${index}`}
                            className="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <div className="text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                {story.persona}
                            </div>
                            <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                Needs {story.need} so they can {story.benefit}.
                            </p>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card className="border-neutral-200/80 shadow-sm dark:border-neutral-800">
                <CardHeader>
                    <CardTitle>Requirements</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {prd.requirements.map((requirement, index) => (
                        <div
                            key={`${requirement.label}-${index}`}
                            className="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <h3 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                    {requirement.label}
                                </h3>
                                <Badge
                                    variant={priorityVariant(
                                        requirement.priority,
                                    )}
                                >
                                    {requirement.priority}
                                </Badge>
                            </div>
                            <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                {requirement.description}
                            </p>
                            <ul className="mt-3 space-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                                {requirement.acceptance_criteria.map(
                                    (criterion, criterionIndex) => (
                                        <li
                                            key={`${criterion}-${criterionIndex}`}
                                            className="flex gap-2"
                                        >
                                            <span className="mt-2 size-1.5 shrink-0 rounded-full bg-neutral-400" />
                                            <span>{criterion}</span>
                                        </li>
                                    ),
                                )}
                            </ul>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <div className="grid gap-5 lg:grid-cols-3">
                <SectionCard title="Risks" items={prd.risks} />
                <SectionCard
                    title="Success metrics"
                    items={prd.success_metrics}
                />
                <SectionCard
                    title="Open questions"
                    items={prd.open_questions}
                />
            </div>
        </div>
    );
}

function SectionCard({
    title,
    items,
}: {
    title: string;
    items: string[];
}): ReactElement {
    return (
        <Card className="border-neutral-200/80 shadow-sm dark:border-neutral-800">
            <CardHeader>
                <CardTitle>{title}</CardTitle>
            </CardHeader>
            <CardContent>
                <Section title={title} items={items} hideTitle />
            </CardContent>
        </Card>
    );
}

function Section({
    title,
    items,
    hideTitle = false,
}: {
    title: string;
    items: string[];
    hideTitle?: boolean;
}): ReactElement {
    return (
        <div>
            {!hideTitle && (
                <h3 className="text-xs font-semibold tracking-[0.16em] text-neutral-500 uppercase dark:text-neutral-400">
                    {title}
                </h3>
            )}
            <ul className="mt-2 space-y-2 text-sm leading-6 text-neutral-700 dark:text-neutral-300">
                {items.map((item, index) => (
                    <li key={`${item}-${index}`} className="flex gap-2">
                        <span className="mt-2 size-1.5 shrink-0 rounded-full bg-neutral-400" />
                        <span>{item}</span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function PrdSkeleton(): ReactElement {
    return (
        <div className="space-y-5" aria-busy="true" aria-label="Generating PRD">
            <Card className="border-neutral-200/80 shadow-sm dark:border-neutral-800">
                <CardHeader>
                    <div className="flex items-start justify-between gap-3">
                        <div className="flex-1 space-y-2">
                            <Skeleton className="h-5 w-2/3" />
                            <Skeleton className="h-3 w-full" />
                            <Skeleton className="h-3 w-5/6" />
                        </div>
                        <Skeleton className="h-5 w-20 rounded-full" />
                    </div>
                </CardHeader>
                <CardContent className="grid gap-4 md:grid-cols-2">
                    {(['Problem', 'Audience'] as const).map((label) => (
                        <div key={label}>
                            <Skeleton className="h-3 w-16" />
                            <div className="mt-3 space-y-2">
                                <Skeleton className="h-3 w-full" />
                                <Skeleton className="h-3 w-4/5" />
                                <Skeleton className="h-3 w-3/4" />
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <div className="grid gap-5 lg:grid-cols-2">
                {(['Goals', 'Non-goals'] as const).map((label) => (
                    <Card
                        key={label}
                        className="border-neutral-200/80 shadow-sm dark:border-neutral-800"
                    >
                        <CardHeader>
                            <Skeleton className="h-4 w-24" />
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <Skeleton className="h-3 w-full" />
                            <Skeleton className="h-3 w-5/6" />
                            <Skeleton className="h-3 w-3/4" />
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Card className="border-neutral-200/80 shadow-sm dark:border-neutral-800">
                <CardHeader>
                    <Skeleton className="h-4 w-28" />
                </CardHeader>
                <CardContent className="space-y-4">
                    {[0, 1].map((index) => (
                        <div
                            key={index}
                            className="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        >
                            <div className="flex items-start justify-between gap-2">
                                <Skeleton className="h-4 w-1/3" />
                                <Skeleton className="h-4 w-12 rounded-full" />
                            </div>
                            <div className="mt-3 space-y-2">
                                <Skeleton className="h-3 w-full" />
                                <Skeleton className="h-3 w-4/5" />
                            </div>
                            <div className="mt-4 space-y-2">
                                <Skeleton className="h-3 w-2/3" />
                                <Skeleton className="h-3 w-3/5" />
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
        </div>
    );
}
