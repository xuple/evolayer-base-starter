import { createInertiaApp } from '@inertiajs/react';
import type { HeadManagerTitleCallback } from '@inertiajs/core';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { classifyPageSurface } from '@/lib/page-surfaces';
import { CommandPaletteProvider } from '@/providers/command-palette-provider';

const appName = import.meta.env.VITE_APP_NAME || 'EvoLayer Base';

const formatInertiaTitle: HeadManagerTitleCallback = (title, page) => {
    const site = page.props.site as { name?: string } | undefined;
    const siteName = site?.name?.trim() || appName;
    const cleanTitle = title.trim();

    if (
        !cleanTitle ||
        cleanTitle === siteName ||
        cleanTitle.endsWith(` | ${siteName}`)
    ) {
        return cleanTitle || siteName;
    }

    return `${cleanTitle} | ${siteName}`;
};

createInertiaApp({
    title: formatInertiaTitle,
    layout: (name) => {
        switch (classifyPageSurface(name)) {
            case 'authentication':
                return AuthLayout;
            case 'settings':
                return [AppLayout, SettingsLayout];
            case 'application':
            case 'administration':
                return AppLayout;
            case 'public':
                return null;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                <CommandPaletteProvider>{app}</CommandPaletteProvider>
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
