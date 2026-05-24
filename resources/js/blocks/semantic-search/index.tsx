import { LoaderCircle, Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { ReactElement } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export type SemanticSearchStrategy = 'vector' | 'like' | 'empty';

export type SemanticSearchResult = {
    id: string;
    title: string;
    subtitle?: string | null;
    excerpt?: string | null;
    status?: string | null;
    urgency?: 'low' | 'medium' | 'high' | null;
    sentiment?: 'positive' | 'neutral' | 'negative' | null;
    created_at?: string | null;
    tags?: string[];
};

export interface SemanticSearchProps {
    searchUrl: string;
    onSelect: (result: SemanticSearchResult) => void;
    placeholder?: string;
    minLength?: number;
    limit?: number;
    className?: string;
}

type SearchResponse = {
    strategy: SemanticSearchStrategy;
    results: SemanticSearchResult[];
};

const URGENCY_VARIANT: Record<string, 'destructive' | 'default' | 'secondary'> =
    {
        high: 'destructive',
        medium: 'default',
        low: 'secondary',
    };

function buildSearchUrl(baseUrl: string, query: string, limit: number): string {
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.set('q', query);
    url.searchParams.set('limit', String(limit));

    return `${url.pathname}${url.search}${url.hash}`;
}

function strategyLabel(strategy: SemanticSearchStrategy): string {
    return strategy === 'vector' ? 'Semantic' : 'Keyword';
}

export function SemanticSearch({
    searchUrl,
    onSelect,
    placeholder = 'Search submissions',
    minLength = 2,
    limit = 8,
    className,
}: SemanticSearchProps): ReactElement {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SemanticSearchResult[]>([]);
    const [strategy, setStrategy] = useState<SemanticSearchStrategy>('empty');
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    const trimmedQuery = useMemo(() => query.trim(), [query]);
    const shouldSearch = trimmedQuery.length >= minLength;

    useEffect(() => {
        abortRef.current?.abort();

        if (!shouldSearch) {
            // eslint-disable-next-line react-hooks/set-state-in-effect
            setResults([]);
            setStrategy('empty');
            setProcessing(false);
            setError(null);

            return;
        }

        const controller = new AbortController();
        abortRef.current = controller;
        setProcessing(true);
        setError(null);

        const timeout = window.setTimeout(() => {
            fetch(buildSearchUrl(searchUrl, trimmedQuery, limit), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        throw new Error('Search failed.');
                    }

                    return (await response.json()) as SearchResponse;
                })
                .then((data) => {
                    setStrategy(data.strategy);
                    setResults(data.results);
                })
                .catch((searchError: unknown) => {
                    if (
                        searchError instanceof DOMException &&
                        searchError.name === 'AbortError'
                    ) {
                        return;
                    }

                    setResults([]);
                    setStrategy('empty');
                    setError('Search is unavailable right now.');
                })
                .finally(() => {
                    if (!controller.signal.aborted) {
                        setProcessing(false);
                    }
                });
        }, 250);

        return () => {
            window.clearTimeout(timeout);
            controller.abort();
        };
    }, [limit, searchUrl, shouldSearch, trimmedQuery]);

    function clear(): void {
        abortRef.current?.abort();
        setQuery('');
        setResults([]);
        setStrategy('empty');
        setProcessing(false);
        setError(null);
    }

    return (
        <div className={cn('relative', className)}>
            <div className="relative">
                <Search className="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-neutral-400" />
                <Input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder={placeholder}
                    className="h-9 pr-9 pl-8 text-sm"
                    type="search"
                />
                <div className="absolute top-1/2 right-1.5 flex -translate-y-1/2 items-center">
                    {processing ? (
                        <LoaderCircle className="size-4 animate-spin text-neutral-400" />
                    ) : query ? (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-6"
                            onClick={clear}
                            aria-label="Clear search"
                        >
                            <X className="size-3.5" />
                        </Button>
                    ) : null}
                </div>
            </div>

            {shouldSearch && (
                <div className="absolute z-20 mt-2 max-h-96 w-full overflow-y-auto rounded-md border border-sidebar-border/70 bg-white shadow-lg dark:border-sidebar-border dark:bg-neutral-950">
                    <div className="flex items-center justify-between border-b border-sidebar-border/70 px-3 py-2 dark:border-sidebar-border">
                        <span className="text-xs font-medium text-neutral-500 dark:text-neutral-400">
                            {results.length} results
                        </span>
                        {strategy !== 'empty' && (
                            <Badge variant="outline" className="text-[10px]">
                                {strategyLabel(strategy)}
                            </Badge>
                        )}
                    </div>

                    {error ? (
                        <div className="px-3 py-4 text-xs text-red-600 dark:text-red-400">
                            {error}
                        </div>
                    ) : results.length === 0 && !processing ? (
                        <div className="px-3 py-4 text-xs text-neutral-500 dark:text-neutral-400">
                            No matching submissions.
                        </div>
                    ) : (
                        <div className="divide-y divide-sidebar-border/50 dark:divide-sidebar-border">
                            {results.map((result) => (
                                <button
                                    type="button"
                                    key={result.id}
                                    onClick={() => {
                                        onSelect(result);
                                        clear();
                                    }}
                                    className="block w-full px-3 py-2.5 text-left transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-900/50"
                                >
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-neutral-900 dark:text-neutral-100">
                                                {result.title}
                                            </p>
                                            {result.subtitle && (
                                                <p className="mt-0.5 truncate text-xs text-neutral-500 dark:text-neutral-400">
                                                    {result.subtitle}
                                                </p>
                                            )}
                                        </div>
                                        {result.urgency && (
                                            <Badge
                                                variant={
                                                    URGENCY_VARIANT[
                                                        result.urgency
                                                    ]
                                                }
                                                className="shrink-0"
                                            >
                                                {result.urgency}
                                            </Badge>
                                        )}
                                    </div>
                                    {result.excerpt && (
                                        <p className="mt-1 line-clamp-2 text-xs leading-5 text-neutral-600 dark:text-neutral-400">
                                            {result.excerpt}
                                        </p>
                                    )}
                                    {result.tags && result.tags.length > 0 && (
                                        <div className="mt-2 flex flex-wrap gap-1">
                                            {result.tags.map((tag) => (
                                                <Badge
                                                    key={tag}
                                                    variant="outline"
                                                    className="text-[10px]"
                                                >
                                                    {tag}
                                                </Badge>
                                            ))}
                                        </div>
                                    )}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
