<template>
  <div class="product-catalogue">
    <TickyHeader
      :title="t('ticky_crm', 'nav_products')"
      :subtitle="n('ticky_crm', 'product_count_singular', 'product_count_plural', products.length, { count: products.length })"
    >
      <template #actions>
        <NcButton type="primary" @click="openCreate">
          <template #icon>
            <IconPlus :size="20" />
          </template>
          {{ t('ticky_crm', 'product_create_submit') }}
        </NcButton>
      </template>
    </TickyHeader>

    <div class="product-catalogue__filters">
      <NcTextField
        v-model="searchQuery"
        :label="t('ticky_crm', 'product_search_placeholder')"
        class="product-catalogue__search"
      >
        <template #trailing-button-icon>
          <IconMagnify :size="18" />
        </template>
      </NcTextField>
    </div>

    <NcLoadingIcon v-if="loading" :size="48" class="product-catalogue__loading" />

    <div v-else-if="filteredProducts.length === 0" class="product-catalogue__empty">
      {{ searchQuery ? t('ticky_crm', 'product_no_results') : t('ticky_crm', 'product_empty') }}
    </div>

    <TickyTable v-else :value="filteredProducts" @row-click="openEdit">
      <TickyColumn field="sku"      :header="t('ticky_crm', 'field_sku')"      max-width="140px" />
      <TickyColumn field="name"     :header="t('ticky_crm', 'field_name')"     max-width="260px" />
      <TickyColumn field="category" :header="t('ticky_crm', 'field_category')" max-width="160px" />
      <TickyColumn field="price"    :header="t('ticky_crm', 'field_price')"    max-width="120px">
        <template #default="{ row }">
          {{ formatPrice(row.price, row.currency) }}
        </template>
      </TickyColumn>
      <TickyColumn field="unit"     :header="t('ticky_crm', 'field_unit')"     max-width="100px" />
      <TickyColumn field="status"   :header="t('ticky_crm', 'field_status')"   max-width="120px">
        <template #default="{ row }">
          <span :class="['product-status', `product-status--${row.status}`]">
            {{ statusLabel(row.status) }}
          </span>
        </template>
      </TickyColumn>
    </TickyTable>

    <ProductDialog
      v-model:open="dialogOpen"
      :product="editingProduct"
      @product-saved="handleSaved"
    />

    <NcDialog
      v-if="deletingProduct"
      :open="true"
      :name="t('ticky_crm', 'product_delete_confirm_title')"
      :buttons="deleteButtons"
      @close="deletingProduct = null"
    >
      {{ t('ticky_crm', 'product_delete_confirm_body', { name: deletingProduct.name }) }}
    </NcDialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { t, n } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import IconPlus from 'vue-material-design-icons/Plus.vue'
import IconMagnify from 'vue-material-design-icons/Magnify.vue'
import TickyTable from './TickyTable.vue'
import TickyColumn from './TickyColumn.vue'
import TickyHeader from './TickyHeader.vue'
import ProductDialog from '../dialogs/ProductDialog.vue'
import { getProducts, deleteProduct } from '../services/productService'

const products       = ref([])
const loading        = ref(true)
const searchQuery    = ref('')
const dialogOpen     = ref(false)
const editingProduct = ref(null)
const deletingProduct = ref(null)

const filteredProducts = computed(() => {
  const q = searchQuery.value.toLowerCase().trim()
  if (!q) return products.value
  return products.value.filter(p =>
    (p.sku      ?? '').toLowerCase().includes(q) ||
    (p.name     ?? '').toLowerCase().includes(q) ||
    (p.category ?? '').toLowerCase().includes(q)
  )
})

const openCreate = () => {
  editingProduct.value = null
  dialogOpen.value     = true
}

const openEdit = (product) => {
  editingProduct.value = product
  dialogOpen.value     = true
}

const handleSaved = (saved) => {
  const idx = products.value.findIndex(p => p.uuid === saved.uuid)
  if (idx !== -1) {
    products.value[idx] = saved
  } else {
    products.value.push(saved)
  }
}

const confirmDelete = (product) => {
  deletingProduct.value = product
}

const handleDelete = async () => {
  if (!deletingProduct.value) return
  try {
    await deleteProduct(deletingProduct.value.uuid)
    products.value = products.value.filter(p => p.uuid !== deletingProduct.value.uuid)
  } finally {
    deletingProduct.value = null
  }
}

const deleteButtons = computed(() => [
  {
    label:    t('ticky_crm', 'client_cancel'),
    type:     'normal',
    callback: () => { deletingProduct.value = null },
  },
  {
    label:    t('ticky_crm', 'product_delete_confirm'),
    type:     'error',
    callback: handleDelete,
  },
])

const statusLabel = (status) => {
  const map = {
    active:       t('ticky_crm', 'status_active'),
    inactive:     t('ticky_crm', 'status_inactive'),
    discontinued: t('ticky_crm', 'product_status_discontinued'),
  }
  return map[status] ?? status
}

const formatPrice = (price, currency) => {
  if (price === null || price === undefined || price === '') return '—'
  return new Intl.NumberFormat(undefined, {
    style: 'currency',
    currency: currency ?? 'EUR',
    minimumFractionDigits: 2,
  }).format(price)
}

onMounted(async () => {
  try {
    products.value = await getProducts()
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.product-catalogue {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding-bottom: 24px;
}

.product-catalogue__filters {
  display: flex;
  gap: 12px;
  align-items: center;
  padding: 0 4px;
}

.product-catalogue__search {
  max-width: 320px;
}

.product-catalogue__loading {
  margin: 48px auto;
  display: block;
}

.product-catalogue__empty {
  text-align: center;
  color: var(--color-text-maxcontrast);
  padding: 48px 0;
  font-size: 14px;
}

.product-status {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
}

.product-status--active {
  background-color: var(--color-success-background, #d4edda);
  color: var(--color-success, #155724);
}

.product-status--inactive {
  background-color: var(--color-warning-background, #fff3cd);
  color: var(--color-warning-text, #856404);
}

.product-status--discontinued {
  background-color: var(--color-error-background);
  color: var(--color-error);
}
</style>
