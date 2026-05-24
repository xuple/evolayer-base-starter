import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon, PaperclipIcon } from 'lucide-react';
import SubmissionsController from '@/actions/EvoDevOps/Base/Http/Controllers/Admin/SubmissionsController';
import { AiTriage } from '@/blocks/ai-triage';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import evodevops from '@/routes/evodevops';
import type { AppLayoutPageProps } from '@/types/layout';

type ActivityEntry = {
    id: number;
    description: string;
    causer: string | null;
    properties: Record<string, unknown>;
    created_at: string;
};

type Submission = {
    id: string;
    type: string;
    status: string;
    triage_urgency: 'low' | 'medium' | 'high' | null;
    triage_sentiment: 'positive' | 'neutral' | 'negative' | null;
    triage_summary: string | null;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    subject: string;
    message: string;
    created_at: string;
    tags: { name: { en: string } | string }[];
    user: { id: number; name: string; email: string } | null;
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
    submission: Submission;
    attachments: Attachment[];
    activity: ActivityEntry[];
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    new: 'default',
    read: 'secondary',
    archived: 'outline',
};

function formatActivityTime(iso: string): string {
    return new Date(iso).toLocaleString('en-GB', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export default function SubmissionsShow({ submission, attachments, activity }: Props) {
    const markRead = useForm({});
    const archive = useForm({});

    function handleMarkRead() {
        markRead.patch(
            SubmissionsController.markRead.url({ submission: submission.id }),
        );
    }

    function handleArchive() {
        archive.patch(
            SubmissionsController.archive.url({ submission: submission.id }),
        );
    }

    return (
        <>
            <Head title={submission.subject} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={evodevops.base.admin.submissions.index.url()}>
                            <ArrowLeftIcon className="size-4" />
                            Submissions
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                    <div className="flex flex-col gap-4">
                        <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-950">
                            <div className="mb-4 flex items-start justify-between gap-4">
                                <h1 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                    {submission.subject}
                                </h1>
                                <Badge
                                    variant={
                                        statusVariant[submission.status] ??
                                        'outline'
                                    }
                                >
                                    {submission.status}
                                </Badge>
                            </div>
                            <p className="text-sm leading-7 whitespace-pre-wrap text-neutral-700 dark:text-neutral-300">
                                {submission.message}
                            </p>
                        </div>

                        {attachments.length > 0 && (
                            <div className="rounded-xl border border-sidebar-border/70 bg-white p-6 dark:border-sidebar-border dark:bg-neutral-950">
                                <h2 className="mb-4 text-xs font-semibold tracking-[0.16em] text-neutral-500 uppercase dark:text-neutral-400">
                                    Attachments
                                </h2>
                                <ul className="space-y-3">
                                    {attachments.map((attachment) => (
                                        <li
                                            key={attachment.id}
                                            className="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
                                        >
                                            <a
                                                href={attachment.url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="flex items-center gap-3 px-4 py-3 text-sm transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900"
                                            >
                                                <PaperclipIcon className="size-4 shrink-0 text-neutral-400" />
                                                <span className="min-w-0 flex-1 truncate font-medium text-neutral-900 dark:text-neutral-100">
                                                    {attachment.file_name}
                                                </span>
                                                <span className="shrink-0 text-xs text-neutral-400">
                                                    {attachment.size}
                                                </span>
                                            </a>
                                            {attachment.ai_analysis && (
                                                <p className="border-t border-sidebar-border/70 px-4 py-2.5 text-xs leading-relaxed text-neutral-500 dark:border-sidebar-border dark:text-neutral-400">
                                                    {attachment.ai_analysis}
                                                </p>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>

                    <div className="flex flex-col gap-4">
                        <div className="rounded-xl border border-sidebar-border/70 bg-white p-5 dark:border-sidebar-border dark:bg-neutral-950">
                            <h2 className="mb-4 text-xs font-semibold tracking-[0.16em] text-neutral-500 uppercase dark:text-neutral-400">
                                Sender
                            </h2>
                            <dl className="space-y-3 text-sm">
                                <div>
                                    <dt className="text-neutral-500 dark:text-neutral-400">
                                        Name
                                    </dt>
                                    <dd className="font-medium text-neutral-900 dark:text-neutral-100">
                                        {submission.first_name}{' '}
                                        {submission.last_name}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-neutral-500 dark:text-neutral-400">
                                        Email
                                    </dt>
                                    <dd>
                                        <a
                                            href={`mailto:${submission.email}`}
                                            className="text-brand hover:underline"
                                        >
                                            {submission.email}
                                        </a>
                                    </dd>
                                </div>
                                {submission.phone && (
                                    <div>
                                        <dt className="text-neutral-500 dark:text-neutral-400">
                                            Phone
                                        </dt>
                                        <dd>
                                            <a
                                                href={`tel:${submission.phone}`}
                                                className="text-neutral-700 hover:text-brand dark:text-neutral-300"
                                            >
                                                {submission.phone}
                                            </a>
                                        </dd>
                                    </div>
                                )}
                                {submission.user && (
                                    <div>
                                        <dt className="text-neutral-500 dark:text-neutral-400">
                                            Account
                                        </dt>
                                        <dd className="text-neutral-700 dark:text-neutral-300">
                                            {submission.user.name}
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </div>

                        <div className="rounded-xl border border-sidebar-border/70 bg-white p-5 dark:border-sidebar-border dark:bg-neutral-950">
                            <h2 className="mb-4 text-xs font-semibold tracking-[0.16em] text-neutral-500 uppercase dark:text-neutral-400">
                                Details
                            </h2>
                            <dl className="space-y-3 text-sm">
                                <div>
                                    <dt className="text-neutral-500 dark:text-neutral-400">
                                        Type
                                    </dt>
                                    <dd className="text-neutral-700 capitalize dark:text-neutral-300">
                                        {submission.type}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-neutral-500 dark:text-neutral-400">
                                        Received
                                    </dt>
                                    <dd className="text-neutral-700 dark:text-neutral-300">
                                        {new Date(
                                            submission.created_at,
                                        ).toLocaleDateString('en-GB', {
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric',
                                        })}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <AiTriage
                            variant="detailed"
                            urgency={submission.triage_urgency}
                            sentiment={submission.triage_sentiment}
                            tags={submission.tags}
                            summary={submission.triage_summary}
                        />

                        <div className="flex flex-col gap-2">
                            {submission.status !== 'read' && (
                                <Button
                                    variant="outline"
                                    onClick={handleMarkRead}
                                    disabled={markRead.processing}
                                    className="w-full"
                                >
                                    Mark as read
                                </Button>
                            )}
                            {submission.status !== 'archived' && (
                                <Button
                                    variant="outline"
                                    onClick={handleArchive}
                                    disabled={archive.processing}
                                    className="w-full"
                                >
                                    Archive
                                </Button>
                            )}
                        </div>

                        {activity.length > 0 && (
                            <div className="rounded-xl border border-sidebar-border/70 bg-white p-5 dark:border-sidebar-border dark:bg-neutral-950">
                                <h2 className="mb-4 text-xs font-semibold tracking-[0.16em] text-neutral-500 uppercase dark:text-neutral-400">
                                    Activity
                                </h2>
                                <ol className="space-y-3">
                                    {activity.map((entry) => (
                                        <li
                                            key={entry.id}
                                            className="flex gap-3 text-sm"
                                        >
                                            <span className="mt-0.5 size-1.5 shrink-0 translate-y-1.5 rounded-full bg-neutral-300 dark:bg-neutral-600" />
                                            <div className="min-w-0">
                                                <p className="text-neutral-700 dark:text-neutral-300">
                                                    {entry.description}
                                                </p>
                                                <p className="mt-0.5 text-xs text-neutral-400 dark:text-neutral-500">
                                                    {entry.causer ?? 'System'} ·{' '}
                                                    {formatActivityTime(
                                                        entry.created_at,
                                                    )}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ol>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

SubmissionsShow.layout = {
    breadcrumbs: [
        { title: 'Submissions', href: evodevops.base.admin.submissions.index.url() },
        { title: 'View submission', href: '#' },
    ],
} satisfies AppLayoutPageProps;
