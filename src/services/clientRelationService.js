import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 * Fetches all relations for a client (both directions).
 * @param {string} clientUuid
 */
export const getRelations = async (clientUuid) => {
    const url = generateUrl(`/apps/ticky_crm/api/v1/clients/${clientUuid}/relations`)
    const response = await axios.get(url)
    return response.data
}

/**
 * Creates a new relation between two clients.
 * @param {string} clientUuid
 * @param {string} relatedUuid
 * @param {string} relationType
 * @param {string|null} notes
 */
export const addRelation = async (clientUuid, relatedUuid, relationType, notes = null) => {
    const url = generateUrl(`/apps/ticky_crm/api/v1/clients/${clientUuid}/relations`)
    const response = await axios.post(url, { related_uuid: relatedUuid, relation_type: relationType, notes })
    return response.data
}

/**
 * Deletes a relation by id.
 * @param {string} clientUuid
 * @param {number} relationId
 */
export const deleteRelation = async (clientUuid, relationId) => {
    const url = generateUrl(`/apps/ticky_crm/api/v1/clients/${clientUuid}/relations/${relationId}`)
    await axios.delete(url)
    return true
}
