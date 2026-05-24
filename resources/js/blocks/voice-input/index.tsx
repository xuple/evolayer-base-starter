import { LoaderCircle, Mic, Square } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactElement } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type VoiceInputState = 'idle' | 'recording' | 'processing';

export interface VoiceInputProps {
    /** Endpoint that accepts a multipart `audio` upload and returns `{ text: string }`. */
    transcribeUrl: string;
    /** Called with the transcribed text when the upload succeeds. */
    onTranscribed: (text: string) => void;
    /** Called with a user-presentable message when something fails. */
    onError?: (message: string) => void;
    /** Disable the button (e.g. when the consuming form is processing). */
    disabled?: boolean;
    /** Maximum recording duration in seconds before we stop automatically. Default 60. */
    maxDurationSeconds?: number;
    /** Visual size matching shadcn Button sizes. */
    size?: 'sm' | 'default' | 'lg' | 'icon';
    /** Custom label for the idle state. Defaults to "Record" (omitted when size is "icon"). */
    label?: string;
    className?: string;
}

function getXsrfToken(): string {
    const match = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return match ? decodeURIComponent(match.split('=').slice(1).join('=')) : '';
}

export function VoiceInput({
    transcribeUrl,
    onTranscribed,
    onError,
    disabled = false,
    maxDurationSeconds = 60,
    size = 'default',
    label = 'Record',
    className,
}: VoiceInputProps): ReactElement {
    const [state, setState] = useState<VoiceInputState>('idle');
    const [seconds, setSeconds] = useState(0);
    const recorderRef = useRef<MediaRecorder | null>(null);
    const chunksRef = useRef<Blob[]>([]);
    const streamRef = useRef<MediaStream | null>(null);
    const tickRef = useRef<number | null>(null);
    const stopTimerRef = useRef<number | null>(null);

    const cleanup = (): void => {
        if (tickRef.current !== null) {
            window.clearInterval(tickRef.current);
            tickRef.current = null;
        }

        if (stopTimerRef.current !== null) {
            window.clearTimeout(stopTimerRef.current);
            stopTimerRef.current = null;
        }

        if (streamRef.current) {
            streamRef.current.getTracks().forEach((track) => track.stop());
            streamRef.current = null;
        }

        recorderRef.current = null;
        chunksRef.current = [];
    };

    useEffect(() => cleanup, []);

    const fail = (message: string): void => {
        cleanup();
        setState('idle');
        setSeconds(0);
        onError?.(message);
    };

    async function start(): Promise<void> {
        if (state !== 'idle' || disabled) {
            return;
        }

        if (
            typeof navigator === 'undefined' ||
            !navigator.mediaDevices?.getUserMedia
        ) {
            fail('Recording is not supported in this browser.');

            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
            });
            streamRef.current = stream;

            const mimeType = MediaRecorder.isTypeSupported('audio/webm')
                ? 'audio/webm'
                : '';

            const recorder = new MediaRecorder(
                stream,
                mimeType ? { mimeType } : undefined,
            );
            recorderRef.current = recorder;
            chunksRef.current = [];

            recorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    chunksRef.current.push(event.data);
                }
            };

            recorder.onstop = () => {
                void upload();
            };

            recorder.start();
            setState('recording');
            setSeconds(0);

            tickRef.current = window.setInterval(() => {
                setSeconds((prev) => prev + 1);
            }, 1000);

            stopTimerRef.current = window.setTimeout(() => {
                stop();
            }, maxDurationSeconds * 1000);
        } catch (err) {
            const message =
                err instanceof DOMException && err.name === 'NotAllowedError'
                    ? 'Microphone access was blocked. Allow it in your browser settings to record.'
                    : 'Could not start recording. Check your microphone connection.';
            fail(message);
        }
    }

    function stop(): void {
        if (state !== 'recording') {
            return;
        }

        if (tickRef.current !== null) {
            window.clearInterval(tickRef.current);
            tickRef.current = null;
        }

        if (stopTimerRef.current !== null) {
            window.clearTimeout(stopTimerRef.current);
            stopTimerRef.current = null;
        }

        try {
            recorderRef.current?.stop();
        } catch {
            // Ignore — onstop will fire if it can.
        }

        setState('processing');
    }

    async function upload(): Promise<void> {
        const blob = new Blob(chunksRef.current, {
            type: chunksRef.current[0]?.type ?? 'audio/webm',
        });
        chunksRef.current = [];

        if (streamRef.current) {
            streamRef.current.getTracks().forEach((t) => t.stop());
            streamRef.current = null;
        }

        recorderRef.current = null;

        if (blob.size === 0) {
            fail('No audio was captured. Try recording again.');

            return;
        }

        const form = new FormData();
        form.append('audio', blob, 'recording.webm');

        try {
            const response = await fetch(transcribeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
                body: form,
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const data = await response.json().catch(() => null);
                fail(
                    data?.message ??
                        'Transcription is unavailable right now. Try again in a moment.',
                );

                return;
            }

            const data = (await response.json()) as { text?: string };
            const text = data.text?.trim() ?? '';

            if (text === '') {
                fail(
                    'Transcription was empty. Try recording with clearer audio.',
                );

                return;
            }

            onTranscribed(text);
            setState('idle');
            setSeconds(0);
        } catch {
            fail(
                'The network connection was lost while transcribing. Try again.',
            );
        }
    }

    function handleClick(): void {
        if (state === 'idle') {
            void start();
        } else if (state === 'recording') {
            stop();
        }
    }

    const isIcon = size === 'icon';
    const ariaLabel =
        state === 'recording'
            ? 'Stop recording'
            : state === 'processing'
              ? 'Transcribing'
              : 'Start recording';

    return (
        <Button
            type="button"
            variant={state === 'recording' ? 'destructive' : 'outline'}
            size={size}
            disabled={disabled || state === 'processing'}
            onClick={handleClick}
            aria-label={ariaLabel}
            className={cn(state === 'recording' && 'animate-pulse', className)}
        >
            {state === 'idle' && (
                <>
                    <Mic className="size-4" />
                    {!isIcon && <span>{label}</span>}
                </>
            )}
            {state === 'recording' && (
                <>
                    <Square className="size-4 fill-current" />
                    {!isIcon && <span>Stop · {seconds}s</span>}
                </>
            )}
            {state === 'processing' && (
                <>
                    <LoaderCircle className="size-4 animate-spin" />
                    {!isIcon && <span>Transcribing…</span>}
                </>
            )}
        </Button>
    );
}
