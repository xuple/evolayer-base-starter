import { Link } from '@inertiajs/react';
import { CheckCircleIcon } from 'lucide-react';
import type { ReactElement } from 'react';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import evolayer from '@/routes/evolayer';

export default function ContactThankYou() {
    return (
        <div className="mx-auto flex w-full max-w-lg flex-col items-center py-16 text-center">
            <div className="flex size-16 items-center justify-center rounded-full bg-brand/10">
                <CheckCircleIcon
                    className="size-8 text-brand"
                    aria-hidden="true"
                />
            </div>

            <h1 className="mt-6 text-3xl font-medium tracking-tight text-neutral-900 dark:text-neutral-100">
                Message sent
            </h1>
            <p className="mt-4 text-base leading-7 text-neutral-600 dark:text-neutral-400">
                Thanks for getting in touch. We've received your message and
                will get back to you within one business day.
            </p>

            <div className="mt-8 flex flex-wrap justify-center gap-3">
                <Button asChild variant="outline">
                    <Link href={evolayer.base.contact()}>
                        Send another message
                    </Link>
                </Button>
                <Button asChild>
                    <Link href="/">Back to home</Link>
                </Button>
            </div>
        </div>
    );
}

ContactThankYou.layout = (page: ReactElement) => (
    <PublicLayout title="Message sent">{page}</PublicLayout>
);
