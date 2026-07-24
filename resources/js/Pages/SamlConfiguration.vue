<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Check, Clipboard, MoreVertical, Pencil, X,
} from 'lucide-vue-next';
import SideBar from '@/Components/SideBar.vue';
import NavHeader from '@/Components/NavHeader.vue';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import { useLoading } from '@/Composables/useLoading';
import { useSidebar } from '@/Composables/useSidebar';

const props = defineProps({
    configurations: { type: Array, default: () => [] },
    endpoints: { type: Object, default: () => ({}) },
});

const { isSidebarOpen, toggleSidebar } = useSidebar();
const { isLoading, loadingTitle, loadingMessage } = useLoading();

const page = usePage();
const configurations = ref([...props.configurations]);
const editingId = ref(null);
const saving = ref(false);
const deletingId = ref(null);
const openActionsId = ref(null);
const message = ref('');
const error = ref('');
const fieldErrors = ref({});

const activeProvider = computed(() => configurations.value.find((item) => item.is_active));
const configurationValid = computed(() => Boolean(
    activeProvider.value?.status === 'active'
    && activeProvider.value?.entity_id
    && activeProvider.value?.sso_url
    && activeProvider.value?.x509_cert
));

const blankForm = () => ({
    name: '', metadata_xml: '', entity_id: '', sso_url: '', slo_url: '', x509_cert: '',
    default_relay_state: '/dashboard', status: 'active', is_active: false,
});
const form = reactive(blankForm());

function resetForm(scroll = true) {
    Object.assign(form, blankForm());
    editingId.value = null;
    fieldErrors.value = {};
    if (scroll) requestAnimationFrame(() => document.getElementById('configuration-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

function editConfiguration(configuration) {
    Object.assign(form, {
        name: configuration.name,
        metadata_xml: configuration.metadata_xml || '',
        entity_id: configuration.entity_id,
        sso_url: configuration.sso_url,
        slo_url: configuration.slo_url || '',
        x509_cert: configuration.x509_cert,
        default_relay_state: configuration.default_relay_state || '/dashboard',
        status: configuration.status || 'active',
        is_active: configuration.is_active,
    });
    editingId.value = configuration.id;
    fieldErrors.value = {};
    document.getElementById('configuration-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function configurationPayload(configuration, overrides = {}) {
    return {
        name: configuration.name,
        metadata_xml: configuration.metadata_xml || '',
        entity_id: configuration.entity_id,
        sso_url: configuration.sso_url,
        slo_url: configuration.slo_url || '',
        x509_cert: configuration.x509_cert,
        default_relay_state: configuration.default_relay_state || '/dashboard',
        status: configuration.status || 'active',
        is_active: Boolean(configuration.is_active),
        ...overrides,
    };
}

function importMetadata() {
    message.value = ''; error.value = ''; fieldErrors.value = {};
    let xml = form.metadata_xml.trim();
    if (!xml) {
        error.value = 'Paste the IdP metadata XML before importing.';
        return;
    }

    if (xml.startsWith('```')) {
        xml = xml.replace(/^```(?:xml)?\s*/i, '').replace(/\s*```$/, '').trim();
    }

    const entityMatch = xml.match(/<([A-Za-z_][\w.-]*:)?EntityDescriptor\b/);
    const hasClosingEntity = /<\/(?:[A-Za-z_][\w.-]*:)?EntityDescriptor\s*>/.test(xml);
    if (entityMatch && !hasClosingEntity) {
        xml += `\n</${entityMatch[1] || ''}EntityDescriptor>`;
        form.metadata_xml = xml;
    }

    const document = new DOMParser().parseFromString(xml, 'application/xml');
    if (document.querySelector('parsererror')) {
        error.value = 'The IdP metadata XML could not be parsed.';
        return;
    }

    const entity = [...document.getElementsByTagNameNS('*', 'EntityDescriptor')][0];
    const ssoServices = [...document.getElementsByTagNameNS('*', 'SingleSignOnService')];
    const redirectBinding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
    const sso = ssoServices.find((node) => node.getAttribute('Binding') === redirectBinding) || ssoServices[0];
    const slo = [...document.getElementsByTagNameNS('*', 'SingleLogoutService')][0];
    const certificateNode = [...document.getElementsByTagNameNS('*', 'X509Certificate')][0];

    form.entity_id = entity?.getAttribute('entityID')?.trim() || form.entity_id;
    form.sso_url = sso?.getAttribute('Location')?.trim() || form.sso_url;
    form.slo_url = slo?.getAttribute('Location')?.trim() || form.slo_url;
    if (certificateNode?.textContent?.trim()) {
        const certificate = certificateNode.textContent.replace(/\s+/g, '');
        form.x509_cert = `-----BEGIN CERTIFICATE-----\n${certificate}\n-----END CERTIFICATE-----`;
    }

    if (!form.entity_id || !form.sso_url || !form.x509_cert) {
        error.value = 'Metadata imported partially. Review the missing required fields below.';
        return;
    }

    message.value = 'IdP metadata imported. Review the extracted values, then save the configuration.';
}

async function updateProviderState(configuration, overrides, successMessage) {
    message.value = ''; error.value = ''; openActionsId.value = null;
    try {
        const { data } = await axios.put(
            route('saml_configurations.update', configuration.id),
            configurationPayload(configuration, overrides),
        );
        if (data.configuration.is_active) {
            configurations.value = configurations.value.map((item) => ({ ...item, is_active: false }));
        }
        const index = configurations.value.findIndex((item) => item.id === configuration.id);
        if (index >= 0) configurations.value[index] = data.configuration;
        message.value = successMessage || data.message;
    } catch (exception) {
        error.value = exception.response?.data?.message || 'Unable to update the SAML provider.';
    }
}

async function saveConfiguration() {
    saving.value = true; message.value = ''; error.value = ''; fieldErrors.value = {};
    try {
        const url = editingId.value ? route('saml_configurations.update', editingId.value) : route('saml_configurations.store');
        const { data } = await axios[editingId.value ? 'put' : 'post'](url, form);
        if (data.configuration.is_active) configurations.value = configurations.value.map((item) => ({ ...item, is_active: false }));
        const index = configurations.value.findIndex((item) => item.id === data.configuration.id);
        if (index >= 0) configurations.value[index] = data.configuration;
        else configurations.value.unshift(data.configuration);
        message.value = data.message;
        resetForm(false);
    } catch (exception) {
        fieldErrors.value = exception.response?.data?.errors || {};
        error.value = exception.response?.data?.message || 'Unable to save the SAML configuration.';
    } finally { saving.value = false; }
}

async function deleteConfiguration(configuration) {
    if (!window.confirm(`Delete ${configuration.name}?`)) return;
    deletingId.value = configuration.id; message.value = ''; error.value = ''; openActionsId.value = null;
    try {
        const { data } = await axios.delete(route('saml_configurations.destroy', configuration.id));
        configurations.value = configurations.value.filter((item) => item.id !== configuration.id);
        if (editingId.value === configuration.id) resetForm(false);
        message.value = data.message;
    } catch (exception) {
        error.value = exception.response?.data?.message || 'Unable to delete the SAML configuration.';
    } finally { deletingId.value = null; }
}

async function copyEndpoint(value) {
    await navigator.clipboard.writeText(value);
    message.value = 'Endpoint copied to clipboard.';
}
</script>

<template>
    <LoadingOverlay :show="isLoading" :title="loadingTitle" :message="loadingMessage" />

    <div class="h-screen flex flex-col bg-gray-50/50">
        <div class="flex flex-1 overflow-hidden">
            <aside class="h-full transition-all duration-300 ease-in-out flex-shrink-0">
                <SideBar :isOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
            </aside>

            <div class="flex flex-col flex-1 overflow-hidden">
                <NavHeader :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />

                <main class="flex-1 p-4 sm:p-6 md:p-8 overflow-y-auto">
                    <div class="saml-config">
                        <h1>SAML Configuration</h1>

                        <div v-if="message" class="notice success">{{ message }}</div>
                        <div v-if="error" class="notice danger">{{ error }}</div>

                        <section class="status-panel">
                            <div class="connection-status status-cell">
                                <div class="status-check" :class="{ invalid: !configurationValid }"><Check v-if="configurationValid" /><X v-else /></div>
                                <div><strong>SAML connection</strong><span>{{ configurationValid ? 'All systems operational' : 'Configuration required' }}</span></div>
                            </div>
                            <div class="status-cell"><span>Active provider</span><strong>{{ activeProvider?.name || 'None selected' }}</strong></div>
                            <div class="status-cell"><span>Last successful login</span><strong>{{ activeProvider?.last_successful_login_at || 'No SAML login yet' }}</strong></div>
                            <div class="status-cell"><span>Configuration status</span><strong>{{ configurationValid ? 'Valid' : 'Not configured' }}</strong></div>
                        </section>

                        <section class="providers-section">
                            <div class="section-heading"><h2>Identity providers</h2><button class="primary-button" @click="resetForm(true)">Add configuration</button></div>
                            <div class="table-wrap">
                                <table>
                                    <thead><tr><th>Name</th><th>Entity ID</th><th>Status</th><th>Last used</th><th class="actions-heading">Actions</th></tr></thead>
                                    <tbody>
                                        <tr v-if="!configurations.length"><td colspan="5" class="empty-state">No identity providers configured.</td></tr>
                                        <tr v-for="configuration in configurations" :key="configuration.id">
                                            <td>{{ configuration.name }}<span v-if="configuration.is_active"> (Default)</span></td>
                                            <td class="entity-cell" :title="configuration.entity_id">{{ configuration.entity_id }}</td>
                                            <td><span class="status-pill" :class="configuration.status"><i></i>{{ configuration.status === 'active' ? 'Active' : 'Inactive' }}</span></td>
                                            <td>{{ configuration.last_used_at || '—' }}</td>
                                            <td>
                                                <div class="row-actions">
                                                    <button aria-label="Edit configuration" @click="editConfiguration(configuration)"><Pencil /></button>
                                                    <div class="actions-menu-wrap">
                                                        <button aria-label="Provider actions" :aria-expanded="openActionsId === configuration.id" @click="openActionsId = openActionsId === configuration.id ? null : configuration.id"><MoreVertical /></button>
                                                        <div v-if="openActionsId === configuration.id" class="actions-menu">
                                                            <button v-if="!configuration.is_active" @click="updateProviderState(configuration, { is_active: true, status: 'active' }, `${configuration.name} is now the default provider.`)">Set as default</button>
                                                            <button @click="updateProviderState(configuration, { status: configuration.status === 'active' ? 'inactive' : 'active', is_active: configuration.status === 'active' ? false : configuration.is_active }, `${configuration.name} ${configuration.status === 'active' ? 'deactivated' : 'activated'}.`)">{{ configuration.status === 'active' ? 'Deactivate' : 'Activate' }}</button>
                                                            <button class="danger-action" :disabled="deletingId === configuration.id" @click="deleteConfiguration(configuration)">Delete</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="editor-grid">
                            <form id="configuration-form" class="panel configuration-panel" @submit.prevent="saveConfiguration">
                                <div class="form-title"><h2>{{ editingId ? 'Edit configuration' : 'Configuration' }}</h2><button v-if="editingId" type="button" @click="resetForm(false)">Create new</button></div>
                                <div class="form-fields">
                                    <label class="field"><span>Name</span><div><input v-model="form.name" placeholder="e.g., UP Cebu SSO" /><small v-if="fieldErrors.name">{{ fieldErrors.name[0] }}</small></div></label>
                                    <label class="field metadata-field">
                                        <span>IdP Metadata XML</span>
                                        <div>
                                            <textarea v-model="form.metadata_xml" rows="5" placeholder="Paste the authoritative IdP metadata XML here"></textarea>
                                            <div class="metadata-help">
                                                <p>Imports the Entity ID, SSO URL, SLO URL, and X.509 signing certificate.</p>
                                                <button type="button" class="metadata-button" @click="importMetadata">Import metadata</button>
                                            </div>
                                            <small v-if="fieldErrors.metadata_xml">{{ fieldErrors.metadata_xml[0] }}</small>
                                        </div>
                                    </label>
                                    <label class="field"><span>Entity ID</span><div><input v-model="form.entity_id" placeholder="e.g., https://idp.example.com/saml/metadata" /><small v-if="fieldErrors.entity_id">{{ fieldErrors.entity_id[0] }}</small></div></label>
                                    <label class="field"><span>SSO URL</span><div><input v-model="form.sso_url" type="url" placeholder="e.g., https://idp.example.com/saml/sso" /><small v-if="fieldErrors.sso_url">{{ fieldErrors.sso_url[0] }}</small></div></label>
                                    <label class="field"><span>SLO URL</span><div><input v-model="form.slo_url" type="url" placeholder="e.g., https://idp.example.com/saml/slo" /><small v-if="fieldErrors.slo_url">{{ fieldErrors.slo_url[0] }}</small></div></label>
                                    <label class="field certificate-field"><span>X.509 Certificate</span><div><textarea v-model="form.x509_cert" rows="3" placeholder="-----BEGIN CERTIFICATE-----&#10;.....&#10;-----END CERTIFICATE-----"></textarea><p>Paste the IdP signing certificate (Base64 encoded).</p><small v-if="fieldErrors.x509_cert">{{ fieldErrors.x509_cert[0] }}</small></div></label>
                                    <label class="field"><span>Default Relay State</span><div><input v-model="form.default_relay_state" placeholder="e.g., /dashboard" /></div></label>
                                    <label class="field"><span>Active Provider</span><span class="checkbox-row"><input v-model="form.is_active" type="checkbox" /> Set as the default active provider</span></label>
                                </div>
                                <div class="form-actions"><button class="primary-button save-button" :disabled="saving">{{ saving ? 'Saving…' : 'Save configuration' }}</button><a :href="route('saml.login')" class="secondary-button">Test SAML Login</a></div>
                            </form>

                            <aside class="panel endpoints-panel">
                                <h2>Local endpoints</h2>
                                <div class="endpoint-list">
                                    <div v-for="(value, key) in endpoints" :key="key" class="endpoint-item">
                                        <label>{{ key === 'metadata' ? 'Metadata URL' : key === 'acs' ? 'ACS URL' : 'Logout URL' }}</label>
                                        <div class="endpoint-input"><span>{{ value }}</span><button type="button" aria-label="Copy endpoint" @click="copyEndpoint(value)"><Clipboard /></button></div>
                                        <p>{{ key === 'metadata' ? 'URL to the SP metadata that you can provide to your Identity Provider.' : key === 'acs' ? 'Assertion Consumer Service (ACS) endpoint.' : 'Single Logout (SLO) endpoint.' }}</p>
                                    </div>
                                </div>
                            </aside>
                        </section>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
.saml-config { --green: #006142; --deep-green: #00563c; --border: #d9dde2; font-family: Figtree, Arial, sans-serif; font-size: 14px; color: #111418; }
.saml-config * { box-sizing: border-box; }
h1 { margin: 0; font-size: 33px; line-height: 1.15; letter-spacing: -.035em; font-weight: 500; color: #0a0b0c; }
h2 { margin: 0; font-size: 16px; font-weight: 600; color: #151719; }
.notice { margin-top: 14px; border: 1px solid; border-radius: 7px; padding: 10px 14px; font-size: 13px; }
.notice.success { border-color: #b8dfce; background: #f0faf5; color: #006142; }
.notice.danger { border-color: #f0bcbc; background: #fff4f4; color: #a51515; }
.status-panel { min-height: 82px; margin-top: 18px; display: grid; grid-template-columns: 1.08fr 1fr 1fr 1fr; align-items: center; border: 1px solid var(--border); border-radius: 7px; background: #fff; }
.status-cell { min-height: 38px; padding: 0 28px; display: flex; flex-direction: column; justify-content: center; border-left: 1px solid var(--border); }
.status-cell:first-child { border-left: 0; }
.status-cell span, .status-cell strong { display: block; font-size: 13px; line-height: 1.35; font-weight: 400; }
.status-cell strong { margin-top: 4px; color: var(--green); font-weight: 600; }
.connection-status { flex-direction: row; align-items: center; justify-content: flex-start; gap: 16px; padding-left: 18px; }
.connection-status strong { margin: 0; color: #151719; }
.connection-status span { margin-top: 4px; color: #262a2e; }
.status-check { width: 38px; height: 38px; display: grid; place-items: center; flex: none; border: 3px solid var(--green); border-radius: 50%; color: var(--green); }
.status-check svg { width: 23px; height: 23px; stroke-width: 2.6; }
.status-check.invalid { border-color: #aeb4bb; color: #7a8188; }
.providers-section { margin-top: 17px; }
.section-heading { min-height: 38px; display: flex; align-items: center; justify-content: space-between; padding: 0 14px 7px 7px; }
.primary-button, .secondary-button { min-height: 37px; display: inline-flex; align-items: center; justify-content: center; border-radius: 5px; padding: 0 21px; font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; }
.primary-button { border: 1px solid #087353; color: white; background: linear-gradient(#087151, #00573f); box-shadow: inset 0 1px rgba(255,255,255,.13); }
.primary-button:hover { background: #004c37; }
.primary-button:disabled { opacity: .55; cursor: wait; }
.table-wrap { overflow-x: auto; border: 1px solid var(--border); border-radius: 7px; }
table { width: 100%; min-width: 850px; border-collapse: collapse; table-layout: fixed; font-size: 13px; }
th, td { height: 52px; padding: 0 18px; border-bottom: 1px solid var(--border); text-align: left; font-weight: 400; white-space: nowrap; }
th { height: 41px; font-weight: 600; }
tr:last-child td { border-bottom: 0; }
th:nth-child(1), td:nth-child(1) { width: 17%; }
th:nth-child(2), td:nth-child(2) { width: 40%; }
th:nth-child(3), td:nth-child(3) { width: 13%; }
th:nth-child(4), td:nth-child(4) { width: 20%; }
th:nth-child(5), td:nth-child(5) { width: 10%; }
.entity-cell { overflow: hidden; text-overflow: ellipsis; }
.actions-heading { text-align: center; }
.status-pill { display: inline-flex; align-items: center; gap: 7px; border: 1px solid; border-radius: 12px; padding: 3px 9px; font-size: 12px; }
.status-pill i { width: 6px; height: 6px; border-radius: 50%; }
.status-pill.active { border-color: #bde5d1; background: #f1fbf6; color: #23815d; }
.status-pill.active i { background: #1d9569; }
.status-pill.inactive { border-color: #d9dde1; background: #f7f8f9; color: #636a71; }
.status-pill.inactive i { background: #697078; }
.row-actions { display: flex; justify-content: center; gap: 8px; }
.row-actions button { width: 34px; height: 34px; display: grid; place-items: center; border: 1px solid var(--border); border-radius: 5px; background: white; color: #4c535a; cursor: pointer; }
.row-actions button:hover { background: #f5f7f8; color: var(--green); }
.row-actions svg { width: 17px; height: 17px; }
.actions-menu-wrap { position: relative; }
.actions-menu { position: absolute; top: 39px; right: 0; z-index: 10; width: 142px; padding: 5px; border: 1px solid var(--border); border-radius: 7px; background: white; box-shadow: 0 12px 30px rgba(22, 30, 26, .14); }
.actions-menu button { width: 100%; height: 34px; display: flex; align-items: center; justify-content: flex-start; border: 0; padding: 0 10px; }
.actions-menu .danger-action { color: #b42318; }
.empty-state { text-align: center; color: #737a81; }
.editor-grid { margin-top: 18px; display: grid; grid-template-columns: minmax(0, 1.65fr) minmax(325px, 1fr); gap: 20px; align-items: stretch; }
.panel { border: 1px solid var(--border); border-radius: 7px; background: white; padding: 17px; }
.form-title { display: flex; align-items: center; justify-content: space-between; }
.form-title button { border: 0; color: var(--green); background: transparent; font: inherit; font-weight: 600; cursor: pointer; }
.form-fields { margin-top: 17px; display: grid; gap: 10px; }
.field { display: grid; grid-template-columns: 165px minmax(0, 1fr); align-items: start; gap: 8px; }
.field > span:first-child { padding-top: 8px; color: #202429; font-size: 13px; }
.field input:not([type='checkbox']), .field textarea { width: 100%; border: 1px solid var(--border); border-radius: 5px; background: #fff; padding: 7px 10px; color: #171b1f; font: inherit; font-size: 12px; line-height: 18px; outline: none; box-shadow: none; }
.field input:not([type='checkbox']) { height: 34px; }
.field input::placeholder, .field textarea::placeholder { color: #959ba2; opacity: 1; }
.field input:not([type='checkbox']):focus, .field textarea:focus { border-color: var(--green); box-shadow: 0 0 0 2px rgba(0,97,66,.09); }
.field textarea { min-height: 75px; resize: vertical; }
.field p { margin: 5px 0 0; color: #7a8188; font-size: 11px; }
.metadata-help { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.metadata-help p { flex: 1; }
.metadata-button { flex: none; margin-top: 6px; border: 0; padding: 0; color: var(--green); background: transparent; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; }
.metadata-button:hover { text-decoration: underline; }
.field small { display: block; margin-top: 3px; color: #c92828; font-size: 11px; }
.checkbox-row { display: flex; align-items: center; gap: 10px; padding-top: 5px; color: #747b82; font-size: 12px; }
.checkbox-row input { width: 16px; height: 16px; border-radius: 3px; color: var(--green); }
.form-actions { margin-top: 20px; display: flex; gap: 14px; }
.save-button, .secondary-button { min-width: 178px; height: 37px; }
.secondary-button { border: 1px solid var(--border); color: #24292e; background: white; }
.secondary-button:hover { background: #f6f7f8; }
.endpoints-panel h2 { margin-bottom: 18px; }
.endpoint-item { padding: 0 0 22px; margin-bottom: 22px; border-bottom: 1px solid var(--border); }
.endpoint-item:last-child { margin: 0; padding-bottom: 0; border: 0; }
.endpoint-item label { display: block; margin-bottom: 8px; font-size: 13px; font-weight: 600; }
.endpoint-input { height: 37px; display: flex; border: 1px solid var(--border); border-radius: 5px; overflow: hidden; }
.endpoint-input span { min-width: 0; flex: 1; overflow: hidden; padding: 9px 10px; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; }
.endpoint-input button { width: 39px; display: grid; place-items: center; flex: none; border: 0; border-left: 1px solid var(--border); background: white; color: #626971; cursor: pointer; }
.endpoint-input button:hover { background: #f5f7f8; color: var(--green); }
.endpoint-input svg { width: 16px; }
.endpoint-item p { margin: 8px 0 0; color: #777e85; font-size: 11px; line-height: 1.5; }

@media (max-width: 1100px) {
    .status-cell { padding: 0 16px; }
    .editor-grid { grid-template-columns: 1fr; }
}
@media (max-width: 820px) {
    .status-panel { grid-template-columns: 1fr 1fr; }
    .status-cell { min-height: 58px; border-top: 1px solid var(--border); }
    .status-cell:nth-child(1), .status-cell:nth-child(2) { border-top: 0; }
    .status-cell:nth-child(3) { border-left: 0; }
}
@media (max-width: 600px) {
    h1 { font-size: 27px; }
    .status-panel { grid-template-columns: 1fr; }
    .status-cell, .status-cell:nth-child(2) { min-height: 64px; border-top: 1px solid var(--border); border-left: 0; }
    .status-cell:first-child { border-top: 0; }
    .section-heading { padding-left: 0; padding-right: 0; }
    .primary-button { padding: 0 14px; }
    .panel { padding: 15px; }
    .field { grid-template-columns: 1fr; gap: 3px; }
    .field > span:first-child { padding-top: 0; }
    .form-actions { flex-direction: column; }
    .save-button, .secondary-button { width: 100%; }
}
</style>