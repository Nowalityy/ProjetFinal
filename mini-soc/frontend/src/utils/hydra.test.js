import { describe, expect, it } from 'vitest';
import { collectionItems, collectionTotal, numericIdFromIri } from './hydra';

describe('numericIdFromIri', () => {
  it('parses trailing id', () => {
    expect(numericIdFromIri('/api/auth_logs/12')).toBe(12);
  });
});

describe('collectionItems', () => {
  it('handles hydra', () => {
    expect(collectionItems({ 'hydra:member': [1, 2] })).toEqual([1, 2]);
  });
  it('handles plain array', () => {
    expect(collectionItems([3])).toEqual([3]);
  });
});

describe('collectionTotal', () => {
  it('uses hydra total when present', () => {
    expect(collectionTotal({ 'hydra:member': [], 'hydra:totalItems': 12 })).toBe(12);
  });
});
