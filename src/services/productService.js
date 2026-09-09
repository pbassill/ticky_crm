import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';

const base = '/apps/ticky_crm/api/v1/products';

export const getProducts = async () => {
    const response = await axios.get(generateUrl(base));
    return response.data;
};

export const createProduct = async (data) => {
    const response = await axios.post(generateUrl(base), data);
    return response.data;
};

export const updateProduct = async (uuid, data) => {
    const response = await axios.put(generateUrl(`${base}/${uuid}`), data);
    return response.data;
};

export const deleteProduct = async (uuid) => {
    await axios.delete(generateUrl(`${base}/${uuid}`));
};
