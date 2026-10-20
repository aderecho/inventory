/**
 * Permission names are <action> <module>, so the module is whatever follows the
 * action. Anything else used to fall into bogus groups ("delete archive_item",
 * "items") because the name was split on spaces.
 */

const ACTIONS = [
    "force delete",
    "view",
    "show",
    "create",
    "edit",
    "delete",
    "restore",
    "upload",
    "print",
    "export",
    "import",
    "borrow",
    "return",
    "assign",
];

const ACTION_ORDER = [
    "view",
    "show",
    "create",
    "edit",
    "delete",
    "restore",
    "force delete",
    "upload",
    "print",
    "export",
    "import",
    "borrow",
    "return",
    "assign",
];

export const MODULE_LABELS = {
    dashboard: "Dashboard",
    inventory: "Inventory",
    "item approvals": "Item Approval",
    "item histories": "Item Location History",
    "property cards": "Property Cards",
    inspections: "Asset Inspection",
    disposal: "Disposal",
    borrowings: "Borrowings",
    suppliers: "Suppliers",
    categories: "Item Categories",
    acknowledgements: "Acknowledgements",
    users: "Users",
    archive_users: "User Archive",
    roles: "Roles",
    permissions: "Permissions",
    api_clients: "API Clients",
    saml_configurations: "SAML Configuration",
    audit_logs: "Audit Logs",
    archive_item: "Item Archive",
    archive_supplier: "Supplier Archive",
};

const MODULE_ORDER = [
    "dashboard",
    "inventory",
    "item approvals",
    "item histories",
    "property cards",
    "inspections",
    "disposal",
    "borrowings",
    "suppliers",
    "categories",
    "acknowledgements",
    "users",
    "archive_users",
    "roles",
    "permissions",
    "api_clients",
    "saml_configurations",
    "audit_logs",
    "archive_item",
    "archive_supplier",
];

export function permissionAction(name) {
    const lower = String(name ?? "").toLowerCase().trim();

    return ACTIONS.find((action) => lower.startsWith(action + " ")) ?? "";
}

export function permissionModule(name) {
    const lower = String(name ?? "").toLowerCase().trim();
    const action = permissionAction(lower);

    if (!action) {
        return "other";
    }

    const resource = lower.slice(action.length).trim();

    // "borrow items" / "return items" belong with the Borrowings module.
    if ((action === "borrow" || action === "return") && resource === "items") {
        return "borrowings";
    }

    return resource || "other";
}

export function moduleLabel(key) {
    if (MODULE_LABELS[key]) {
        return MODULE_LABELS[key];
    }

    return String(key ?? "other")
        .replace(/_/g, " ")
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

export function actionLabel(name) {
    const action = permissionAction(name);

    return action
        ? action.replace(/\b\w/g, (character) => character.toUpperCase())
        : "Custom";
}

function actionRank(name) {
    const action = permissionAction(name);
    const index = ACTION_ORDER.indexOf(action);

    return index === -1 ? ACTION_ORDER.length : index;
}

/** Groups permissions by module, in a stable, human order. */
export function groupPermissions(permissions = []) {
    const groups = new Map();

    for (const permission of permissions) {
        const key = permissionModule(permission.name);

        if (!groups.has(key)) {
            groups.set(key, []);
        }

        groups.get(key).push(permission);
    }

    const rank = (key) => {
        const index = MODULE_ORDER.indexOf(key);

        return index === -1 ? MODULE_ORDER.length : index;
    };

    return [...groups.entries()]
        .map(([key, list]) => ({
            key,
            label: moduleLabel(key),
            permissions: [...list].sort(
                (a, b) =>
                    actionRank(a.name) - actionRank(b.name) ||
                    a.name.localeCompare(b.name),
            ),
        }))
        .sort(
            (a, b) =>
                rank(a.key) - rank(b.key) || a.label.localeCompare(b.label),
        );
}

/** Applies a drag order saved in the browser; unknown groups keep their place. */
export function applyGroupOrder(groups, order = []) {
    if (!Array.isArray(order) || !order.length) {
        return groups;
    }

    const position = new Map(order.map((key, index) => [key, index]));

    return [...groups].sort((a, b) => {
        const aPos = position.has(a.key) ? position.get(a.key) : order.length;
        const bPos = position.has(b.key) ? position.get(b.key) : order.length;

        return aPos - bPos;
    });
}

export function loadGroupOrder(storageKey) {
    try {
        const raw = window.localStorage.getItem(storageKey);

        return raw ? JSON.parse(raw) : [];
    } catch (error) {
        return [];
    }
}

export function saveGroupOrder(storageKey, order) {
    try {
        window.localStorage.setItem(storageKey, JSON.stringify(order));
    } catch (error) {
        // A blocked storage quota should never break the page.
    }
}

