import { readFile } from 'node:fs/promises';
import { expect, test } from 'bun:test';

const source = await readFile(
    new URL('./dashboard.tsx', import.meta.url),
    'utf8',
);

test('admin order chart has a purposeful zero-activity state', () => {
    expect(source).toContain(
        'const hasOrderActivity = data.orderTrendData.some(',
    );
    expect(source).toMatch(/Belum ada aktivitas pesanan pada periode\s+ini\./);
});
