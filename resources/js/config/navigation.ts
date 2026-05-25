import {
    FileText,
    Home,
    Inbox,
    LayoutGrid,
    Settings,
    Sparkles,
} from 'lucide-react';
import { dashboard } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance/index';
import { edit as profileEdit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security/index';
import type { EvoLayerNavItem } from '@/types/evolayer';

/*
 * EvoLayer feature URLs are stable string paths rather than Wayfinder
 * controller imports. This keeps the always-published `navigation.ts` (core)
 * free of compile-time dependencies on any single feature's routes — a feature
 * you haven't enabled won't have a Wayfinder-generated controller, and core
 * must compile regardless. `useExampleNavItems()` filters these by
 * `exampleKey` against the enabled flags, so disabled features never render.
 * Host/starter routes (dashboard, profile, etc.) keep their typed Wayfinder
 * imports since the starter always ships them.
 */
export const sidebarPrimaryNavItems: EvoLayerNavItem[] = [
    {
        title: 'Home',
        href: '/home',
        icon: Home,
        isAccent: true,
        description: 'Go to the launcher',
        exampleKey: 'marketing_pages',
    },
    {
        title: 'ThreadStudio',
        href: '/ai/thread-studio',
        icon: Sparkles,
        description: 'Shape threaded work into reviewed outputs',
        exampleKey: 'thread_studio',
    },
    {
        title: 'Inbox',
        href: '/admin/inbox',
        icon: Inbox,
        description: 'Review and respond to contact form submissions',
        exampleKey: 'admin_inbox',
    },
    {
        title: 'PRD Studio',
        href: '/admin/prd',
        icon: FileText,
        description: 'Turn product notes into scoped requirements',
        exampleKey: 'prd_studio',
    },
];

export const sidebarSecondaryNavItems: EvoLayerNavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
        description: 'View your activity',
    },
];

export const mainNavItems: EvoLayerNavItem[] = [
    ...sidebarPrimaryNavItems,
    ...sidebarSecondaryNavItems,
];

export const settingsNavItems: EvoLayerNavItem[] = [
    {
        title: 'Settings',
        href: profileEdit(),
        icon: Settings,
        description: 'Manage your account',
    },
];

export const settingsSectionNavItems: EvoLayerNavItem[] = [
    {
        title: 'Profile',
        href: profileEdit(),
        icon: null,
    },
    {
        title: 'Security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: null,
    },
];
