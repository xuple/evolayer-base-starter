import { Head, Link } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import SubmissionsController from '@/actions/Xuple/EvoLayer/Base/Http/Controllers/Admin/SubmissionsController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import evolayer from '@/routes/evolayer';
import type { AppLayoutPageProps } from '@/types/layout';

type Submission = {
    id: string;
    type: string;
    status: string;
    triage_urgency: 'low' | 'medium' | 'high' | null;
    first_name: string;
    last_name: string;
    email: string;
    subject: string;
    created_at: string;
};

type PaginatorLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: PaginatorLink[];
};

type Props = {
    submissions: Paginator<Submission>;
};

const statusVariant: Record<string, 'default' | 'secondary' | 'outline'> = {
    new: 'default',
    read: 'secondary',
    archived: 'outline',
};

const urgencyVariant: Record<string, 'destructive' | 'default' | 'secondary'> =
    {
        high: 'destructive',
        medium: 'default',
        low: 'secondary',
    };

export default function SubmissionsIndex({ submissions }: Props) {
    return (
        <>
            <Head title="Submissions" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold text-neutral-900 dark:text-neutral-100">
                            Submissions
                        </h1>
                        <p className="mt-0.5 text-sm text-neutral-500 dark:text-neutral-400">
                            {submissions.total} total
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    {submissions.data.length === 0 ? (
                        <div className="px-6 py-12 text-center text-sm text-neutral-500 dark:text-neutral-400">
                            No submissions yet.
                        </div>
                    ) : (
                        <table className="w-full text-sm">
                            <thead className="border-b border-sidebar-border/70 bg-neutral-50/50 dark:border-sidebar-border dark:bg-neutral-900/50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        From
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Subject
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Type
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Status
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Urgency
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium text-neutral-600 dark:text-neutral-400">
                                        Received
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-sidebar-border/50 dark:divide-sidebar-border">
                                {submissions.data.map((submission) => (
                                    <tr
                                        key={submission.id}
                                        className="transition-colors hover:bg-neutral-50/60 dark:hover:bg-neutral-900/40"
                                    >
                                        <td className="px-4 py-3">
                                            <Link
                                                href={SubmissionsController.show.url(
                                                    {
                                                        submission:
                                                            submission.id,
                                                    },
                                                )}
                                                className="font-medium text-neutral-900 hover:text-brand dark:text-neutral-100"
                                            >
                                                {submission.first_name}{' '}
                                                {submission.last_name}
                                            </Link>
                                            <div className="text-xs text-neutral-500 dark:text-neutral-400">
                                                {submission.email}
                                            </div>
                                        </td>
                                        <td className="max-w-xs truncate px-4 py-3 text-neutral-700 dark:text-neutral-300">
                                            <Link
                                                href={SubmissionsController.show.url(
                                                    {
                                                        submission:
                                                            submission.id,
                                                    },
                                                )}
                                                className="hover:text-brand"
                                            >
                                                {submission.subject}
                                            </Link>
                                        </td>
                                        <td className="px-4 py-3 text-neutral-600 capitalize dark:text-neutral-400">
                                            {submission.type}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        submission.status
                                                    ] ?? 'outline'
                                                }
                                            >
                                                {submission.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            {submission.triage_urgency ? (
                                                <Badge
                                                    variant={
                                                        urgencyVariant[
                                                            submission
                                                                .triage_urgency
                                                        ]
                                                    }
                                                >
                                                    {submission.triage_urgency}
                                                </Badge>
                                            ) : (
                                                <span className="text-xs text-neutral-400 dark:text-neutral-600">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-neutral-500 dark:text-neutral-400">
                                            {new Date(
                                                submission.created_at,
                                            ).toLocaleDateString('en-GB', {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                            })}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {submissions.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Page {submissions.current_page} of{' '}
                            {submissions.last_page}
                        </p>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                disabled={!submissions.prev_page_url}
                            >
                                {submissions.prev_page_url ? (
                                    <Link href={submissions.prev_page_url}>
                                        <ChevronLeftIcon className="size-4" />
                                        Previous
                                    </Link>
                                ) : (
                                    <span>
                                        <ChevronLeftIcon className="size-4" />
                                        Previous
                                    </span>
                                )}
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                disabled={!submissions.next_page_url}
                            >
                                {submissions.next_page_url ? (
                                    <Link href={submissions.next_page_url}>
                                        Next
                                        <ChevronRightIcon className="size-4" />
                                    </Link>
                                ) : (
                                    <span>
                                        Next
                                        <ChevronRightIcon className="size-4" />
                                    </span>
                                )}
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

SubmissionsIndex.layout = {
    breadcrumbs: [
        { title: 'Submissions', href: evolayer.base.admin.submissions.index.url() },
    ],
} satisfies AppLayoutPageProps;
