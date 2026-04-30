import { describe, it, expect } from 'vitest';
import { detectMultiVendorConflict, validateCartForSubmit } from './MultiVendorDetector';

describe('MultiVendorDetector', () => {
    describe('detectMultiVendorConflict', () => {
        it('returns no conflict for empty cart', () => {
            const result = detectMultiVendorConflict([]);
            expect(result.isConflict).toBe(false);
            expect(result.vendorIds).toEqual([]);
            expect(result.message).toBe('');
        });

        it('returns no conflict for null items', () => {
            const result = detectMultiVendorConflict(null);
            expect(result.isConflict).toBe(false);
        });

        it('returns no conflict for single vendor', () => {
            const items = [
                { productId: 'p1', vendorId: 'v1' },
                { productId: 'p2', vendorId: 'v1' },
            ];
            const result = detectMultiVendorConflict(items);
            expect(result.isConflict).toBe(false);
            expect(result.vendorIds).toEqual(['v1']);
        });

        it('detects conflict with two vendors', () => {
            const items = [
                { productId: 'p1', vendorId: 'v1' },
                { productId: 'p2', vendorId: 'v2' },
            ];
            const result = detectMultiVendorConflict(items);
            expect(result.isConflict).toBe(true);
            expect(result.vendorIds).toContain('v1');
            expect(result.vendorIds).toContain('v2');
            expect(result.vendorIds).toHaveLength(2);
            expect(result.message).toContain('2');
        });

        it('detects conflict with three vendors', () => {
            const items = [
                { productId: 'p1', vendorId: 'v1' },
                { productId: 'p2', vendorId: 'v2' },
                { productId: 'p3', vendorId: 'v3' },
            ];
            const result = detectMultiVendorConflict(items);
            expect(result.isConflict).toBe(true);
            expect(result.vendorIds).toHaveLength(3);
        });

        it('normalizes vendor IDs as strings', () => {
            const items = [
                { productId: 'p1', vendorId: 1 },
                { productId: 'p2', vendorId: '1' },
            ];
            const result = detectMultiVendorConflict(items);
            expect(result.isConflict).toBe(false);
        });
    });

    describe('validateCartForSubmit', () => {
        it('returns empty_cart for empty items', () => {
            const errors = validateCartForSubmit([], 'v1', { lat: 1, lon: 1 });
            expect(errors).toContain('empty_cart');
        });

        it('returns multi_vendor for conflicting cart', () => {
            const items = [
                { productId: 'p1', vendorId: 'v1' },
                { productId: 'p2', vendorId: 'v2' },
            ];
            const errors = validateCartForSubmit(items, 'v1', { lat: 1, lon: 1 });
            expect(errors).toContain('multi_vendor');
        });

        it('returns no_vendor when vendorId is missing', () => {
            const items = [{ productId: 'p1', vendorId: 'v1' }];
            const errors = validateCartForSubmit(items, '', { lat: 1, lon: 1 });
            expect(errors).toContain('no_vendor');
        });

        it('returns missing_address when coordinates are missing', () => {
            const items = [{ productId: 'p1', vendorId: 'v1' }];
            const errors = validateCartForSubmit(items, 'v1', null);
            expect(errors).toContain('missing_address');
        });

        it('returns no errors for valid cart', () => {
            const items = [{ productId: 'p1', vendorId: 'v1' }];
            const errors = validateCartForSubmit(items, 'v1', { lat: 1, lon: 1 });
            expect(errors).toHaveLength(0);
        });
    });
});
