export type SiteSocialImage = {
    url: string | null;
    alt: string | null;
    width: number | null;
    height: number | null;
    type: string | null;
    version: string | null;
};

export type SiteSharedProps = {
    name: string;
    titleTemplate: string;
    description: string;
    url: string;
    ogLocale: string;
    themeColor: string | null;
    robots: {
        default: string;
    };
    social: {
        image: SiteSocialImage;
        twitter: {
            site: string | null;
            creator: string | null;
        };
    };
    structuredData: {
        enabled: boolean;
        logo: string | null;
    };
};

export type JsonLdPayload = Record<string, unknown> | Record<string, unknown>[];
