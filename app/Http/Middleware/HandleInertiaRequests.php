<?php

namespace App\Http\Middleware;

use App\Support\SiteMetadata;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Xuple\EvoLayer\Base\Support\EvoLayerProps;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // EvoLayer Base shared prop — consumed by published pages via
            // useEvoLayerProps() / useBrand(). EvoLayerProps::base() assembles
            // examples + features + brand from config (one DRY source), so hosts
            // rebrand the public explainer (evolayer/base) via
            // config('evolayer.base.brand') / env instead of editing the page file.
            'evolayer' => [
                'base' => EvoLayerProps::base(),
            ],
        ];
    }

    /**
     * Define the props that are shared once and remembered by the client.
     *
     * @see https://inertiajs.com/shared-data#sharing-once-props
     *
     * @return array<string, mixed>
     */
    public function shareOnce(Request $request): array
    {
        return [
            ...parent::shareOnce($request),
            'site' => fn () => SiteMetadata::inertiaDefaults(),
        ];
    }
}
