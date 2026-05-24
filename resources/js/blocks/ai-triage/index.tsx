import type { ReactElement } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export type AiTriageUrgency = 'low' | 'medium' | 'high';

export type AiTriageSentiment = 'positive' | 'neutral' | 'negative';

export type AiTriageTag =
    | string
    | { name: string | { en?: string; [locale: string]: unknown } };

export interface AiTriageProps {
    urgency?: AiTriageUrgency | null;
    sentiment?: AiTriageSentiment | null;
    tags?: AiTriageTag[];
    summary?: string | null;
    title?: string;
    variant?: 'compact' | 'detailed';
    className?: string;
}

const URGENCY_VARIANT: Record<
    AiTriageUrgency,
    'destructive' | 'default' | 'secondary'
> = {
    high: 'destructive',
    medium: 'default',
    low: 'secondary',
};

const SENTIMENT_VARIANT: Record<
    AiTriageSentiment,
    'secondary' | 'outline' | 'destructive'
> = {
    positive: 'secondary',
    neutral: 'outline',
    negative: 'destructive',
};

function tagLabel(tag: AiTriageTag): string {
    if (typeof tag === 'string') {
        return tag;
    }

    if (typeof tag.name === 'string') {
        return tag.name;
    }

    return (tag.name.en as string | undefined) ?? '';
}

export function AiTriage({
    urgency,
    sentiment,
    tags = [],
    summary,
    title = 'AI Triage',
    variant = 'compact',
    className,
}: AiTriageProps): ReactElement | null {
    const hasContent =
        Boolean(urgency) ||
        Boolean(sentiment) ||
        tags.length > 0 ||
        Boolean(summary);

    if (!hasContent) {
        return null;
    }

    if (variant === 'detailed') {
        return (
            <div
                className={cn(
                    'rounded-xl border border-sidebar-border/70 bg-white p-5 dark:border-sidebar-border dark:bg-neutral-950',
                    className,
                )}
            >
                <h2 className="mb-4 text-xs font-semibold tracking-[0.16em] text-neutral-500 uppercase dark:text-neutral-400">
                    {title}
                </h2>
                <dl className="space-y-3 text-sm">
                    {urgency && (
                        <div>
                            <dt className="text-neutral-500 dark:text-neutral-400">
                                Urgency
                            </dt>
                            <dd className="mt-1">
                                <Badge variant={URGENCY_VARIANT[urgency]}>
                                    {urgency}
                                </Badge>
                            </dd>
                        </div>
                    )}
                    {sentiment && (
                        <div>
                            <dt className="text-neutral-500 dark:text-neutral-400">
                                Sentiment
                            </dt>
                            <dd className="mt-1">
                                <Badge variant={SENTIMENT_VARIANT[sentiment]}>
                                    {sentiment}
                                </Badge>
                            </dd>
                        </div>
                    )}
                    {tags.length > 0 && (
                        <div>
                            <dt className="text-neutral-500 dark:text-neutral-400">
                                Tags
                            </dt>
                            <dd className="mt-1.5 flex flex-wrap gap-1.5">
                                {tags.map((tag, index) => (
                                    <Badge
                                        key={`${tagLabel(tag)}-${index}`}
                                        variant="outline"
                                    >
                                        {tagLabel(tag)}
                                    </Badge>
                                ))}
                            </dd>
                        </div>
                    )}
                    {summary && (
                        <div>
                            <dt className="text-neutral-500 dark:text-neutral-400">
                                Summary
                            </dt>
                            <dd className="mt-1 leading-6 text-neutral-700 dark:text-neutral-300">
                                {summary}
                            </dd>
                        </div>
                    )}
                </dl>
            </div>
        );
    }

    return (
        <div className={className}>
            <p className="mb-3 text-xs font-semibold tracking-[0.14em] text-neutral-500 uppercase dark:text-neutral-400">
                {title}
            </p>
            <div className="flex flex-wrap items-center gap-2">
                {urgency && (
                    <Badge variant={URGENCY_VARIANT[urgency]}>{urgency}</Badge>
                )}
                {sentiment && (
                    <Badge variant={SENTIMENT_VARIANT[sentiment]}>
                        {sentiment}
                    </Badge>
                )}
                {tags.map((tag, index) => (
                    <Badge key={`${tagLabel(tag)}-${index}`} variant="outline">
                        {tagLabel(tag)}
                    </Badge>
                ))}
            </div>
            {summary && (
                <p className="mt-3 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                    {summary}
                </p>
            )}
        </div>
    );
}
