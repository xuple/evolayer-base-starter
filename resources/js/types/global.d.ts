import type { Auth } from '@/types/auth';
import type { EvoLayerSharedProps } from '@/types/evolayer';
import type { SiteSharedProps } from '@/types/site';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            site: SiteSharedProps;
            // Published by xuple/evolayer-base — see resources/js/types/evolayer.d.ts.
            evolayer: EvoLayerSharedProps;
            [key: string]: unknown;
        };
    }
}
