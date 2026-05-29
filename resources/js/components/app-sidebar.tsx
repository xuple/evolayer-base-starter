import { Link } from '@inertiajs/react';
import { BookOpen, FolderGit2, LayoutGrid } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { sidebarPrimaryNavItems } from '@/config/navigation';
import { useExampleNavItems } from '@/hooks/use-example-nav-items';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const baseNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/xuple/evolayer-base-starter',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://docs.evodevops.com/base',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    // EvoLayer example pages, filtered to those enabled via EVOLAYER_BASE_EXAMPLE_* flags.
    const exampleNavItems = useExampleNavItems(sidebarPrimaryNavItems).map(
        (item): NavItem => ({
            title: item.title,
            href: item.href,
            icon: (item.icon ?? undefined) as LucideIcon | undefined,
        }),
    );

    const mainNavItems: NavItem[] = [...exampleNavItems, ...baseNavItems];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
