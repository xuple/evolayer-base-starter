import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AlertCircle,
    LoaderCircle,
    PaperclipIcon,
    Sparkles,
} from 'lucide-react';
import { useState } from 'react';
import type { ReactElement } from 'react';
import InboxController from '@/actions/Xuple/EvoLayer/Base/Http/Controllers/Admin/InboxController';
import SubmissionsController from '@/actions/Xuple/EvoLayer/Base/Http/Controllers/Admin/SubmissionsController';
import { AiTriage } from '@/blocks/ai-triage';
import { SemanticSearch } from '@/blocks/semantic-search';
import { StreamingCard } from '@/blocks/streaming-card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';
import { useThreadStudioStream } from '@/hooks/use-thread-studio-stream';
import { useTypewriter } from '@/hooks/use-typewriter';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';

// ── Types ──────────────────────────────────────────────────────────────────

type Tag = { name: { en: string } | string };

type InboxSubmission = {
    id: string;
    type: string;
    status: string;
    triage_urgency: 'low' | 'medium' | 'high' | null;
    triage_sentiment: 'positive' | 'neutral' | 'negative' | null;
    first_name: string;
    last_name: string;
    email: string;
    subject: string;
    created_at: string;
    tags: Tag[];
};

type SelectedSubmission = InboxSubmission & {
    phone: string | null;
    message: string;
    triage_summary: string | null;
    user: { id: number; name: string; email: string } | null;
};

type ActivityEntry = {
    id: number;
    description: string;
    causer: string | null;
    created_at: string;
};

type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Attachment = {
    id: number;
    file_name: string;
    mime_type: string;
    size: string;
    url: string;
    ai_analysis: string | null;
};

type Props = {
    submissions: Paginator<InboxSubmission>;
    selected: SelectedSubmission | null;
    attachments: Attachment[];
    activity: ActivityEntry[];
    canCompose: boolean;
};

// ── Helpers ────────────────────────────────────────────────────────────────

function tagName(tag: Tag): string {
    return typeof tag.name === 'string' ? tag.name : (tag.name.en ?? '');
}

function relativeTime(iso: string): string {
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);

    if (mins < 60) {
        return `${mins}m ago`;
    }

    const hours = Math.floor(mins / 60);

    if (hours < 24) {
        return `${hours}h ago`;
    }

    return new Date(iso).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'short',
    });
}

const urgencyVariant: Record<string, 'destructive' | 'default' | 'secondary'> =
    {
        high: 'destructive',
        medium: 'default',
        low: 'secondary',
    };

const sentimentVariant: Record<
    string,
    'secondary' | 'outline' | 'destructive'
> = {
    positive: 'secondary',
    neutral: 'outline',
    negative: 'destructive',
};

// ── Compose panel ──────────────────────────────────────────────────────────

function ComposePanel({ message }: { message: string }) {
    const stream = useThreadStudioStream();
    const [tone, setTone] = useState<'balanced' | 'warm' | 'firm'>('balanced');
    const [customerMessage, setCustomerMessage] = useState(message);
    const [validationError, setValidationError] = useState<string | null>(null);
    const { processing, result, error: streamError, clearResult } = stream;
    const [, copy] = useClipboard();

    const replyText = useTypewriter(result?.customer_reply ?? null);
    const noteText = useTypewriter(result?.internal_note ?? null);

    async function handleGenerate() {
        setValidationError(null);
        clearResult();

        if (customerMessage.trim().length < 10) {
            setValidationError('Message must be at least 10 characters.');

            return;
        }

        await stream.submit({ customer_message: customerMessage, tone });
    }

    const toneOptions: Array<typeof tone> = ['balanced', 'warm', 'firm'];
    const errorMessage = validationError ?? streamError?.message ?? null;

    return (
        <div className="flex h-full flex-col">
            <div className="border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                <div className="flex items-center gap-2">
                    <Sparkles className="size-3.5 text-brand" />
                    <span className="text-xs font-semibold tracking-[0.14em] text-neutral-500 uppercase dark:text-neutral-400">
                        ThreadStudio
                    </span>
                </div>
            </div>

            <div className="flex-1 space-y-4 overflow-y-auto p-4">
                <div className="flex flex-col gap-1.5">
                    <label className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                        Customer message
                    </label>
                    <textarea
                        value={customerMessage}
                        onChange={(e) => {
                            setCustomerMessage(e.target.value);
                            clearResult();
                        }}
                        rows={5}
                        className="w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-xs shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                    />
                </div>

                <div className="flex flex-col gap-1.5">
                    <span className="text-xs font-medium text-neutral-600 dark:text-neutral-400">
                        Tone
                    </span>
                    <div className="flex gap-1.5">
                        {toneOptions.map((t) => (
                            <button
                                key={t}
                                onClick={() => setTone(t)}
                                className={cn(
                                    'flex-1 rounded-md border px-2 py-1 text-xs font-medium transition-colors',
                                    tone === t
                                        ? 'border-brand bg-brand/10 text-brand'
                                        : 'border-neutral-200 text-neutral-600 hover:border-neutral-300 dark:border-neutral-700 dark:text-neutral-400',
                                )}
                            >
                                {t.charAt(0).toUpperCase() + t.slice(1)}
                            </button>
                        ))}
                    </div>
                </div>

                <Button
                    onClick={() => void handleGenerate()}
                    disabled={processing}
                    className="w-full"
                    size="sm"
                >
                    {processing ? (
                        <>
                            <LoaderCircle className="size-3.5 animate-spin" />
                            Composing…
                        </>
                    ) : (
                        <>
                            <Sparkles className="size-3.5" />
                            Generate reply
                        </>
                    )}
                </Button>

                {errorMessage && !result && (
                    <Alert variant="destructive">
                        <AlertCircle className="size-4" />
                        <AlertDescription className="text-xs">
                            {errorMessage}
                        </AlertDescription>
                    </Alert>
                )}

                {(processing || result) && (
                    <div className="space-y-3">
                        <StreamingCard
                            density="compact"
                            title="Customer reply"
                            text={replyText}
                            processing={processing}
                            skeletonLines={3}
                            onCopy={
                                result
                                    ? () => copy(result.customer_reply)
                                    : undefined
                            }
                        />

                        <StreamingCard
                            density="compact"
                            title="Internal note"
                            text={noteText}
                            processing={processing}
                            skeletonLines={2}
                            onCopy={
                                result
                                    ? () => copy(result.internal_note)
                                    : undefined
                            }
                        />
                    </div>
                )}
            </div>
        </div>
    );
}

// ── Detail panel ───────────────────────────────────────────────────────────

function DetailPanel({
    submission,
    attachments,
    activity,
}: {
    submission: SelectedSubmission;
    attachments: Attachment[];
    activity: ActivityEntry[];
}) {
    const markRead = useForm({});
    const archive = useForm({});

    return (
        <div className="flex h-full flex-col overflow-y-auto">
            <div className="border-b border-sidebar-border/70 px-6 py-4 dark:border-sidebar-border">
                <div className="flex items-start justify-between gap-4">
                    <div className="min-w-0">
                        <h2 className="truncate text-base font-semibold text-neutral-900 dark:text-neutral-100">
                            {submission.subject}
                        </h2>
                        <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                            {submission.first_name} {submission.last_name} ·{' '}
                            {submission.email}
                        </p>
                    </div>
                    <Badge
                        variant={
                            submission.status === 'new'
                                ? 'default'
                                : submission.status === 'read'
                                  ? 'secondary'
                                  : 'outline'
                        }
                    >
                        {submission.status}
                    </Badge>
                </div>
            </div>

            <div className="flex-1 px-6 py-5">
                <p className="text-sm leading-7 whitespace-pre-wrap text-neutral-700 dark:text-neutral-300">
                    {submission.message}
                </p>

                {attachments.length > 0 && (
                    <ul className="mt-4 space-y-2">
                        {attachments.map((attachment) => (
                            <li
                                key={attachment.id}
                                className="rounded-md border border-sidebar-border/70 dark:border-sidebar-border"
                            >
                                <a
                                    href={attachment.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="flex items-center gap-2 px-3 py-2 text-sm transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900"
                                >
                                    <PaperclipIcon className="size-3.5 shrink-0 text-neutral-400" />
                                    <span className="min-w-0 flex-1 truncate text-neutral-900 dark:text-neutral-100">
                                        {attachment.file_name}
                                    </span>
                                    <span className="shrink-0 text-xs text-neutral-400">
                                        {attachment.size}
                                    </span>
                                </a>
                                {attachment.ai_analysis && (
                                    <p className="border-t border-sidebar-border/70 px-3 py-2 text-xs leading-relaxed text-neutral-500 dark:border-sidebar-border dark:text-neutral-400">
                                        {attachment.ai_analysis}
                                    </p>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {submission.triage_urgency && (
                <div className="border-t border-sidebar-border/70 px-6 py-4 dark:border-sidebar-border">
                    <AiTriage
                        urgency={submission.triage_urgency}
                        sentiment={submission.triage_sentiment}
                        tags={submission.tags}
                        summary={submission.triage_summary}
                    />
                </div>
            )}

            <div className="flex gap-2 border-t border-sidebar-border/70 px-6 py-4 dark:border-sidebar-border">
                {submission.status !== 'read' && (
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            markRead.patch(
                                SubmissionsController.markRead.url({
                                    submission: submission.id,
                                }),
                            )
                        }
                        disabled={markRead.processing}
                    >
                        Mark as read
                    </Button>
                )}
                {submission.status !== 'archived' && (
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            archive.patch(
                                SubmissionsController.archive.url({
                                    submission: submission.id,
                                }),
                            )
                        }
                        disabled={archive.processing}
                    >
                        Archive
                    </Button>
                )}
            </div>

            {activity.length > 0 && (
                <div className="border-t border-sidebar-border/70 px-6 py-4 dark:border-sidebar-border">
                    <p className="mb-3 text-xs font-semibold tracking-[0.14em] text-neutral-500 uppercase dark:text-neutral-400">
                        Activity
                    </p>
                    <ol className="space-y-2.5">
                        {activity.map((entry) => (
                            <li key={entry.id} className="flex gap-2.5 text-xs">
                                <span className="mt-1.5 size-1.5 shrink-0 rounded-full bg-neutral-300 dark:bg-neutral-600" />
                                <div>
                                    <span className="text-neutral-700 dark:text-neutral-300">
                                        {entry.description}
                                    </span>
                                    <span className="ml-1.5 text-neutral-400 dark:text-neutral-500">
                                        {entry.causer ?? 'System'} ·{' '}
                                        {relativeTime(entry.created_at)}
                                    </span>
                                </div>
                            </li>
                        ))}
                    </ol>
                </div>
            )}
        </div>
    );
}

// ── Empty state ────────────────────────────────────────────────────────────

function EmptyDetail() {
    return (
        <div className="flex h-full items-center justify-center">
            <div className="text-center">
                <div className="mx-auto mb-3 flex size-12 items-center justify-center rounded-2xl bg-neutral-100 dark:bg-neutral-800">
                    <Sparkles className="size-5 text-neutral-400" />
                </div>
                <p className="text-sm font-medium text-neutral-600 dark:text-neutral-400">
                    Select a submission
                </p>
                <p className="mt-1 text-xs text-neutral-400 dark:text-neutral-500">
                    Choose from the list to review and compose a reply
                </p>
            </div>
        </div>
    );
}

// ── Main page ──────────────────────────────────────────────────────────────

export default function InboxPage({
    submissions,
    selected,
    attachments,
    activity,
    canCompose,
}: Props) {
    const panelCols = canCompose
        ? 'lg:grid-cols-[280px_1fr_320px]'
        : 'lg:grid-cols-[280px_1fr]';

    return (
        <>
            <Head title="Inbox" />

            <div
                className={cn(
                    'grid h-full divide-x divide-sidebar-border/70 dark:divide-sidebar-border',
                    panelCols,
                )}
            >
                {/* Left: submission list */}
                <div className="flex flex-col overflow-hidden">
                    <div className="flex flex-col gap-3 border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                        <div>
                            <h1 className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                Inbox
                            </h1>
                            <p className="text-xs text-neutral-500 dark:text-neutral-400">
                                {submissions.total} submissions
                            </p>
                        </div>
                        <SemanticSearch
                            searchUrl={InboxController.search.url()}
                            placeholder="Search inbox"
                            onSelect={(result) =>
                                router.visit(
                                    InboxController.detail.url({
                                        submission: result.id,
                                    }),
                                )
                            }
                        />
                    </div>

                    <div className="flex-1 divide-y divide-sidebar-border/40 overflow-y-auto dark:divide-sidebar-border">
                        {submissions.data.length === 0 ? (
                            <div className="px-4 py-8 text-center text-xs text-neutral-400">
                                No submissions yet.
                            </div>
                        ) : (
                            submissions.data.map((submission) => (
                                <Link
                                    key={submission.id}
                                    href={InboxController.detail.url({
                                        submission: submission.id,
                                    })}
                                    className={cn(
                                        'block px-4 py-3 transition-colors hover:bg-neutral-50/60 dark:hover:bg-neutral-900/40',
                                        selected?.id === submission.id &&
                                            'border-r-2 border-r-brand bg-brand/5',
                                    )}
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <p
                                            className={cn(
                                                'truncate text-sm',
                                                submission.status === 'new'
                                                    ? 'font-semibold text-neutral-900 dark:text-neutral-100'
                                                    : 'font-normal text-neutral-600 dark:text-neutral-400',
                                            )}
                                        >
                                            {submission.first_name}{' '}
                                            {submission.last_name}
                                        </p>
                                        <span className="shrink-0 text-xs text-neutral-400 dark:text-neutral-500">
                                            {relativeTime(
                                                submission.created_at,
                                            )}
                                        </span>
                                    </div>
                                    <p className="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-400">
                                        {submission.subject}
                                    </p>
                                    <div className="mt-1.5 flex flex-wrap items-center gap-1">
                                        {submission.triage_urgency && (
                                            <Badge
                                                variant={
                                                    urgencyVariant[
                                                        submission
                                                            .triage_urgency
                                                    ]
                                                }
                                                className="px-1.5 py-0 text-[10px]"
                                            >
                                                {submission.triage_urgency}
                                            </Badge>
                                        )}
                                        {submission.triage_sentiment && (
                                            <Badge
                                                variant={
                                                    sentimentVariant[
                                                        submission
                                                            .triage_sentiment
                                                    ]
                                                }
                                                className="px-1.5 py-0 text-[10px]"
                                            >
                                                {submission.triage_sentiment}
                                            </Badge>
                                        )}
                                        {submission.tags
                                            .slice(0, 2)
                                            .map((tag) => (
                                                <Badge
                                                    key={tagName(tag)}
                                                    variant="outline"
                                                    className="px-1.5 py-0 text-[10px]"
                                                >
                                                    {tagName(tag)}
                                                </Badge>
                                            ))}
                                    </div>
                                </Link>
                            ))
                        )}
                    </div>

                    {submissions.last_page > 1 && (
                        <div className="flex justify-between border-t border-sidebar-border/70 px-3 py-2 dark:border-sidebar-border">
                            <Button
                                variant="ghost"
                                size="sm"
                                asChild
                                disabled={!submissions.prev_page_url}
                            >
                                {submissions.prev_page_url ? (
                                    <Link href={submissions.prev_page_url}>
                                        ← Prev
                                    </Link>
                                ) : (
                                    <span>← Prev</span>
                                )}
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                asChild
                                disabled={!submissions.next_page_url}
                            >
                                {submissions.next_page_url ? (
                                    <Link href={submissions.next_page_url}>
                                        Next →
                                    </Link>
                                ) : (
                                    <span>Next →</span>
                                )}
                            </Button>
                        </div>
                    )}
                </div>

                {/* Center: detail */}
                <div className="overflow-hidden">
                    {selected ? (
                        <DetailPanel
                            submission={selected}
                            attachments={attachments}
                            activity={activity}
                        />
                    ) : (
                        <EmptyDetail />
                    )}
                </div>

                {/* Right: compose (admin only) */}
                {canCompose && (
                    <div className="overflow-hidden">
                        {selected ? (
                            <ComposePanel
                                key={selected.id}
                                message={selected.message}
                            />
                        ) : (
                            <div className="flex h-full items-center justify-center">
                                <p className="text-xs text-neutral-400 dark:text-neutral-500">
                                    Select a submission to compose
                                </p>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

InboxPage.layout = (page: ReactElement) => (
    <AppLayout
        breadcrumbs={[{ title: 'Inbox', href: InboxController.show.url() }]}
    >
        {page}
    </AppLayout>
);
