import { usePage } from '@inertiajs/react';
import type { EvoLayerSharedProps } from '@/types/evolayer';

/**
 * Read the EvoLayer shared props from the current Inertia page.
 *
 * The host's HandleInertiaRequests middleware is expected to share an
 * `evolayer: { base: { examples, features } }` block — see the package README
 * for the one-line wiring snippet.
 *
 * This hook centralises the type coercion. If a host project augments
 * Inertia's InertiaConfig.sharedPageProps with the same shape, the cast
 * becomes a no-op and full type-safety is restored throughout the package's
 * published pages.
 */
export function useEvoLayerProps(): EvoLayerSharedProps {
    const props = usePage().props as unknown as {
        evolayer?: EvoLayerSharedProps;
    };

    if (!props.evolayer) {
        throw new Error(
            'EvoLayer Base: usePage().props.evolayer is missing. Add the evolayer shared prop to ' +
                "HandleInertiaRequests::share() — see the package README's 'Wire EvoLayer shared props into Inertia' step.",
        );
    }

    return props.evolayer;
}
