import { usePage } from '@inertiajs/react';
import type { EvoLayerBrand, EvoLayerSharedProps } from '@/types/evolayer';

/**
 * Built-in brand defaults, mirroring config('evolayer.base.brand'). Used when
 * the host has not (yet) shared `evolayer.base.brand`, so published landing
 * pages render without crashing during gradual adoption.
 */
const FALLBACK_BRAND: EvoLayerBrand = {
    name: 'EvoLayer Base',
    tagline:
        'A fully working AI application layer for the official Laravel AI SDK.',
    description:
        'Start from a working application: Laravel auth, typed routes, structured AI workflows, admin screens, ontology tooling, and local verification commands already wired together.',
};

/**
 * Read the host's brand surface from the EvoLayer shared props.
 *
 * Unlike `useEvoLayerProps()`, this never throws: it falls back to the package
 * defaults when the host has not shared `evolayer.base.brand`. Wire
 * `EvoLayerProps::base()` into HandleInertiaRequests to make brand
 * host-controlled — so home/about can be rebranded without ejecting or
 * overwriting the page files.
 */
export function useBrand(): EvoLayerBrand {
    const props = usePage().props as unknown as {
        evolayer?: EvoLayerSharedProps;
    };

    return props.evolayer?.base?.brand ?? FALLBACK_BRAND;
}
