import type { InertiaLinkProps } from '@inertiajs/react';
import type { ComponentType } from 'react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: ComponentType<{ className?: string }> | null;
    isActive?: boolean;
};
