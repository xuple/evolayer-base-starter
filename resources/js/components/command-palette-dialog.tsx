import { router } from '@inertiajs/react';
import { Blocks, Flame, Orbit } from 'lucide-react';
import { useEffect } from 'react';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import {
    commandPaletteCopy,
    commandPalettePlaceholder,
} from '@/config/command-palette';
import { starterDocsCommands } from '@/config/docs';
import { mainNavItems, settingsNavItems } from '@/config/navigation';
import { useExampleNavItems } from '@/hooks/use-example-nav-items';
import { getCommandModifier } from '@/lib/platform';
import { cn } from '@/lib/utils';
import { useCommandPalette } from '@/providers/command-palette-provider';

const docsCommandIcons = {
    base: Blocks,
    inertia: Orbit,
    laravel: Flame,
} as const;

export function CommandPaletteDialog() {
    const { isOpen, open, close } = useCommandPalette();

    const filteredMainItems = useExampleNavItems(mainNavItems);
    const navItems = [...filteredMainItems, ...settingsNavItems];

    useEffect(() => {
        const handleKeyDown = (event: KeyboardEvent) => {
            if (
                event.key.toLowerCase() !== 'k' ||
                (!event.metaKey && !event.ctrlKey)
            ) {
                return;
            }

            const target = event.target;
            const isEditableTarget =
                target instanceof HTMLElement &&
                (target.tagName === 'INPUT' ||
                    target.tagName === 'TEXTAREA' ||
                    target.isContentEditable);

            if (!isOpen && isEditableTarget) {
                return;
            }

            event.preventDefault();

            if (isOpen) {
                close();
            } else {
                open();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [close, isOpen, open]);

    return (
        <CommandDialog
            open={isOpen}
            onOpenChange={(nextOpen) => (nextOpen ? open() : close())}
        >
            <CommandInput autoFocus placeholder={commandPalettePlaceholder} />
            <CommandList>
                <CommandEmpty>{commandPaletteCopy.emptyState}</CommandEmpty>
                <CommandGroup heading={commandPaletteCopy.groups.navigation}>
                    {navItems.map((item) => {
                        const Icon = item.icon;

                        return (
                            <CommandItem
                                key={item.title}
                                value={[item.title, item.description]
                                    .filter(Boolean)
                                    .join(' ')}
                                onSelect={() => {
                                    close();
                                    router.visit(item.href);
                                }}
                            >
                                <div className="flex items-center gap-2.5">
                                    <div className="flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded bg-neutral-100 group-data-[selected=true]:bg-neutral-200 dark:bg-neutral-800 dark:group-data-[selected=true]:bg-neutral-700">
                                        {Icon ? (
                                            <Icon
                                                className={cn(
                                                    'text-neutral-600 dark:text-neutral-400',
                                                    item.isAccent &&
                                                        'text-brand dark:text-brand',
                                                )}
                                            />
                                        ) : null}
                                    </div>
                                    <div>
                                        <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {item.title}
                                        </div>
                                        {item.description ? (
                                            <div className="mt-0.5 text-[11.5px] text-neutral-500">
                                                {item.description}
                                            </div>
                                        ) : null}
                                    </div>
                                </div>
                            </CommandItem>
                        );
                    })}
                </CommandGroup>
                <CommandSeparator />
                <CommandGroup heading={commandPaletteCopy.groups.docs}>
                    {starterDocsCommands.map((item) => {
                        const Icon = docsCommandIcons[item.icon];

                        return (
                            <CommandItem
                                key={item.title}
                                value={item.value}
                                onSelect={() => {
                                    close();
                                    window.open(
                                        item.url,
                                        '_blank',
                                        'noopener,noreferrer',
                                    );
                                }}
                            >
                                <div className="flex items-center gap-2.5">
                                    <div className="flex h-[26px] w-[26px] shrink-0 items-center justify-center rounded bg-neutral-100 group-data-[selected=true]:bg-neutral-200 dark:bg-neutral-800 dark:group-data-[selected=true]:bg-neutral-700">
                                        <Icon className="text-neutral-600 dark:text-neutral-400" />
                                    </div>
                                    <div>
                                        <div className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {item.title}
                                        </div>
                                        <div className="mt-0.5 text-[11.5px] text-neutral-500">
                                            {item.description}
                                        </div>
                                    </div>
                                </div>
                            </CommandItem>
                        );
                    })}
                </CommandGroup>
            </CommandList>

            <div className="flex gap-3 border-t border-neutral-200 px-3.5 py-2 text-[11.5px] text-neutral-500 dark:border-neutral-800">
                {commandPaletteCopy.footerHints.map((hint) => (
                    <span key={hint.label} className="flex items-center gap-1">
                        <kbd
                            suppressHydrationWarning={hint.keys === 'modifier'}
                            className="rounded bg-neutral-100 px-1 font-mono dark:bg-neutral-800"
                        >
                            {hint.keys === 'arrows'
                                ? '↑↓'
                                : hint.keys === 'enter'
                                  ? '↵'
                                  : hint.keys === 'modifier'
                                    ? `${getCommandModifier()}K`
                                    : 'Esc'}
                        </kbd>
                        {hint.label}
                    </span>
                ))}
            </div>
        </CommandDialog>
    );
}
