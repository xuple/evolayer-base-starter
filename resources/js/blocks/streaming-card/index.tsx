import { Copy } from 'lucide-react';
import type { ReactElement, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

export type StreamingCardDensity = 'comfortable' | 'compact';

export interface StreamingCardProps {
    title: ReactNode;
    description?: ReactNode;
    /** Currently displayed text. May be partial during typewriter playback. */
    text?: string | null;
    /** Whether the AI is still generating. While true and `text` is empty, skeletons render. */
    processing?: boolean;
    /** When true, shows a blinking cursor after `text` to indicate ongoing reveal. */
    streaming?: boolean;
    /** Placeholder shown when there is no text and we are not processing. */
    emptyText?: ReactNode;
    /** Number of skeleton lines to render while processing. */
    skeletonLines?: number;
    /** Click handler for the copy button. Omitting hides the button. */
    onCopy?: () => void;
    /** Button label. Consumer manages the toggle between e.g. "Copy" and "Copied". */
    copyLabel?: string;
    /** Disable the copy button (e.g. when `text` is empty). */
    copyDisabled?: boolean;
    /** Visual density. */
    density?: StreamingCardDensity;
    className?: string;
}

const SKELETON_WIDTHS = [
    'w-full',
    'w-full',
    'w-5/6',
    'w-3/4',
    'w-2/3',
    'w-4/5',
];

export function StreamingCard({
    title,
    description,
    text,
    processing = false,
    streaming = false,
    emptyText,
    skeletonLines = 4,
    onCopy,
    copyLabel = 'Copy',
    copyDisabled = false,
    density = 'comfortable',
    className,
}: StreamingCardProps): ReactElement {
    const showSkeleton = processing && !text;
    const showText =
        !showSkeleton && typeof text === 'string' && text.length > 0;
    const showEmpty = !showSkeleton && !showText && Boolean(emptyText);

    const skeletons = Array.from({ length: skeletonLines }).map(
        (_, index) => SKELETON_WIDTHS[index % SKELETON_WIDTHS.length],
    );

    if (density === 'compact') {
        return (
            <div
                className={cn(
                    'rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border',
                    className,
                )}
            >
                <p className="mb-2 text-xs font-medium text-neutral-500 dark:text-neutral-400">
                    {title}
                </p>
                {showSkeleton && (
                    <div className="space-y-1.5">
                        {skeletons.map((width, index) => (
                            <Skeleton
                                key={index}
                                className={cn('h-3', width)}
                            />
                        ))}
                    </div>
                )}
                {showText && (
                    <div className="flex items-start gap-2">
                        <p className="flex-1 text-xs leading-5 whitespace-pre-wrap text-neutral-700 dark:text-neutral-300">
                            {text}
                            {streaming && (
                                <span className="ml-px animate-pulse text-neutral-400">
                                    |
                                </span>
                            )}
                        </p>
                        {onCopy && (
                            <button
                                type="button"
                                onClick={onCopy}
                                disabled={copyDisabled}
                                className="shrink-0 text-neutral-400 hover:text-neutral-600 disabled:opacity-50 dark:hover:text-neutral-200"
                                title={copyLabel}
                                aria-label={copyLabel}
                            >
                                <Copy className="size-3.5" />
                            </button>
                        )}
                    </div>
                )}
                {showEmpty && (
                    <p className="text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                        {emptyText}
                    </p>
                )}
            </div>
        );
    }

    return (
        <Card
            className={cn(
                'border-neutral-200/80 shadow-sm dark:border-neutral-800',
                className,
            )}
        >
            <CardHeader>
                <div className="flex items-center justify-between gap-3">
                    <CardTitle>{title}</CardTitle>
                    {onCopy && (
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={copyDisabled}
                            onClick={onCopy}
                        >
                            <Copy className="h-4 w-4" />
                            {copyLabel}
                        </Button>
                    )}
                </div>
                {description && (
                    <CardDescription>{description}</CardDescription>
                )}
            </CardHeader>
            <CardContent className="space-y-3">
                {showSkeleton && (
                    <div className="space-y-2">
                        {skeletons.map((width, index) => (
                            <Skeleton
                                key={index}
                                className={cn('h-4', width)}
                            />
                        ))}
                    </div>
                )}
                {showText && (
                    <p className="text-sm leading-7 whitespace-pre-wrap text-neutral-700 dark:text-neutral-300">
                        {text}
                        {streaming && (
                            <span className="ml-px animate-pulse text-neutral-400">
                                |
                            </span>
                        )}
                    </p>
                )}
                {showEmpty && (
                    <p className="text-sm leading-7 text-neutral-500 dark:text-neutral-400">
                        {emptyText}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}
