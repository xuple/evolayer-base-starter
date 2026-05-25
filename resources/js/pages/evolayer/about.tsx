import { Head, Link } from '@inertiajs/react';
import {
    Bot,
    CheckCircle2,
    Database,
    FileText,
    Gauge,
    Layers,
    LockKeyhole,
    RouteIcon,
    Sparkles,
    Workflow,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactElement } from 'react';
import { docsBaseUrl } from '@/config/docs';
import PublicLayout from '@/layouts/public-layout';

type Layer = {
    name: string;
    summary: string;
    icon: LucideIcon;
};

type DemoCard = {
    title: string;
    text: string;
    icon: LucideIcon;
};

const layers: Layer[] = [
    {
        name: 'AI layer',
        summary:
            'Structured streaming, provider probes, typed agents, smoke commands, and a seeded capability ledger.',
        icon: Bot,
    },
    {
        name: 'Product layer',
        summary:
            'ThreadStudio, PRD Studio, contact AI, voice input, and admin inbox examples wired as real Inertia pages.',
        icon: Sparkles,
    },
    {
        name: 'Data layer',
        summary:
            'Prefixed EvoLayer tables, ontology compile, change-event lineage, media, tags, and activity logging.',
        icon: Database,
    },
    {
        name: 'Laravel layer',
        summary:
            'Laravel 13, Fortify, React 19, Inertia, Wayfinder, Tailwind, permissions, and production build tooling.',
        icon: Layers,
    },
];

const demoSurface = [
    {
        title: 'ThreadStudio',
        text: 'Compose structured AI responses and watch streamed fields arrive.',
        icon: Workflow,
    },
    {
        title: 'PRD Studio',
        text: 'Turn rough product notes into scoped requirements.',
        icon: FileText,
    },
    {
        title: 'Admin inbox',
        text: 'Review contact submissions behind the EvoLayer admin gate.',
        icon: LockKeyhole,
    },
    {
        title: 'Contact AI',
        text: 'Triage, tag, and enrich form submissions with optional media.',
        icon: RouteIcon,
    },
] satisfies DemoCard[];

const walkthrough = [
    'Start with the public landing and contact flow.',
    'Log in with the seeded admin user.',
    'Open ThreadStudio for structured streaming composition.',
    'Review PRD Studio, voice input, text assist, and the admin inbox.',
] as const;

const proof = [
    'Clean create-project install',
    'Migrate and seed',
    'Wayfinder generated',
    'Ontology compiled',
    'Production build passed',
    'Doctor command green',
] as const;

export default function About() {
    return (
        <>
            <Head title="EvoLayer Base" />

            <main className="relative mx-auto flex w-full max-w-7xl flex-col gap-14 overflow-hidden py-8 sm:py-12">
                <div className="bg-brand/10 pointer-events-none absolute inset-x-8 top-0 -z-10 h-80 rounded-full blur-3xl" />

                <section>
                    <div className="flex flex-col justify-center rounded-[2.5rem] border border-neutral-200 bg-white/85 p-7 shadow-[0_28px_90px_rgba(15,23,42,0.08)] backdrop-blur sm:p-10 lg:p-12 dark:border-neutral-800 dark:bg-neutral-950/85">
                        <div className="border-brand/20 bg-brand/10 text-brand inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold tracking-[0.18em] uppercase">
                            <Sparkles className="size-3.5" />
                            EvoLayer Base
                        </div>

                        <h1 className="mt-7 max-w-4xl text-4xl font-semibold tracking-tight text-neutral-950 sm:text-5xl lg:text-6xl dark:text-neutral-50">
                            The free Laravel React base for AI-enabled product
                            teams.
                        </h1>

                        <p className="mt-6 max-w-2xl text-base leading-8 text-neutral-600 sm:text-lg dark:text-neutral-400">
                            Start from a working application: auth, typed
                            routes, structured AI workflows, admin screens,
                            ontology tooling, and release checks already wired
                            together.
                        </p>

                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link
                                href="/register"
                                className="bg-brand hover:bg-brand-hover inline-flex items-center rounded-xl px-5 py-3 text-sm font-semibold text-white shadow-sm transition"
                            >
                                Create account
                            </Link>
                            <a
                                href={docsBaseUrl}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-5 py-3 text-sm font-semibold text-neutral-900 transition hover:border-neutral-300 dark:border-neutral-800 dark:bg-neutral-950 dark:text-neutral-100 dark:hover:border-neutral-700"
                            >
                                <FileText className="size-4" />
                                Read docs
                            </a>
                        </div>

                        <div className="mt-9 max-w-2xl overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-950 text-sm text-neutral-100 shadow-inner dark:border-neutral-800">
                            <div className="border-b border-white/10 px-4 py-2 text-xs text-neutral-400">
                                install
                            </div>
                            <pre className="overflow-x-auto px-4 py-4">
                                <code>
                                    composer create-project
                                    xuple/evolayer-base-starter my-app
                                </code>
                            </pre>
                        </div>

                        <div className="mt-9 border-t border-neutral-200 pt-8 dark:border-neutral-800">
                            <p className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                                System map
                            </p>
                            <div className="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                {layers.map((layer) => {
                                    const Icon = layer.icon;

                                    return (
                                        <div
                                            key={layer.name}
                                            className="rounded-2xl border border-neutral-200 bg-neutral-50/80 p-4 dark:border-neutral-800 dark:bg-neutral-900/70"
                                        >
                                            <div className="flex items-center gap-3">
                                                <span className="bg-brand/10 text-brand flex size-9 items-center justify-center rounded-xl">
                                                    <Icon className="size-4" />
                                                </span>
                                                <h2 className="font-semibold text-neutral-950 dark:text-neutral-50">
                                                    {layer.name}
                                                </h2>
                                            </div>
                                            <p className="mt-3 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                                {layer.summary}
                                            </p>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                            Demo surface
                        </p>
                        <h2 className="mt-3 text-3xl font-semibold tracking-tight text-neutral-950 dark:text-neutral-50">
                            One install, a full product-shaped walkthrough.
                        </h2>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        {demoSurface.map((item) => {
                            const Icon = item.icon;

                            return (
                                <article
                                    key={item.title}
                                    className="rounded-[2rem] border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-950"
                                >
                                    <Icon className="text-brand size-6" />
                                    <h3 className="mt-5 text-xl font-semibold text-neutral-950 dark:text-neutral-50">
                                        {item.title}
                                    </h3>
                                    <p className="mt-3 text-sm leading-6 text-neutral-600 dark:text-neutral-400">
                                        {item.text}
                                    </p>
                                </article>
                            );
                        })}
                    </div>
                </section>

                <section className="grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                    <div className="rounded-[2.25rem] border border-neutral-200 bg-white p-7 dark:border-neutral-800 dark:bg-neutral-950">
                        <p className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                            Try it
                        </p>
                        <h2 className="mt-3 text-2xl font-semibold tracking-tight text-neutral-950 dark:text-neutral-50">
                            A guided path through the starter.
                        </h2>
                        <ol className="mt-7 space-y-4">
                            {walkthrough.map((step, index) => (
                                <li key={step} className="flex gap-4">
                                    <span className="bg-brand flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold text-white">
                                        {index + 1}
                                    </span>
                                    <span className="pt-1 text-sm leading-6 text-neutral-700 dark:text-neutral-300">
                                        {step}
                                    </span>
                                </li>
                            ))}
                        </ol>
                    </div>

                    <div className="rounded-[2.25rem] border border-neutral-200 bg-white p-7 dark:border-neutral-800 dark:bg-neutral-950">
                        <div className="flex items-center gap-3">
                            <Gauge className="text-brand size-6" />
                            <div>
                                <p className="text-xs font-semibold tracking-[0.18em] text-neutral-500 uppercase">
                                    Known-good baseline
                                </p>
                                <h2 className="mt-1 text-2xl font-semibold tracking-tight text-neutral-950 dark:text-neutral-50">
                                    Install, build, then customize.
                                </h2>
                            </div>
                        </div>

                        <div className="mt-7 grid gap-3 sm:grid-cols-2">
                            {proof.map((item) => (
                                <div
                                    key={item}
                                    className="flex items-center gap-3 rounded-2xl bg-neutral-50 px-4 py-3 text-sm text-neutral-700 dark:bg-neutral-900/70 dark:text-neutral-300"
                                >
                                    <CheckCircle2 className="text-brand size-4" />
                                    <span>{item}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </section>
            </main>
        </>
    );
}

About.layout = (page: ReactElement) => (
    <PublicLayout
        title="EvoLayer Base"
        description="A free Laravel React starter with structured AI workflows, typed routes, and product-shaped examples already wired in."
    >
        {page}
    </PublicLayout>
);
