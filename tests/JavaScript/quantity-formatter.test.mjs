import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const source = readFileSync(
    new URL('../../public/assets/js/core/quantity.js', import.meta.url),
    'utf8',
);
const document = {
    addEventListener() {},
};
const window = { document };

vm.runInNewContext(source, { Intl, Number, Object, String, window });

const quantity = window.StoreApp.quantity;

test('quantity display uses Indonesian separators and removes trailing zeroes', () => {
    assert.equal(quantity.format('0.000'), '0');
    assert.equal(quantity.format('1.000'), '1');
    assert.equal(quantity.format('1.500'), '1,5');
    assert.equal(quantity.format('1.250'), '1,25');
    assert.equal(quantity.format('0.125'), '0,125');
    assert.equal(quantity.format('1000.500'), '1.000,5');
});

test('quantity input accepts comma and dot without losing fractional precision', () => {
    assert.equal(quantity.normalizeInput('1,5'), '1.5');
    assert.equal(quantity.normalizeInput('1.5'), '1.5');
    assert.equal(quantity.inputValue('1000.500'), '1000,5');
    assert.equal(quantity.normalizeInput('1.0001'), null);
});
