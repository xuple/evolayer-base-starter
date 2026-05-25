import { Head } from '@inertiajs/react';
import { AlertCircle, LoaderCircle, Sparkles } from 'lucide-react';
import { useState } from 'react';
import type { ReactElement } from 'react';
import ThreadStudioController from '@/actions/Xuple/EvoLayer/Base/Http/Controllers/Ai/ThreadStudioController';
import { StreamingCard } from '@/blocks/streaming-card';
import { VoiceInput } from '@/blocks/voice-input';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { useClipboard } from '@/hooks/use-clipboard';
import type { ThreadStudioError } from '@/hooks/use-thread-studio-stream';
import { useThreadStudioStream } from '@/hooks/use-thread-studio-stream';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';

type ThreadStudioForm = {
    customer_message: string;
    tone: 'balanced' | 'warm' | 'firm';
    provider: ThreadStudioProvider['name'];
    model: string;
};

type ThreadStudioCapability = {
    status: 'supported' | 'experimental' | 'blocked';
    output_mode: 'json_schema' | 'json_object' | 'prompt_json' | 'unsupported';
};

type ThreadStudioProvider = {
    name: 'anthropic' | 'gemini' | 'nvidia' | 'opencode' | 'openrouter';
    label: string;
    model: {
        name: string;
        source: 'provider_default';
    };
    models: ThreadStudioModel[];
    capability: ThreadStudioCapability | null;
};

type ThreadStudioModel = {
    name: string;
    label: string;
    source: 'provider_default' | 'catalogue';
    selectable: boolean;
    default: boolean;
    capability: ThreadStudioCapability | null;
    note: string | null;
};

const capabilityBadgeClass: Record<ThreadStudioCapability['status'], string> = {
    supported:
        'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-400',
    experimental:
        'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-400',
    blocked:
        'border-rose-300 bg-rose-50 text-rose-700 dark:border-rose-900 dark:bg-rose-950/40 dark:text-rose-400',
};

type ThreadStudioPageProps = {
    aiProvider: ThreadStudioProvider;
    aiProviders: ThreadStudioProvider[];
    voiceInputUrl: string | null;
};

const toneOptions: Array<ThreadStudioForm['tone']> = [
    'balanced',
    'warm',
    'firm',
];

const dogfoodExamples: Array<{
    label: string;
    message: string;
    tone: ThreadStudioForm['tone'];
}> = [
    {
        label: 'Install help',
        message:
            "Greetings, now what do I do once I've downloaded EvoLayer Base?",
        tone: 'balanced',
    },
    {
        label: 'Frustrated dev',
        message:
            'Help me install this goddamn framework. Nothing is working and I do not know which command to run next.',
        tone: 'balanced',
    },
    {
        label: 'nginx/HMR issue',
        message:
            'The app loads through the dev.home.arpa URL, but hot reload is broken and the browser keeps trying to connect to the wrong Vite host.',
        tone: 'firm',
    },
];

function defaultModelFor(provider: ThreadStudioProvider): ThreadStudioModel {
    return (
        provider.models.find((model) => model.default && model.selectable) ??
        provider.models.find((model) => model.selectable) ??
        provider.models[0]
    );
}

function StreamErrorAlert({
    error,
}: {
    error: ThreadStudioError;
}): ReactElement {
    const providerSuffix =
        error.provider && error.model
            ? `${error.provider} / ${error.model}`
            : (error.provider ?? null);

    if (error.failureType === 'incomplete_response') {
        return (
            <Alert variant="destructive">
                <AlertCircle />
                <AlertTitle>Partial response</AlertTitle>
                <AlertDescription>
                    <div className="space-y-2">
                        <p>
                            The provider returned a reply but it was missing or
                            invalid in places.
                        </p>
                        {error.missingFields.length > 0 ? (
                            <p>
                                <span className="font-medium">Missing:</span>{' '}
                                {error.missingFields.join(', ')}
                            </p>
                        ) : null}
                        {error.invalidFields.length > 0 ? (
                            <p>
                                <span className="font-medium">Invalid:</span>{' '}
                                {error.invalidFields
                                    .map((field) => field.field)
                                    .join(', ')}
                            </p>
                        ) : null}
                        {providerSuffix ? (
                            <p className="text-xs opacity-80">
                                Provider {providerSuffix}
                            </p>
                        ) : null}
                    </div>
                </AlertDescription>
            </Alert>
        );
    }

    if (error.failureType === 'malformed_response') {
        return (
            <Alert variant="destructive">
                <AlertCircle />
                <AlertTitle>Unstructured output</AlertTitle>
                <AlertDescription>
                    <div className="space-y-2">
                        <p>
                            The provider responded but the payload was not a
                            structured ThreadStudio reply. Try regenerating.
                        </p>
                        {providerSuffix ? (
                            <p className="text-xs opacity-80">
                                Provider {providerSuffix}
                            </p>
                        ) : null}
                    </div>
                </AlertDescription>
            </Alert>
        );
    }

    return (
        <Alert variant="destructive">
            <AlertCircle />
            <AlertTitle>Provider unavailable</AlertTitle>
            <AlertDescription>
                <div className="space-y-2">
                    <p>
                        {error.message ||
                            'The provider call failed. Retry in a moment.'}
                    </p>
                    {providerSuffix ? (
                        <p className="text-xs opacity-80">
                            Provider {providerSuffix}
                        </p>
                    ) : null}
                </div>
            </AlertDescription>
        </Alert>
    );
}

export default function ThreadStudioPage({
    aiProvider,
    aiProviders,
    voiceInputUrl,
}: ThreadStudioPageProps) {
    const stream = useThreadStudioStream();
    const [formData, setFormData] = useState<ThreadStudioForm>({
        customer_message: '',
        tone: 'balanced',
        provider: aiProvider.name,
        model: defaultModelFor(aiProvider).name,
    });
    const [validationError, setValidationError] = useState<string | null>(null);
    const {
        processing,
        result,
        streamingFields,
        invocation,
        error: streamError,
        clearResult,
    } = stream;

    const [copiedText, copy] = useClipboard();
    const selectedProvider =
        aiProviders.find((provider) => provider.name === formData.provider) ??
        aiProvider;
    const selectedModel =
        selectedProvider.models.find(
            (model) => model.name === formData.model,
        ) ?? defaultModelFor(selectedProvider);
    const selectedCapability =
        selectedModel.capability ?? selectedProvider.capability;

    async function composeWithTone(
        tone: ThreadStudioForm['tone'],
    ): Promise<void> {
        setValidationError(null);

        if (formData.customer_message.trim().length < 10) {
            setValidationError(
                'The customer message must be at least 10 characters.',
            );

            return;
        }

        await stream.submit({
            customer_message: formData.customer_message,
            model: selectedModel.name,
            provider: formData.provider,
            tone,
        });
    }

    return (
        <>
            <Head title="ThreadStudio" />

            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col overflow-y-auto px-6 py-8 md:px-10 lg:px-12">
                <div className="mb-8 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <Heading
                        title="ThreadStudio"
                        description="Dogfood EvoLayer Base support: turn rough starter-kit setup, install, and local-dev messages into a polished reply plus an internal handoff note."
                    />
                    <div className="rounded-lg border border-neutral-200/80 bg-neutral-50 px-4 py-3 text-sm shadow-sm dark:border-neutral-800 dark:bg-neutral-950">
                        <div className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                            Provider path
                        </div>
                        <div className="mt-2 flex items-center gap-2">
                            <Badge variant="outline">
                                {selectedProvider.label}
                            </Badge>
                            <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                {selectedProvider.name}
                            </span>
                        </div>
                        <div className="mt-3 text-xs text-neutral-600 dark:text-neutral-300">
                            <span className="font-medium">
                                {selectedModel.name}
                            </span>
                            <span className="text-neutral-500 dark:text-neutral-400">
                                {' '}
                                ({selectedModel.source})
                            </span>
                        </div>
                        {selectedCapability ? (
                            <div className="mt-2">
                                <span
                                    className={cn(
                                        'inline-flex items-center gap-1.5 rounded-full border px-2 py-0.5 text-[10px] font-medium tracking-wide uppercase',
                                        capabilityBadgeClass[
                                            selectedCapability.status
                                        ],
                                    )}
                                >
                                    {selectedCapability.status}
                                    <span className="font-normal opacity-70">
                                        · {selectedCapability.output_mode}
                                    </span>
                                </span>
                            </div>
                        ) : null}
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <Card className="border-neutral-200/80 shadow-sm dark:border-neutral-800">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Sparkles className="h-4 w-4 text-brand" />
                                Compose a support-ready answer
                            </CardTitle>
                            <CardDescription>
                                Use a real EvoLayer Base support message. The
                                model will summarize it, classify tone and
                                urgency, draft a customer reply, and write an
                                internal note.
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-5">
                            <div className="rounded-lg border border-dashed border-neutral-300 bg-neutral-50/70 p-3 dark:border-neutral-800 dark:bg-neutral-950/60">
                                <div className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                                    Dogfood examples
                                </div>
                                <div className="mt-3 flex flex-wrap gap-2">
                                    {dogfoodExamples.map((example) => (
                                        <Button
                                            key={example.label}
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => {
                                                setFormData((prev) => ({
                                                    ...prev,
                                                    customer_message:
                                                        example.message,
                                                    tone: example.tone,
                                                }));
                                                setValidationError(null);
                                                clearResult();
                                            }}
                                        >
                                            {example.label}
                                        </Button>
                                    ))}
                                </div>
                                <p className="mt-3 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    These examples keep the showcase anchored in
                                    the starter itself: setup, install, and
                                    nginx-aware local development support. The
                                    public reference path is
                                    docs.evodevops.com/base.
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <label
                                    htmlFor="provider"
                                    className="text-sm font-medium"
                                >
                                    Provider path
                                </label>
                                <Select
                                    value={formData.provider}
                                    onValueChange={(provider) => {
                                        const nextProvider =
                                            aiProviders.find(
                                                (candidate) =>
                                                    candidate.name === provider,
                                            ) ?? aiProvider;

                                        setFormData((prev) => ({
                                            ...prev,
                                            provider:
                                                provider as ThreadStudioForm['provider'],
                                            model: defaultModelFor(nextProvider)
                                                .name,
                                        }));
                                        setValidationError(null);
                                        clearResult();
                                    }}
                                >
                                    <SelectTrigger
                                        id="provider"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Select provider" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {aiProviders.map((provider) => (
                                            <SelectItem
                                                key={provider.name}
                                                value={provider.name}
                                            >
                                                {provider.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    Defaults to your configured
                                    AI_THREAD_STUDIO_PROVIDER, but can be
                                    switched here to test another configured
                                    provider path.
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <label
                                    htmlFor="model"
                                    className="text-sm font-medium"
                                >
                                    Model
                                </label>
                                <Select
                                    value={selectedModel.name}
                                    onValueChange={(model) => {
                                        setFormData((prev) => ({
                                            ...prev,
                                            model,
                                        }));
                                        setValidationError(null);
                                        clearResult();
                                    }}
                                >
                                    <SelectTrigger
                                        id="model"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Select model" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {selectedProvider.models.map(
                                            (model) => (
                                                <SelectItem
                                                    key={model.name}
                                                    value={model.name}
                                                    disabled={!model.selectable}
                                                >
                                                    <span className="flex min-w-0 flex-col items-start gap-0.5">
                                                        <span className="truncate">
                                                            {model.label}
                                                        </span>
                                                        {model.capability ? (
                                                            <span className="text-[10px] tracking-wide text-neutral-500 uppercase">
                                                                {
                                                                    model
                                                                        .capability
                                                                        .status
                                                                }{' '}
                                                                ·{' '}
                                                                {
                                                                    model
                                                                        .capability
                                                                        .output_mode
                                                                }
                                                            </span>
                                                        ) : null}
                                                    </span>
                                                </SelectItem>
                                            ),
                                        )}
                                    </SelectContent>
                                </Select>
                                <p className="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                    Models are scoped to the selected provider.
                                    Provider defaults come from each provider's
                                    configured chat model; unsupported OpenCode
                                    strategies are visible but disabled.
                                </p>
                                {selectedModel.note ? (
                                    <p className="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                        {selectedModel.note}
                                    </p>
                                ) : null}
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center justify-between gap-3">
                                    <label
                                        htmlFor="customer_message"
                                        className="text-sm font-medium"
                                    >
                                        Customer message
                                    </label>
                                    {voiceInputUrl && (
                                        <VoiceInput
                                            size="sm"
                                            label="Dictate"
                                            disabled={processing}
                                            transcribeUrl={voiceInputUrl}
                                            onTranscribed={(text) =>
                                                setFormData((prev) => ({
                                                    ...prev,
                                                    customer_message:
                                                        prev.customer_message
                                                            ? `${prev.customer_message.trimEnd()} ${text}`
                                                            : text,
                                                }))
                                            }
                                            onError={(message) =>
                                                setValidationError(message)
                                            }
                                        />
                                    )}
                                </div>
                                <textarea
                                    id="customer_message"
                                    value={formData.customer_message}
                                    onChange={(event) =>
                                        setFormData((prev) => ({
                                            ...prev,
                                            customer_message:
                                                event.target.value,
                                        }))
                                    }
                                    className={cn(
                                        'min-h-72 w-full rounded-md border border-input bg-transparent px-3 py-3 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                    )}
                                    placeholder="Paste a rough EvoLayer Base install, setup, or local-dev support message here..."
                                />
                                <InputError
                                    message={validationError ?? undefined}
                                />
                            </div>

                            <div className="grid gap-2">
                                <span
                                    id="thread-studio-tone-label"
                                    className="text-sm font-medium"
                                >
                                    Preferred tone
                                </span>
                                <div
                                    role="radiogroup"
                                    aria-labelledby="thread-studio-tone-label"
                                    className="flex flex-wrap gap-2"
                                >
                                    {toneOptions.map((tone) => (
                                        <Button
                                            key={tone}
                                            type="button"
                                            role="radio"
                                            aria-checked={
                                                formData.tone === tone
                                            }
                                            variant={
                                                formData.tone === tone
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            onClick={() =>
                                                setFormData((prev) => ({
                                                    ...prev,
                                                    tone,
                                                }))
                                            }
                                        >
                                            {tone}
                                        </Button>
                                    ))}
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                <Button
                                    type="button"
                                    disabled={processing}
                                    onClick={() =>
                                        composeWithTone(formData.tone)
                                    }
                                >
                                    {processing ? (
                                        <>
                                            <LoaderCircle className="h-4 w-4 animate-spin" />
                                            Generating
                                        </>
                                    ) : (
                                        'Generate reply set'
                                    )}
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={
                                        processing ||
                                        formData.customer_message.trim() === ''
                                    }
                                    onClick={() => composeWithTone('warm')}
                                >
                                    Regenerate warmer
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={
                                        processing ||
                                        formData.customer_message.trim() === ''
                                    }
                                    onClick={() => composeWithTone('firm')}
                                >
                                    Regenerate firmer
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        {validationError && !streamError && !result ? (
                            <Alert variant="destructive">
                                <AlertCircle />
                                <AlertTitle>
                                    Could not compose a reply
                                </AlertTitle>
                                <AlertDescription>
                                    Fix the validation issues or retry when the
                                    provider is available again.
                                </AlertDescription>
                            </Alert>
                        ) : null}

                        {streamError && !result ? (
                            <StreamErrorAlert error={streamError} />
                        ) : null}

                        <Card className="border-neutral-200/80 shadow-sm dark:border-neutral-800">
                            <CardHeader>
                                <CardTitle>Thread readout</CardTitle>
                                <CardDescription>
                                    A compressed snapshot of the message before
                                    you send anything.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {processing ? (
                                    <div className="space-y-3">
                                        <div className="flex flex-wrap gap-2">
                                            <Skeleton className="h-5 w-14 rounded-full" />
                                            <Skeleton className="h-5 w-20 rounded-full" />
                                            <Skeleton className="h-5 w-28 rounded-full" />
                                        </div>
                                        <div>
                                            <Skeleton className="mb-2 h-3 w-16" />
                                            <Skeleton className="h-4 w-full" />
                                            <Skeleton className="mt-1 h-4 w-4/5" />
                                        </div>
                                    </div>
                                ) : result ||
                                  streamingFields.summary ? (
                                    <>
                                        <div className="flex flex-wrap gap-2">
                                            {(result?.urgency ??
                                                streamingFields.urgency) && (
                                                <Badge>
                                                    {result?.urgency ??
                                                        streamingFields.urgency}
                                                </Badge>
                                            )}
                                            {(result?.sentiment ??
                                                streamingFields.sentiment) && (
                                                <Badge variant="secondary">
                                                    {result?.sentiment ??
                                                        streamingFields.sentiment}
                                                </Badge>
                                            )}
                                            {(result?.recommended_tone ??
                                                streamingFields.recommended_tone) && (
                                                <Badge variant="outline">
                                                    {result?.recommended_tone ??
                                                        streamingFields.recommended_tone}
                                                </Badge>
                                            )}
                                        </div>
                                        <div>
                                            <div className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                                                Summary
                                            </div>
                                            <p className="mt-2 text-sm leading-7 text-neutral-700 dark:text-neutral-300">
                                                {result?.summary ??
                                                    streamingFields.summary}
                                                {processing &&
                                                    streamingFields.summary &&
                                                    !result && (
                                                        <span className="ml-px animate-pulse text-neutral-400">
                                                            |
                                                        </span>
                                                    )}
                                            </p>
                                        </div>
                                    </>
                                ) : (
                                    <p className="text-sm leading-7 text-neutral-500 dark:text-neutral-400">
                                        No result yet. Paste a message, choose a
                                        tone, and generate a reply set.
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        <StreamingCard
                            title="Customer reply"
                            description="This is the sendable version for the customer."
                            text={
                                result?.customer_reply ??
                                streamingFields.customer_reply ??
                                null
                            }
                            processing={processing}
                            streaming={
                                processing &&
                                Boolean(streamingFields.customer_reply)
                            }
                            skeletonLines={4}
                            emptyText="Generate a reply to see the customer-facing draft here."
                            onCopy={
                                result?.customer_reply
                                    ? () => copy(result.customer_reply)
                                    : undefined
                            }
                            copyLabel={
                                copiedText === result?.customer_reply
                                    ? 'Copied'
                                    : 'Copy'
                            }
                            copyDisabled={!result?.customer_reply}
                        />

                        <StreamingCard
                            title="Internal note"
                            description="Handoff language for the team behind the scenes."
                            text={
                                result?.internal_note ??
                                streamingFields.internal_note ??
                                null
                            }
                            processing={processing}
                            streaming={
                                processing &&
                                Boolean(streamingFields.internal_note)
                            }
                            skeletonLines={3}
                            emptyText="Generate a reply to see the internal note here."
                            onCopy={
                                result?.internal_note
                                    ? () => copy(result.internal_note)
                                    : undefined
                            }
                            copyLabel={
                                copiedText === result?.internal_note
                                    ? 'Copied'
                                    : 'Copy'
                            }
                            copyDisabled={!result?.internal_note}
                        />

                        {invocation && result ? (
                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                Invocation{' '}
                                <span className="font-mono">
                                    {invocation.id}
                                </span>
                                {invocation.durationMs !== null ? (
                                    <>
                                        {' · '}
                                        {(invocation.durationMs / 1000).toFixed(
                                            2,
                                        )}
                                        s
                                    </>
                                ) : null}
                                {' · '}
                                {selectedProvider.name} / {selectedModel.name}
                            </p>
                        ) : null}
                    </div>
                </div>
            </div>
        </>
    );
}

ThreadStudioPage.layout = (page: ReactElement) => (
    <AppLayout
        breadcrumbs={[
            { title: 'ThreadStudio', href: ThreadStudioController.show() },
        ]}
    >
        {page}
    </AppLayout>
);
