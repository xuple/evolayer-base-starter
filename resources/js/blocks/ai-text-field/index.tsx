import { LoaderCircle, Sparkles } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import type { ChangeEvent, ReactElement, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

export interface AiTextFieldProps {
    /** The label displayed above the textarea. */
    label: string;
    /** Controlled value. */
    value: string;
    /** Called whenever the value changes (user edit or AI stream delta). */
    onChange: (value: string) => void;
    /** Endpoint accepting `{ field_hint, context? }` — returns SSE stream. */
    suggestUrl: string;
    /** Returns the POST body for the suggest request. */
    buildPayload: () => { field_hint: string; context?: string };
    placeholder?: string;
    rows?: number;
    disabled?: boolean;
    /** Label for the suggest button. Defaults to "AI Suggest". */
    suggestLabel?: string;
    /** Extra actions rendered in the label row before the AI Suggest button. */
    labelActions?: ReactNode;
    className?: string;
    textareaClassName?: string;
    id?: string;
}

function getXsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.split('=').slice(1).join('=')) : '';
}

export function AiTextField({
    label,
    value,
    onChange,
    suggestUrl,
    buildPayload,
    placeholder,
    rows = 4,
    disabled = false,
    suggestLabel = 'AI Suggest',
    labelActions,
    className,
    textareaClassName,
    id,
}: AiTextFieldProps): ReactElement {
    const [suggesting, setSuggesting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const abortRef = useRef<AbortController | null>(null);
    const accumulatedRef = useRef<string>('');

    const suggest = useCallback(async (): Promise<void> => {
        abortRef.current?.abort();

        const controller = new AbortController();
        abortRef.current = controller;

        setSuggesting(true);
        setError(null);
        accumulatedRef.current = '';
        onChange('');

        try {
            const response = await fetch(suggestUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'text/event-stream',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                body: JSON.stringify(buildPayload()),
                signal: controller.signal,
                cache: 'no-store',
                credentials: 'same-origin',
            });

            if (!response.ok || !response.body) {
                setError(
                    'AI assist is unavailable right now. Try again in a moment.',
                );

                return;
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder('utf-8');
            let buffer = '';

            while (true) {
                const { done, value: chunk } = await reader.read();

                if (done) {
                    break;
                }

                buffer += decoder.decode(chunk, { stream: true });

                const lastDoubleLF = buffer.lastIndexOf('\n\n');

                if (lastDoubleLF === -1) {
                    continue;
                }

                const complete = buffer.slice(0, lastDoubleLF + 2);
                buffer = buffer.slice(lastDoubleLF + 2);

                for (const block of complete.split(/\n\n/)) {
                    if (!block.trim()) {
                        continue;
                    }

                    let event = 'message';
                    let data = '';

                    for (const line of block.split('\n')) {
                        if (line.startsWith('event:')) {
                            event = line.slice('event:'.length).trim();
                        } else if (line.startsWith('data:')) {
                            data = line.slice('data:'.length).trim();
                        }
                    }

                    if (event === 'text_delta' && data) {
                        try {
                            const parsed = JSON.parse(data) as {
                                delta: string;
                            };
                            accumulatedRef.current += parsed.delta;
                            onChange(accumulatedRef.current);
                        } catch {
                            // ignore malformed delta
                        }
                    } else if (event === 'error' && data) {
                        try {
                            const parsed = JSON.parse(data) as {
                                message?: string;
                            };
                            setError(
                                parsed.message ??
                                    'AI assist is unavailable right now.',
                            );
                        } catch {
                            setError('AI assist is unavailable right now.');
                        }
                    }
                }
            }
        } catch (err) {
            if (err instanceof DOMException && err.name === 'AbortError') {
                return;
            }

            setError(
                'The connection was lost while generating. Try again.',
            );
        } finally {
            setSuggesting(false);
        }
    }, [suggestUrl, buildPayload, onChange]);

    const inputId = id ?? label.toLowerCase().replace(/\s+/g, '_');

    return (
        <div className={cn('grid gap-2', className)}>
            <div className="flex items-center justify-between gap-3">
                <Label htmlFor={inputId}>{label}</Label>
                <div className="flex items-center gap-2">
                    {labelActions}
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        disabled={disabled || suggesting}
                        onClick={() => void suggest()}
                        className="h-7 gap-1.5 text-xs"
                    >
                        {suggesting ? (
                            <>
                                <LoaderCircle className="size-3 animate-spin" />
                                Generating…
                            </>
                        ) : (
                            <>
                                <Sparkles className="size-3" />
                                {suggestLabel}
                            </>
                        )}
                    </Button>
                </div>
            </div>
            <div className="relative">
                <textarea
                    id={inputId}
                    value={value}
                    onChange={(e: ChangeEvent<HTMLTextAreaElement>) =>
                        onChange(e.target.value)
                    }
                    placeholder={placeholder}
                    rows={rows}
                    disabled={disabled}
                    className={cn(
                        'min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30',
                        suggesting && 'border-primary/50',
                        textareaClassName,
                    )}
                />
                {suggesting && value && (
                    <span className="pointer-events-none absolute right-3 bottom-3 animate-pulse text-xs text-neutral-400">
                        |
                    </span>
                )}
            </div>
            {error && (
                <p className="text-sm text-destructive">{error}</p>
            )}
        </div>
    );
}
