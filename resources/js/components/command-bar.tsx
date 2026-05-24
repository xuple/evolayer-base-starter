import { Search } from 'lucide-react';
import { commandPalettePlaceholder } from '@/config/command-palette';
import { getCommandModifier } from '@/lib/platform';
import { useCommandPalette } from '@/providers/command-palette-provider';

export function CommandBar() {
    const { open } = useCommandPalette();

    return (
        <button
            type="button"
            onClick={open}
            className="flex w-full max-w-sm items-center gap-2 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2 text-left shadow-sm transition-colors hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-neutral-700"
        >
            <Search className="h-4 w-4 shrink-0 text-neutral-500" />
            <span className="flex-1 text-sm text-neutral-500">
                {commandPalettePlaceholder}
            </span>
            <div className="flex shrink-0 gap-1">
                <kbd
                    suppressHydrationWarning
                    className="rounded border border-neutral-200 bg-neutral-100 px-1.5 py-0.5 font-mono text-[10px] text-neutral-500 dark:border-neutral-800 dark:bg-neutral-950"
                >
                    {getCommandModifier()}
                </kbd>
                <kbd className="rounded border border-neutral-200 bg-neutral-100 px-1.5 py-0.5 font-mono text-[10px] text-neutral-500 dark:border-neutral-800 dark:bg-neutral-950">
                    K
                </kbd>
            </div>
        </button>
    );
}
