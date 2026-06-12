import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogoIcon from '@/components/app-logo-icon';
import { SiteHead } from '@/components/site-head';
import type { JsonLdPayload, SiteSocialImage } from '@/types/site';
import { login, register } from '@/routes';

interface Props {
    title?: string;
    description?: string;
    canonical?: string;
    robots?: string;
    ogType?: string;
    image?: Partial<SiteSocialImage> | string | null;
    jsonLd?: JsonLdPayload | false | null;
    children: ReactNode;
}

export default function PublicLayout({
    title,
    description,
    canonical,
    robots,
    ogType,
    image,
    jsonLd,
    children,
}: Props) {
    const { auth, name } = usePage().props;

    return (
        <>
            <SiteHead
                title={title}
                description={description}
                canonical={canonical}
                robots={robots}
                ogType={ogType}
                image={image}
                jsonLd={jsonLd}
            />
            <div className="flex min-h-screen flex-col items-center bg-background p-6 text-foreground lg:p-8">
                <header className="mb-6 w-full max-w-7xl text-sm not-has-[nav]:hidden">
                    <nav className="flex items-center justify-between gap-4">
                        <Link
                            href="/"
                            className="flex items-center gap-2 text-foreground"
                        >
                            <AppLogoIcon className="h-6 w-6 fill-current text-brand" />
                            <span className="text-lg font-semibold tracking-tight">
                                {name}
                            </span>
                        </Link>

                        <div className="flex items-center justify-end gap-4">
                            {!auth.user && (
                                <>
                                    <Link
                                        href={login()}
                                        className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-foreground hover:border-border"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={register()}
                                        className="inline-block rounded-sm border border-border px-5 py-1.5 text-sm leading-normal text-foreground hover:border-foreground/20"
                                    >
                                        Register
                                    </Link>
                                </>
                            )}
                        </div>
                    </nav>
                </header>
                {children}
            </div>
        </>
    );
}
