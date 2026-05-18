export function collectionItems(data) {
  if (Array.isArray(data)) {
    return data;
  }
  if (data && Array.isArray(data['hydra:member'])) {
    return data['hydra:member'];
  }
  return [];
}

export function collectionTotal(data) {
  if (data && typeof data['hydra:totalItems'] === 'number') {
    return data['hydra:totalItems'];
  }
  return collectionItems(data).length;
}

/** @returns {number|null} */
export function numericIdFromIri(iri) {
  if (!iri || typeof iri !== 'string') {
    return null;
  }
  const m = iri.match(/\/(\d+)$/);
  return m ? Number(m[1]) : null;
}
