import { Form } from '@inertiajs/react';
import { useEvoLayerProps } from '@/hooks/use-evolayer-props';
import { MailIcon, MessageSquareIcon, PaperclipIcon, PhoneIcon } from 'lucide-react';
import { useCallback, useRef, useState } from 'react';
import type { ReactElement } from 'react';
import ContactController from '@/actions/Xuple/EvoLayer/Base/Http/Controllers/ContactController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import PublicLayout from '@/layouts/public-layout';

const infoItems = [
    {
        icon: MailIcon,
        label: 'Email',
        value: 'hello@example.com',
        href: 'mailto:hello@example.com',
    },
    {
        icon: PhoneIcon,
        label: 'Phone',
        value: '+44 (0) 20 0000 0000',
        href: 'tel:+442000000000',
    },
    {
        icon: MessageSquareIcon,
        label: 'Response time',
        value: 'Within one business day',
        href: null,
    },
] as const;

export default function Contact() {
    const evolayer = useEvoLayerProps();
    const [subjectPlaceholder, setSubjectPlaceholder] = useState<string | null>(
        null,
    );
    const fetchHintsRef = useRef<AbortController | null>(null);

    const contactAiEnabled = evolayer.base.examples.contact_ai;
    const contactAttachmentsEnabled = evolayer.base.features.contact_attachments;

    const fetchHints = useCallback(
        async (type: string): Promise<void> => {
            fetchHintsRef.current?.abort();

            if (!type || !contactAiEnabled) {
                setSubjectPlaceholder(null);

                return;
            }

            const controller = new AbortController();
            fetchHintsRef.current = controller;

            try {
                const response = await fetch(
                    ContactController.subjectHints.url({ query: { type } }),
                    { signal: controller.signal },
                );
                const json = (await response.json()) as {
                    placeholder: string | null;
                };
                setSubjectPlaceholder(json.placeholder);
            } catch {
                // AbortError or network failure — leave placeholder unchanged
            }
        },
        [contactAiEnabled],
    );

    return (
        <div className="mx-auto w-full max-w-6xl">
            <div className="grid gap-12 lg:grid-cols-2">
                <div className="flex flex-col justify-center">
                    <p className="text-xs font-semibold tracking-[0.22em] text-neutral-500 uppercase dark:text-neutral-400">
                        Get in touch
                    </p>
                    <h1 className="mt-4 text-4xl font-medium tracking-tight text-neutral-900 sm:text-5xl dark:text-neutral-100">
                        We'd love to hear from you
                    </h1>
                    <p className="mt-5 text-base leading-7 text-neutral-600 dark:text-neutral-400">
                        Whether you have a question, an enquiry, or feedback,
                        we'll get back to you as quickly as we can.
                    </p>

                    <div className="mt-10 space-y-6">
                        {infoItems.map((item) => (
                            <div
                                key={item.label}
                                className="flex items-start gap-4"
                            >
                                <div className="flex size-11 flex-shrink-0 items-center justify-center rounded-2xl bg-brand/10">
                                    <item.icon
                                        className="size-5 text-brand"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-neutral-900 dark:text-neutral-100">
                                        {item.label}
                                    </p>
                                    {item.href ? (
                                        <a
                                            href={item.href}
                                            className="mt-1 text-sm text-neutral-600 transition hover:text-brand dark:text-neutral-400"
                                        >
                                            {item.value}
                                        </a>
                                    ) : (
                                        <p className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                            {item.value}
                                        </p>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="rounded-[2rem] border border-neutral-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-8 dark:border-neutral-800 dark:bg-neutral-950">
                    <Form
                        {...ContactController.store.form()}
                        className="flex flex-col gap-5"
                    >
                        {({ errors, processing }) => (
                            <>
                                {/* Honeypot — invisible to humans, bots fill it */}
                                <input
                                    type="text"
                                    name="honeypot"
                                    tabIndex={-1}
                                    autoComplete="off"
                                    aria-hidden="true"
                                    className="absolute -left-[9999px] opacity-0"
                                />

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="flex flex-col gap-1.5">
                                        <Label htmlFor="first_name">
                                            First name
                                        </Label>
                                        <Input
                                            id="first_name"
                                            name="first_name"
                                            type="text"
                                            autoComplete="given-name"
                                            placeholder="Jane"
                                            required
                                        />
                                        <InputError
                                            message={errors.first_name}
                                        />
                                    </div>
                                    <div className="flex flex-col gap-1.5">
                                        <Label htmlFor="last_name">
                                            Last name
                                        </Label>
                                        <Input
                                            id="last_name"
                                            name="last_name"
                                            type="text"
                                            autoComplete="family-name"
                                            placeholder="Smith"
                                            required
                                        />
                                        <InputError
                                            message={errors.last_name}
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="flex flex-col gap-1.5">
                                        <Label htmlFor="email">
                                            Email address
                                        </Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            autoComplete="email"
                                            placeholder="jane@example.com"
                                            required
                                        />
                                        <InputError message={errors.email} />
                                    </div>
                                    <div className="flex flex-col gap-1.5">
                                        <Label htmlFor="phone">
                                            Phone{' '}
                                            <span className="font-normal text-neutral-400">
                                                (optional)
                                            </span>
                                        </Label>
                                        <Input
                                            id="phone"
                                            name="phone"
                                            type="tel"
                                            autoComplete="tel"
                                            placeholder="+44 (0) 20 0000 0000"
                                        />
                                        <InputError message={errors.phone} />
                                    </div>
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="type">Enquiry type</Label>
                                    <select
                                        id="type"
                                        name="type"
                                        defaultValue=""
                                        required
                                        onChange={(e) =>
                                            void fetchHints(e.target.value)
                                        }
                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    >
                                        <option value="" disabled>
                                            Select a type
                                        </option>
                                        <option value="contact">
                                            General contact
                                        </option>
                                        <option value="enquiry">Enquiry</option>
                                        <option value="complaint">
                                            Complaint
                                        </option>
                                    </select>
                                    <InputError message={errors.type} />
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="subject">Subject</Label>
                                    <Input
                                        id="subject"
                                        name="subject"
                                        type="text"
                                        placeholder={
                                            subjectPlaceholder ??
                                            'How can we help?'
                                        }
                                        required
                                    />
                                    <InputError message={errors.subject} />
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="message">Message</Label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows={4}
                                        placeholder="Tell us more about your enquiry…"
                                        required
                                        className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-input/30"
                                    />
                                    <InputError message={errors.message} />
                                </div>

                                {contactAttachmentsEnabled && (
                                    <div className="flex flex-col gap-1.5">
                                        <Label htmlFor="attachments">
                                            Attachments{' '}
                                            <span className="font-normal text-neutral-400">
                                                (optional, up to 5 files, 10 MB each)
                                            </span>
                                        </Label>
                                        <label
                                            htmlFor="attachments"
                                            className="flex cursor-pointer items-center gap-2 rounded-md border border-dashed border-input bg-transparent px-3 py-3 text-sm text-muted-foreground transition-colors hover:border-ring hover:text-foreground"
                                        >
                                            <PaperclipIcon className="size-4 shrink-0" />
                                            <span>Click to attach files</span>
                                            <input
                                                id="attachments"
                                                name="attachments[]"
                                                type="file"
                                                multiple
                                                accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.txt,.csv,.mp3,.wav,.m4a,.ogg"
                                                className="sr-only"
                                            />
                                        </label>
                                        <InputError message={errors['attachments']} />
                                        <InputError message={errors['attachments.0']} />
                                    </div>
                                )}

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full"
                                >
                                    {processing ? (
                                        <>
                                            <Spinner className="size-4" />
                                            Sending…
                                        </>
                                    ) : (
                                        'Send message'
                                    )}
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </div>
    );
}

Contact.layout = (page: ReactElement) => (
    <PublicLayout title="Contact" description="Get in touch with us.">
        {page}
    </PublicLayout>
);
