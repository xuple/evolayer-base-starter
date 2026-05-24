import type { BreadcrumbItem } from '@/types';

export interface AppLayoutPageProps {
    breadcrumbs?: BreadcrumbItem[];
}

export interface AuthLayoutPageProps {
    title?: string;
    description?: string;
}
