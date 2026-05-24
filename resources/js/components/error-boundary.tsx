import { AlertTriangleIcon } from 'lucide-react';
import { Component } from 'react';
import type { ErrorInfo, ReactNode } from 'react';
import { Button } from '@/components/ui/button';

type ErrorBoundaryProps = {
    children: ReactNode;
};

type ErrorBoundaryState = {
    hasError: boolean;
};

export class ErrorBoundary extends Component<
    ErrorBoundaryProps,
    ErrorBoundaryState
> {
    state: ErrorBoundaryState = {
        hasError: false,
    };

    static getDerivedStateFromError(): ErrorBoundaryState {
        return { hasError: true };
    }

    componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
        console.error('React render error caught by ErrorBoundary.', {
            error,
            errorInfo,
        });
    }

    render(): ReactNode {
        if (!this.state.hasError) {
            return this.props.children;
        }

        return (
            <main className="flex min-h-screen items-center justify-center bg-background px-6 py-12 text-foreground">
                <section
                    className="w-full max-w-lg rounded-2xl border border-border bg-card p-8 text-card-foreground shadow-sm"
                    role="alert"
                    aria-live="assertive"
                >
                    <div className="flex items-start gap-4">
                        <div className="flex size-11 shrink-0 items-center justify-center rounded-full bg-destructive/10 text-destructive">
                            <AlertTriangleIcon className="size-5" />
                        </div>
                        <div className="space-y-3">
                            <div className="space-y-1">
                                <h1 className="text-lg font-semibold">
                                    This screen could not render
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    This screen hit a client-side rendering
                                    error. Reload the page to retry after the
                                    latest assets or data are available.
                                </p>
                            </div>
                            <Button
                                type="button"
                                onClick={() => window.location.reload()}
                            >
                                Reload page
                            </Button>
                        </div>
                    </div>
                </section>
            </main>
        );
    }
}
