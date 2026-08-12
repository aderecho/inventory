import { computed } from 'vue'
import { useAuth } from '@/Composables/useAuth'

export function usePermissions() {
  const { can } = useAuth()

  // Inventory
  const inventoryActions = computed(() => {
    const result = []
    if (can('view inventory'))   result.push('view')
    if (can('edit inventory'))   result.push('edit')
    if (can('delete inventory')) result.push('delete')
    if (can('print inventory'))  result.push('print')
    return result
  })

  // Disposal
  const disposalActions = computed(() => {
    const result = []
    if (can('view inventory'))   result.push('view')
    return result
  })

  // Inspection
  const inspectionActions = computed(() => {
    const result = []
    if (can('view inventory'))   result.push('view')
    return result
  })

  const canViewInventory   = computed(() => can('view inventory'))
  const canCreateInventory = computed(() => can('create inventory'))
  const canImportInventory = computed(() => can('import inventory'))
  const canExportInventory = computed(() => can('export inventory'))

  // Suppliers
  const supplierActions = computed(() => {
    const result = []
    if (can('edit suppliers'))   result.push('edit')
    if (can('delete suppliers')) result.push('delete')
    return result
  })

  const canCreateSupplier = computed(() => can('create suppliers'))

  // Categories
  const categoryActions = computed(() => {
    const result = []
    if (can('edit categories'))   result.push('edit')
    if (can('delete categories')) result.push('delete')
    return result
  })

  const canCreateCategory = computed(() => can('create categories'))

  // Acknowledgements
  const canViewAcknowledgements   = computed(() => can('view acknowledgements'))
  const canCreateAcknowledgements = computed(() => can('create acknowledgements'))
  const canShowAcknowledgements   = computed(() => can('show acknowledgements'))
  const canUploadAcknowledgements = computed(() => can('upload acknowledgements'))

  // Users
  const userActions = computed(() => {
    const result = []
    if (can('view users'))   result.push('view')
    if (can('edit users'))   result.push('edit')
    if (can('delete users')) result.push('delete')
    return result
  })

  const canCreateUser = computed(() => can('create users'))

  // Roles
  const roleActions = computed(() => {
    const result = []
    if (can('view roles'))   result.push('view')
    if (can('edit roles'))   result.push('edit')
    if (can('delete roles')) result.push('delete')
    return result
  })

  const canCreateRole = computed(() => can('create roles'))

  // Archives
  const archiveItemActions = computed(() => {
    const result = []
    if (can('view archive_item'))         result.push('view')
    if (can('restore archive_item'))      result.push('restore')
    if (can('force delete archive_item')) result.push('force-delete')
    return result
  })

  const archiveSupplierActions = computed(() => {
    const result = []
    if (can('view archive_supplier'))         result.push('view')
    if (can('restore archive_supplier'))      result.push('restore')
    if (can('force delete archive_supplier')) result.push('force-delete')
    return result
  })

  return {
    // Inventory
    inventoryActions,
    canViewInventory,
    canCreateInventory,
    canImportInventory,
    canExportInventory,

    //Disposal
    disposalActions,

    //Inspection
    inspectionActions,

    // Suppliers
    supplierActions,
    canCreateSupplier,

    // Categories
    categoryActions,
    canCreateCategory,

    // Acknowledgements
    canViewAcknowledgements,
    canCreateAcknowledgements,
    canShowAcknowledgements,
    canUploadAcknowledgements,

    // Users
    userActions,
    canCreateUser,

    // Roles
    roleActions,
    canCreateRole,

    // Archives
    archiveItemActions,
    archiveSupplierActions,
  }
}