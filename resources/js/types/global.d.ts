import type { Auth } from '@/types/auth';
import type { EvoSharedProps } from '@/types/evodevops';

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
            // Published by evodevops/base — see resources/js/types/evodevops.d.ts.
            evo: EvoSharedProps;
            [key: string]: unknown;
        };
    }
}
