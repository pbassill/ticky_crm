<template>
  <div class="relations-tab">
    <!-- Add relation form -->
    <div class="add-relation-form">
      <NcSelect
        v-model="selectedClient"
        :options="otherClients"
        :loading="isLoadingClients"
        label="name"
        :placeholder="t('ticky_crm', 'relation_search_placeholder')"
        class="relations-select"
      >
        <template #option="option">
          <div class="client-option">
            <span class="client-option__name">{{ option.name }}</span>
            <span class="client-option__number text-muted">{{ option.client_number }}</span>
          </div>
        </template>
        <template #no-options>
          {{ t('ticky_crm', 'relation_no_clients') }}
        </template>
      </NcSelect>

      <NcSelect
        v-model="selectedType"
        :options="relationTypeOptions"
        label="label"
        :placeholder="t('ticky_crm', 'relation_type_placeholder')"
        class="relations-select"
      />

      <NcButton
        type="primary"
        :disabled="!selectedClient || !selectedType || isAdding"
        @click="handleAdd"
      >
        <template v-if="isAdding" #icon>
          <NcLoadingIcon :size="20" />
        </template>
        <template v-else #icon>
          <IconPlus :size="20" />
        </template>
        {{ t('ticky_crm', 'relation_add_button') }}
      </NcButton>
    </div>

    <!-- Relations list -->
    <NcLoadingIcon v-if="isLoading" :name="t('ticky_crm', 'relation_loading')" />

    <ul v-else class="relations-list">
      <li
        v-for="relation in relations"
        :key="relation.id"
        class="relation-item"
      >
        <div class="relation-info">
          <div class="relation-badge" :class="`relation-badge--${relation.relation_type}`">
            {{ getTypeLabel(relation.relation_type) }}
          </div>
          <div class="relation-client">
            <span class="relation-client__name">{{ relation.related_name }}</span>
            <span class="relation-client__number text-muted">{{ relation.related_client_number }}</span>
          </div>
        </div>
        <NcActions :aria-label="t('ticky_crm', 'relation_actions_label', { name: relation.related_name })">
          <NcActionButton @click="navigateToClient(relation.related_uuid)">
            <template #icon>
              <IconEye :size="20" />
            </template>
            {{ t('ticky_crm', 'relation_view_client') }}
          </NcActionButton>
          <NcActionButton
            :disabled="removingId === relation.id"
            @click="handleRemove(relation)"
          >
            <template #icon>
              <NcLoadingIcon v-if="removingId === relation.id" :size="20" />
              <IconDelete v-else :size="20" />
            </template>
            {{ t('ticky_crm', 'relation_remove') }}
          </NcActionButton>
        </NcActions>
      </li>

      <li v-if="relations.length === 0" class="empty-state text-muted">
        {{ t('ticky_crm', 'relation_empty_state') }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { t } from '@nextcloud/l10n'
import { NcButton, NcSelect, NcLoadingIcon, NcActions, NcActionButton } from '@nextcloud/vue'
import { showError, showSuccess } from '@nextcloud/dialogs'
import IconPlus from 'vue-material-design-icons/Plus.vue'
import IconDelete from 'vue-material-design-icons/Delete.vue'
import IconEye from 'vue-material-design-icons/Eye.vue'

import { getRelations, addRelation, deleteRelation } from '../services/clientRelationService'
import { getClients } from '../services/clientService'

const props = defineProps({
    clientUuid: { type: String, required: true },
    allClients: { type: Array, default: () => [] },
})

const emit = defineEmits(['navigate-to-client'])

// -------------------------------------------------------------------------
// State
// -------------------------------------------------------------------------
const relations      = ref([])
const isLoading      = ref(false)
const isAdding       = ref(false)
const removingId     = ref(null)
const selectedClient = ref(null)
const selectedType   = ref(null)
const allClients     = ref([])
const isLoadingClients = ref(false)

// -------------------------------------------------------------------------
// Computed
// -------------------------------------------------------------------------
const relationTypeOptions = computed(() => [
    { id: 'partner',         label: t('ticky_crm', 'relation_type_partner') },
    { id: 'subsidiary',      label: t('ticky_crm', 'relation_type_subsidiary') },
    { id: 'parent',          label: t('ticky_crm', 'relation_type_parent') },
    { id: 'reseller',        label: t('ticky_crm', 'relation_type_reseller') },
    { id: 'reseller_client', label: t('ticky_crm', 'relation_type_reseller_client') },
    { id: 'competitor',      label: t('ticky_crm', 'relation_type_competitor') },
    { id: 'other',           label: t('ticky_crm', 'relation_type_other') },
])

const relatedIds = computed(() => new Set(relations.value.map(r => r.related_uuid)))

const otherClients = computed(() =>
    allClients.value.filter(c => c.uuid !== props.clientUuid && !relatedIds.value.has(c.uuid))
)

function getTypeLabel(typeId) {
    return relationTypeOptions.value.find(o => o.id === typeId)?.label ?? typeId
}

// -------------------------------------------------------------------------
// Data fetching
// -------------------------------------------------------------------------
async function fetchRelations() {
    isLoading.value = true
    try {
        relations.value = await getRelations(props.clientUuid)
    } catch {
        showError(t('ticky_crm', 'relation_error_load'))
    } finally {
        isLoading.value = false
    }
}

async function fetchAllClients() {
    isLoadingClients.value = true
    try {
        allClients.value = await getClients()
    } catch {
        // non-critical, leave empty
    } finally {
        isLoadingClients.value = false
    }
}

// -------------------------------------------------------------------------
// Actions
// -------------------------------------------------------------------------
async function handleAdd() {
    if (!selectedClient.value || !selectedType.value) return

    isAdding.value = true
    try {
        const relation = await addRelation(
            props.clientUuid,
            selectedClient.value.uuid,
            selectedType.value.id,
        )
        relations.value.push(relation)
        showSuccess(t('ticky_crm', 'relation_success_added'))
        selectedClient.value = null
        selectedType.value   = null
    } catch (error) {
        showError(
            error.response?.data?.message ?? t('ticky_crm', 'relation_error_add'),
        )
    } finally {
        isAdding.value = false
    }
}

async function handleRemove(relation) {
    removingId.value = relation.id
    try {
        await deleteRelation(props.clientUuid, relation.id)
        relations.value = relations.value.filter(r => r.id !== relation.id)
        showSuccess(t('ticky_crm', 'relation_success_removed'))
    } catch {
        showError(t('ticky_crm', 'relation_error_remove'))
    } finally {
        removingId.value = null
    }
}

function navigateToClient(uuid) {
    emit('navigate-to-client', uuid)
}

// -------------------------------------------------------------------------
// Lifecycle
// -------------------------------------------------------------------------
watch(() => props.clientUuid, fetchRelations, { immediate: true })
onMounted(fetchAllClients)
</script>

<style scoped lang="scss">
.relations-tab {
    padding: 16px;
}

.add-relation-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
    align-items: flex-end;
}

.relations-select {
    width: 100%;
}

.relations-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.relation-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--color-border);
}

.relation-info {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.relation-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    white-space: nowrap;
    background-color: var(--color-background-dark);
    color: var(--color-text-maxcontrast);

    &--partner         { background-color: var(--color-primary-element-light); color: var(--color-primary-element-text); }
    &--subsidiary      { background-color: #e8f5e9; color: #2e7d32; }
    &--parent          { background-color: #e3f2fd; color: #1565c0; }
    &--reseller        { background-color: #fff3e0; color: #e65100; }
    &--reseller_client { background-color: #fce4ec; color: #880e4f; }
    &--competitor      { background-color: #fbe9e7; color: #bf360c; }
    &--other           { background-color: var(--color-background-dark); color: var(--color-text-maxcontrast); }
}

.relation-client {
    display: flex;
    flex-direction: column;
    min-width: 0;

    &__name   { font-weight: 600; }
    &__number { font-size: 0.85em; }
}

.client-option {
    display: flex;
    flex-direction: column;

    &__name   { font-weight: 600; }
    &__number { font-size: 0.85em; color: var(--color-text-maxcontrast); }
}

.empty-state {
    text-align: center;
    padding: 20px;
}
</style>
