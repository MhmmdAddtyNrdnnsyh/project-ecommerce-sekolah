import { describe, expect, test } from 'bun:test';
import { getSearchConfig } from '@/components/app-sidebar-header';

const url = (href) => (typeof href === 'string' ? href : href.url);

describe('dashboard header search', () => {
    test.each([
        ['picket_officer', null],
        ['admin_jurusan', null],
    ])('does not expose search for %s', (role, expected) => {
        expect(getSearchConfig(role, 'kaos')).toBe(expected);
    });

    test.each([
        ['buyer', 'Pencarian katalog', '/catalog?search=kaos'],
        ['seller', 'Pencarian seller', '/seller/products?q=kaos'],
        ['admin', 'Pencarian admin', '/admin/products?q=kaos'],
    ])('uses the %s search destination', (role, ariaLabel, expectedUrl) => {
        const config = getSearchConfig(role, 'kaos');

        expect(config?.ariaLabel).toBe(ariaLabel);
        expect(url(config.targets[0].href)).toBe(expectedUrl);
    });
});
