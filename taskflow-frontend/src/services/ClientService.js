import api from './api'

export default {
    getAll(params) {
        return api.get('clients', { params })
    },

    get(id) {
        return api.get(`/clients/${id}`)
    },

    getOne(id) {
        return api.get(`/clients/${id}`)
    },

    create(data) {
        return api.post('clients', data)
    },

    update(id, data) {
        return api.put(`/clients/${id}`, data)
    },

    delete(id) {
        return api.delete(`/clients/${id}`)
    },

    // Contacts
    addContact(clientId, data) {
        return api.post(`/clients/${clientId}/contacts`, data)
    },
    updateContact(contactId, data) {
        return api.put(`/contacts/${contactId}`, data)
    },
    deleteContact(contactId) {
        return api.delete(`/contacts/${contactId}`)
    },

    // Attachments
    addAttachment(clientId, formData, onUploadProgress) {
        return api.post(`/clients/${clientId}/attachments`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress
        })
    },
    deleteAttachment(clientId, attachmentId) {
        return api.delete(`/clients/${clientId}/attachments/${attachmentId}`)
    }
}
