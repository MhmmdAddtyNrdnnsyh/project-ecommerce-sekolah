import { readFile } from 'node:fs/promises';
import { expect, test } from 'bun:test';

const source = await readFile(new URL('./index.tsx', import.meta.url), 'utf8');

test('product form is opened from a dialog instead of rendered inline', () => {
    expect(source).toContain('<Dialog');
    expect(source).toContain('<DialogTrigger asChild>');
    expect(source.indexOf('action="/admin-jurusan/products"')).toBeGreaterThan(
        source.indexOf('<DialogContent'),
    );
    expect(source.match(/action="\/admin-jurusan\/products"/g)).toHaveLength(1);
});
