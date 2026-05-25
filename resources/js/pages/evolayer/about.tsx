import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeftIcon,
    FileText,
    RouteIcon,
    ShieldCheckIcon,
    WorkflowIcon,
} from 'lucide-react';
import type { ReactElement } from 'react';
import { docsBaseUrl } from '@/config/docs';
import PublicLayout from '@/layouts/public-layout';

const roleBlocks = [
    {
        name: 'Docs',
        summary:
            'The docs cover installation, setup, conventions, and verification.',
        icon: <FileText className="size-5 text-brand" />,
    },
    {
        name: 'Public pages',
        summary:
            'The public pages explain the starter and link people into login, registration, and docs.',
        icon: <WorkflowIcon className="size-5 text-brand" />,
    },
    {
        name: 'Auth and app',
        summary:
            'Authentication, settings, and the first app pages are already wired and ready to extend.',
        icon: <ShieldCheckIcon className="size-5 text-brand" />,
    },
] as const;

const flowBlocks = [
    {
        name: 'Review the docs',
        summary:
            'Check installation, setup, and verification before changing the starter.',
    },
    {
        name: 'Replace starter copy',
        summary:
            'Update the public pages early so the app speaks in your product language instead of starter language.',
    },
    {
        name: 'Build from the app area',
        summary:
            'Add your real routes, screens, and data from the authenticated side of the app.',
    },
] as const;

const guidanceBlocks = [
    {
        name: 'Keep docs and code in sync',
        summary:
            'If the docs say a page, route, or setup step exists, the running app should match it.',
    },
    {
        name: 'Replace placeholders early',
        summary:
            'Remove starter claims and filler copy before adding more pages around them.',
    },
    {
        name: 'Keep the root page simple',
        summary:
            'Use the home page to orient people, not to explain every part of the framework.',
    },
] as const;

export default function About() {
    return (
        <>
            <Head title="About" />

            <div className="relative w-full overflow-hidden">
                <div className="absolute inset-x-0 top-0 -z-10 h-[28rem] bg-gradient-to-b from-brand/10 via-transparent to-transparent" />

                <main className="mx-auto flex w-full max-w-6xl flex-col py-6 sm:py-8">
                    <header className="border-b border-neutral-200/80 pb-5 dark:border-neutral-800/80">
                        <div className="text-xs font-semibold tracking-[0.22em] text-neutral-500 uppercase dark:text-neutral-400">
                            About
                        </div>
                        <div className="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div className="max-w-3xl">
                                <h1 className="text-3xl font-medium tracking-tight text-neutral-900 md:text-4xl dark:text-neutral-100">
                                    How this starter is organized
                                </h1>
                                <p className="mt-3 text-sm leading-7 text-neutral-600 md:text-base dark:text-neutral-400">
                                    Use this page to see where the docs, public
                                    pages, and app pages live before you start
                                    changing routes, copy, or screens.
                                </p>
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                <Link
                                    href="/"
                                    className="inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white px-4 py-2 text-sm font-medium text-neutral-900 transition hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-100 dark:hover:border-neutral-700"
                                >
                                    <ArrowLeftIcon className="size-4" />
                                    Back to main page
                                </Link>
                                <a
                                    href={docsBaseUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-hover"
                                >
                                    Open base docs
                                </a>
                            </div>
                        </div>
                    </header>

                    <section className="pt-10">
                        <div className="max-w-2xl">
                            <p className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                                Main areas
                            </p>
                            <h2 className="mt-3 text-2xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                                Each part of the starter has a clear job.
                            </h2>
                        </div>

                        <div className="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {roleBlocks.map((block) => (
                                <div
                                    key={block.name}
                                    className="rounded-[1.75rem] border border-neutral-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                                >
                                    <div className="flex size-11 items-center justify-center rounded-2xl bg-brand/10">
                                        {block.icon}
                                    </div>
                                    <h3 className="mt-4 text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                        {block.name}
                                    </h3>
                                    <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                        {block.summary}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="mt-12 grid gap-6 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
                        <div className="rounded-[2rem] border border-neutral-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-8 dark:border-neutral-800 dark:bg-neutral-950">
                            <p className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                                Start here
                            </p>
                            <h2 className="mt-3 text-2xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                                A simple order for first changes.
                            </h2>
                            <div className="mt-6 space-y-4">
                                {flowBlocks.map((flow) => (
                                    <div
                                        key={flow.name}
                                        className="rounded-[1.5rem] border border-neutral-200 bg-neutral-50/80 p-4 dark:border-neutral-800 dark:bg-neutral-900/70"
                                    >
                                        <h3 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                            {flow.name}
                                        </h3>
                                        <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                            {flow.summary}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="rounded-[2rem] border border-neutral-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-8 dark:border-neutral-800 dark:bg-neutral-950">
                            <p className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                                Keep it clean
                            </p>
                            <h2 className="mt-3 text-2xl font-semibold tracking-tight text-neutral-900 dark:text-neutral-100">
                                A few rules for changing the starter.
                            </h2>
                            <div className="mt-6 space-y-4">
                                {guidanceBlocks.map((block) => (
                                    <div
                                        key={block.name}
                                        className="rounded-[1.5rem] border border-neutral-200 bg-neutral-50/80 p-4 dark:border-neutral-800 dark:bg-neutral-900/70"
                                    >
                                        <div className="flex items-center gap-3">
                                            <RouteIcon className="size-5 text-brand" />
                                            <h3 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                                {block.name}
                                            </h3>
                                        </div>
                                        <p className="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                            {block.summary}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </>
    );
}

About.layout = (page: ReactElement) => (
    <PublicLayout
        title="About"
        description="See how the docs, public pages, and app pages are organized before you start changing the starter."
    >
        {page}
    </PublicLayout>
);
