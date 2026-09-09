<template>
  <NcDialog
    v-model:open="showDialog"
    :name="isEdit ? t('ticky_crm', 'product_edit_title') : t('ticky_crm', 'product_create_title')"
    size="large"
    :buttons="buttons"
    :close-on-click-outside="!isLoading"
  >
    <div class="ticky-form-container">
      <div v-if="errorMessage" class="ticky-error-banner">
        {{ errorMessage }}
      </div>

      <fieldset class="ticky-fieldset">
        <legend>{{ t('ticky_crm', 'section_master_data') }}</legend>
        <div class="ticky-grid">
          <NcTextField
            v-model="form.sku"
            :label="t('ticky_crm', 'field_sku')"
            :disabled="isLoading"
            required
          />

          <NcTextField
            v-model="form.name"
            :label="t('ticky_crm', 'field_name')"
            :disabled="isLoading"
            required
          />

          <NcTextField
            v-model="form.category"
            :label="t('ticky_crm', 'field_category')"
            :disabled="isLoading"
          />

          <NcSelect
            v-bind="statusSelectConfig"
            v-model="statusSelectConfig.value"
            :disabled="isLoading"
          />
        </div>
      </fieldset>

      <fieldset class="ticky-fieldset">
        <legend>{{ t('ticky_crm', 'section_pricing') }}</legend>
        <div class="ticky-grid">
          <NcTextField
            v-model="form.price"
            :label="t('ticky_crm', 'field_price')"
            type="number"
            step="0.01"
            min="0"
            :disabled="isLoading"
          />

          <NcTextField
            v-model="form.currency"
            :label="t('ticky_crm', 'field_currency')"
            :disabled="isLoading"
            maxlength="3"
          />

          <NcTextField
            v-model="form.unit"
            :label="t('ticky_crm', 'field_unit')"
            :disabled="isLoading"
          />
        </div>
      </fieldset>

      <fieldset class="ticky-fieldset">
        <legend>{{ t('ticky_crm', 'field_description') }}</legend>
        <NcTextArea
          v-model="form.description"
          :label="t('ticky_crm', 'field_description')"
          :disabled="isLoading"
          class="ticky-textarea"
          :rows="4"
        />
      </fieldset>
    </div>
  </NcDialog>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { t } from '@nextcloud/l10n'
import { NcDialog, NcTextField, NcTextArea, NcSelect } from '@nextcloud/vue'
import { createProduct, updateProduct } from '../services/productService'

const props = defineProps({
  open:    { type: Boolean, default: false },
  product: { type: Object,  default: null },
})
const emit = defineEmits(['update:open', 'product-saved'])

const showDialog = computed({
  get: () => props.open,
  set: (v) => emit('update:open', v),
})

const isEdit    = computed(() => !!props.product)
const isLoading = ref(false)
const errorMessage = ref('')

const statusSelectConfig = reactive({
  inputLabel: t('ticky_crm', 'field_status'),
  options: [
    { id: 'active',       label: t('ticky_crm', 'status_active') },
    { id: 'inactive',     label: t('ticky_crm', 'status_inactive') },
    { id: 'discontinued', label: t('ticky_crm', 'product_status_discontinued') },
  ],
  value: null,
})

const initialForm = () => ({
  sku:         '',
  name:        '',
  description: '',
  price:       '',
  currency:    'EUR',
  unit:        '',
  category:    '',
})

const form = ref(initialForm())

const syncFromProduct = () => {
  if (props.product) {
    form.value = {
      sku:         props.product.sku         ?? '',
      name:        props.product.name        ?? '',
      description: props.product.description ?? '',
      price:       props.product.price       ?? '',
      currency:    props.product.currency    ?? 'EUR',
      unit:        props.product.unit        ?? '',
      category:    props.product.category    ?? '',
    }
    statusSelectConfig.value = statusSelectConfig.options.find(o => o.id === props.product.status)
      ?? statusSelectConfig.options[0]
  } else {
    form.value = initialForm()
    statusSelectConfig.value = statusSelectConfig.options[0]
  }
}

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    syncFromProduct()
    errorMessage.value = ''
  }
})

const handleSubmit = async () => {
  if (!form.value.sku.trim() || !form.value.name.trim()) {
    errorMessage.value = t('ticky_crm', 'error_required_fields')
    return
  }

  isLoading.value    = true
  errorMessage.value = ''

  try {
    const payload = {
      ...form.value,
      status: statusSelectConfig.value?.id ?? 'active',
    }

    let saved
    if (isEdit.value) {
      saved = await updateProduct(props.product.uuid, payload)
    } else {
      saved = await createProduct(payload)
    }

    emit('product-saved', saved)
    showDialog.value = false
  } catch (error) {
    if (error.response?.data?.error === 'duplicate_sku') {
      errorMessage.value = t('ticky_crm', 'error_duplicate_sku')
    } else {
      errorMessage.value = error.response?.data?.message || error.message || t('ticky_crm', 'error_save')
    }
  } finally {
    isLoading.value = false
  }
}

const buttons = computed(() => [
  {
    label:    t('ticky_crm', 'client_cancel'),
    type:     'normal',
    callback: () => { showDialog.value = false },
    disabled: isLoading.value,
  },
  {
    label:    isLoading.value
                ? t('ticky_crm', 'client_create_saving')
                : (isEdit.value ? t('ticky_crm', 'product_save') : t('ticky_crm', 'product_create_submit')),
    type:     'primary',
    callback: handleSubmit,
    disabled: isLoading.value || !form.value.sku.trim() || !form.value.name.trim(),
  },
])
</script>

<style scoped>
.ticky-form-container {
  display: flex;
  flex-direction: column;
  gap: 28px;
  padding: 8px 24px 16px;
  width: 680px;
  max-width: 100%;
  box-sizing: border-box;
  margin: 0 auto;
}

.ticky-error-banner {
  background-color: var(--color-error-background);
  color: var(--color-error);
  border-left: 4px solid var(--color-error);
  padding: 12px 16px;
  border-radius: var(--border-radius);
  font-size: 13px;
}

.ticky-fieldset {
  border: none;
  padding: 0;
  margin: 0;

  legend {
    font-weight: 600;
    font-size: 11px;
    color: var(--color-text-maxcontrast);
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 0;
  }
}

.ticky-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px 20px;
}

.ticky-textarea {
  width: 100%;
}
</style>
