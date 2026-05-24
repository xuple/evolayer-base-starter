import { useCallback, useRef, useState } from 'react';
import ThreadStudioController from '@/actions/EvoDevOps/Base/Http/Controllers/Ai/ThreadStudioController';

export type ThreadStudioResult = {
    summary: string;
    urgency: string;
    sentiment: string;
    recommended_tone: string;
    customer_reply: string;
    internal_note: string;
};

export type ThreadStudioFailureType =
    | 'agent_exception'
    | 'malformed_response'
    | 'incomplete_response';

export type ThreadStudioInvalidField = { field: string; reason: string };

export type ThreadStudioError = {
    message: string;
    failureType: ThreadStudioFailureType | null;
    provider: string | null;
    model: string | null;
    missingFields: string[];
    invalidFields: ThreadStudioInvalidField[];
};

type ThreadStudioForm = {
    customer_message: string;
    tone: 'balanced' | 'warm' | 'firm';
    provider?: string;
    model?: string;
};

export type ThreadStudioInvocation = {
    id: string;
    durationMs: number | null;
};

export type UseThreadStudioStreamReturn = {
    submit: (form: ThreadStudioForm) => Promise<void>;
    processing: boolean;
    result: ThreadStudioResult | null;
    streamingFields: Partial<ThreadStudioResult>;
    invocation: ThreadStudioInvocation | null;
    error: ThreadStudioError | null;
    clearResult: () => void;
};

type SseFrame = { event: string; data: string };

function getXsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.split('=').slice(1).join('=')) : '';
}

function parseSseFrames(chunk: string): SseFrame[] {
    const frames: SseFrame[] = [];

    for (const block of chunk.split(/\n\n/)) {
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

        if (data !== '') {
            frames.push({ event, data });
        }
    }

    return frames;
}

const FALLBACK_ERROR: ThreadStudioError = {
    message: 'An unexpected error occurred. Try again in a moment.',
    failureType: null,
    provider: null,
    model: null,
    missingFields: [],
    invalidFields: [],
};

function parseErrorFrame(data: string): ThreadStudioError {
    try {
        const parsed = JSON.parse(data) as Partial<{
            message: string;
            failure_type: ThreadStudioFailureType;
            provider: string;
            model: string;
            missing_fields: string[];
            invalid_fields: ThreadStudioInvalidField[];
        }>;

        return {
            message:
                parsed.message ??
                'The AI provider did not return a usable reply. Try again in a moment.',
            failureType: parsed.failure_type ?? null,
            provider: parsed.provider ?? null,
            model: parsed.model ?? null,
            missingFields: parsed.missing_fields ?? [],
            invalidFields: parsed.invalid_fields ?? [],
        };
    } catch {
        return FALLBACK_ERROR;
    }
}

function applyFrame(
    frame: SseFrame,
    setResult: (r: ThreadStudioResult) => void,
    setInvocation: (i: ThreadStudioInvocation) => void,
    setError: (e: ThreadStudioError) => void,
    setStreamingFields: (fields: Partial<ThreadStudioResult>) => void,
): void {
    if (frame.event === 'done') {
        try {
            const parsed = JSON.parse(frame.data) as {
                result: ThreadStudioResult;
                invocation_id?: string;
                duration_ms?: number | null;
            };
            setStreamingFields({});
            setResult(parsed.result);

            if (parsed.invocation_id) {
                setInvocation({
                    id: parsed.invocation_id,
                    durationMs: parsed.duration_ms ?? null,
                });
            }
        } catch {
            setError({
                ...FALLBACK_ERROR,
                message: 'The response from the server was not valid JSON.',
            });
        }
    } else if (frame.event === 'error') {
        setStreamingFields({});
        setError(parseErrorFrame(frame.data));
    }
}

export function useThreadStudioStream(): UseThreadStudioStreamReturn {
    const [processing, setProcessing] = useState(false);
    const [result, setResult] = useState<ThreadStudioResult | null>(null);
    const [streamingFields, setStreamingFields] = useState<
        Partial<ThreadStudioResult>
    >({});
    const [invocation, setInvocation] = useState<ThreadStudioInvocation | null>(
        null,
    );
    const [error, setError] = useState<ThreadStudioError | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    const clearResult = useCallback((): void => {
        setResult(null);
        setStreamingFields({});
        setInvocation(null);
        setError(null);
    }, []);

    const submit = useCallback(
        async (form: ThreadStudioForm): Promise<void> => {
            // Cancel any previous in-flight request before starting a new one.
            abortRef.current?.abort();

            const controller = new AbortController();
            abortRef.current = controller;

            setProcessing(true);
            setResult(null);
            setStreamingFields({});
            setInvocation(null);
            setError(null);

            try {
                const response = await fetch(
                    ThreadStudioController.streamCompose.url(),
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'text/event-stream',
                            'X-XSRF-TOKEN': getXsrfToken(),
                        },
                        body: JSON.stringify(form),
                        signal: controller.signal,
                        cache: 'no-store',
                    },
                );

                if (!response.ok || !response.body) {
                    setError({
                        ...FALLBACK_ERROR,
                        message:
                            'The AI provider could not complete this request just now.',
                    });

                    return;
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();

                    if (done) {
                        break;
                    }

                    buffer += decoder.decode(value, { stream: true });

                    // Find the last complete SSE frame boundary (\n\n).
                    // Keep the incomplete tail for the next iteration.
                    const lastDoubleLF = buffer.lastIndexOf('\n\n');

                    if (lastDoubleLF === -1) {
                        continue;
                    }

                    const complete = buffer.slice(0, lastDoubleLF + 2);
                    buffer = buffer.slice(lastDoubleLF + 2);

                    for (const frame of parseSseFrames(complete)) {
                        if (frame.event === 'field_delta') {
                            try {
                                const parsed = JSON.parse(frame.data) as {
                                    name: keyof ThreadStudioResult;
                                    delta: string;
                                };
                                setStreamingFields((prev) => ({
                                    ...prev,
                                    [parsed.name]:
                                        (prev[parsed.name] ?? '') +
                                        parsed.delta,
                                }));
                            } catch {
                                // ignore malformed delta
                            }
                        } else if (frame.event !== 'field_complete') {
                            applyFrame(
                                frame,
                                setResult,
                                setInvocation,
                                setError,
                                setStreamingFields,
                            );
                        }
                    }
                }

                // Flush any remaining buffer once the stream closes.
                if (buffer.trim()) {
                    for (const frame of parseSseFrames(buffer)) {
                        applyFrame(
                            frame,
                            setResult,
                            setInvocation,
                            setError,
                            setStreamingFields,
                        );
                    }
                }
            } catch (err) {
                if (err instanceof DOMException && err.name === 'AbortError') {
                    return;
                }

                setError({
                    ...FALLBACK_ERROR,
                    message:
                        'The connection to the server was lost. Check your network and try again.',
                });
            } finally {
                setProcessing(false);
            }
        },
        [],
    );

    return {
        submit,
        processing,
        result,
        streamingFields,
        invocation,
        error,
        clearResult,
    };
}
