import { Head, Link, usePage } from '@inertiajs/react';
import { Blocks, FileText, Route, ShieldCheck, Wrench } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactElement } from 'react';
import { CommandBar } from '@/components/command-bar';
import { docsBaseUrl } from '@/config/docs';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { edit as profileEdit } from '@/routes/profile';
import evolayer from '@/routes/evolayer';

type StarterLink = {
    title: string;
    description: string;
    href: string;
    icon: LucideIcon;
    accent?: boolean;
};

type ExtensionPath = {
    title: string;
    description: string;
    icon: LucideIcon;
    href?: string;
};

const gettingStartedLinks: StarterLink[] = [
    {
        title: 'Open base docs',
        description:
            'Start with the canonical framework docs and the route map for this starter.',
        href: docsBaseUrl,
        icon: FileText,
        accent: true,
    },
    {
        title: 'Verify this install',
        description:
            'Run the verification path before you trust local changes or scaffold assumptions.',
        href: `${docsBaseUrl}/verification`,
        icon: ShieldCheck,
    },
    {
        title: 'Review conventions',
        description:
            'Check the rules for structure, naming, and safe change boundaries before extending.',
        href: `${docsBaseUrl}/conventions`,
        icon: Route,
    },
] as const;

const extensionPaths: ExtensionPath[] = [
    {
        title: 'Public pages and docs',
        description:
            'Use the about page to review the public pages, docs, and first app areas before extending.',
        href: evolayer.base.about().url,
        icon: Blocks,
    },
    {
        title: 'Account and settings',
        description:
            'Profile and security flows already exist. Extend them before inventing separate account scaffolding.',
        href: profileEdit().url,
        icon: ShieldCheck,
    },
    {
        title: 'Real app work',
        description:
            'Add domain screens, routes, and data once the baseline is verified.',
        icon: Wrench,
    },
] as const;

export default function Home() {
    const { auth } = usePage().props;
    const firstName = auth.user?.name.split(' ')[0] || 'there';

    const hour = new Date().getHours();
    const greeting =
        hour < 12
            ? 'Good morning'
            : hour < 17
              ? 'Good afternoon'
              : 'Good evening';

    return (
        <>
            <Head title="Home" />

            <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col overflow-y-auto px-6 py-8 md:px-10 lg:px-12">
                <div className="mb-6 animate-in duration-300 fade-in slide-in-from-bottom-2">
                    <div className="text-xs font-semibold tracking-[0.22em] text-neutral-500 uppercase dark:text-neutral-400">
                        Home
                    </div>
                    <h1 className="mt-4 text-3xl font-medium tracking-tight text-neutral-900 md:text-4xl dark:text-neutral-100">
                        {greeting}, {firstName}
                    </h1>
                    <p className="mt-3 max-w-3xl text-sm leading-7 text-neutral-600 md:text-base dark:text-neutral-400">
                        This root is here to orient you after install. Start
                        with the docs, verify the baseline, and extend the
                        starter through real routes and features.
                    </p>
                </div>

                <div className="mb-10 animate-in delay-75 duration-300 fill-mode-both fade-in slide-in-from-bottom-2">
                    <CommandBar />
                </div>

                <div className="mb-10 animate-in delay-100 duration-300 fill-mode-both fade-in slide-in-from-bottom-2">
                    <h2 className="mb-4 text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                        Start Here
                    </h2>
                    <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                        {gettingStartedLinks.map((item) => {
                            const Icon = item.icon;

                            return (
                                <a
                                    key={item.title}
                                    href={item.href}
                                    target="_blank"
                                    rel="noreferrer"
                                    className={cn(
                                        'group rounded-xl border bg-white p-4 shadow-sm transition-all dark:bg-neutral-950',
                                        item.accent
                                            ? 'border-brand/20 hover:border-brand/40 dark:border-brand/30 dark:hover:border-brand/50'
                                            : 'border-neutral-200 hover:border-neutral-300 dark:border-neutral-800 dark:hover:border-neutral-700',
                                    )}
                                >
                                    <div className="mb-3 flex items-center justify-between">
                                        <div
                                            className={cn(
                                                'flex h-9 w-9 items-center justify-center rounded-lg',
                                                item.accent
                                                    ? 'bg-brand/10 text-brand'
                                                    : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-400',
                                            )}
                                        >
                                            <Icon className="h-4 w-4" />
                                        </div>
                                    </div>
                                    <div
                                        className={cn(
                                            'text-sm font-medium transition-colors',
                                            item.accent
                                                ? 'text-neutral-900 group-hover:text-brand dark:text-neutral-100'
                                                : 'text-neutral-900 group-hover:text-brand dark:text-neutral-100',
                                        )}
                                    >
                                        {item.title}
                                    </div>
                                    <div className="mt-1 text-xs leading-6 text-neutral-500">
                                        {item.description}
                                    </div>
                                </a>
                            );
                        })}
                    </div>
                </div>

                <div className="mb-10 animate-in delay-150 duration-300 fill-mode-both fade-in slide-in-from-bottom-2">
                    <div className="rounded-2xl border border-dashed border-neutral-300 bg-neutral-50/70 p-6 dark:border-neutral-700 dark:bg-neutral-900/50">
                        <h2 className="text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                            Starter Status
                        </h2>
                        <p className="mt-3 max-w-3xl text-sm leading-7 text-neutral-600 dark:text-neutral-400">
                            No projects, templates, teammates, or activity are
                            seeded here yet. That is intentional. This starter
                            is meant to give you a coherent shell, auth flows,
                            settings, and docs-backed guidance before domain
                            entities exist.
                        </p>
                    </div>
                </div>

                <div className="animate-in delay-200 duration-300 fill-mode-both fade-in slide-in-from-bottom-2">
                    <h2 className="mb-4 text-xs font-semibold tracking-wider text-neutral-500 uppercase">
                        Safe Extension Paths
                    </h2>

                    <div className="grid grid-cols-1 gap-3 md:grid-cols-3">
                        {extensionPaths.map((item) => {
                            const Icon = item.icon;
                            const content = (
                                <>
                                    <div className="mb-3 flex items-center justify-between">
                                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-neutral-100 text-neutral-600 dark:bg-neutral-900 dark:text-neutral-400">
                                            <Icon className="h-4 w-4" />
                                        </div>
                                    </div>
                                    <div className="text-sm font-medium text-neutral-900 transition-colors group-hover:text-brand dark:text-neutral-100">
                                        {item.title}
                                    </div>
                                    <div className="mt-1 text-xs leading-6 text-neutral-500">
                                        {item.description}
                                    </div>
                                </>
                            );

                            if (item.href) {
                                return (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        className="group rounded-xl border border-neutral-200 bg-white p-4 shadow-sm transition-all hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-700"
                                    >
                                        {content}
                                    </Link>
                                );
                            }

                            return (
                                <div
                                    key={item.title}
                                    className="group rounded-xl border border-neutral-200 bg-white p-4 shadow-sm transition-all dark:border-neutral-800 dark:bg-neutral-950"
                                >
                                    {content}
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </>
    );
}

Home.layout = (page: ReactElement) => (
    <AppLayout breadcrumbs={[{ title: 'Home', href: evolayer.base.home() }]}>
        {page}
    </AppLayout>
);
