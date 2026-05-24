import { usePage } from '@inertiajs/react';
import type { EvoSharedProps } from '@/types/evodevops';

/**
 * Read the EvoDevOps shared props from the current Inertia page.
 *
 * The host's HandleInertiaRequests middleware is expected to share an
 * `evo: { base: { examples, features } }` block — see the package README
 * for the one-line wiring snippet.
 *
 * This hook centralises the type coercion. If a host project augments
 * Inertia's InertiaConfig.sharedPageProps with the same shape, the cast
 * becomes a no-op and full type-safety is restored throughout the package's
 * published pages.
 */
export function useEvoProps(): EvoSharedProps {
    const props = usePage().props as unknown as { evo?: EvoSharedProps };

    if (!props.evo) {
        throw new Error(
            'EvoDevOps Base: usePage().props.evo is missing. Add the evo shared prop to '
                + "HandleInertiaRequests::share() — see the package README's 'Wire EvoDevOps shared props into Inertia' step."
        );
    }

    return props.evo;
}
