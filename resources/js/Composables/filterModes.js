export const filterModes = {
    inventory: {
        url: "/inventory/items",
        fields: [
            "search",
            "cost_range",
            "acknowledgement_status",
            "asset_condition_id",
            "room_id",
        ],
        requestOptions: { only: ["items"] },
        placeholder: "Search Item, Property Number, Serial Number...",
    },

    inspection: {
        url: "/inspection",
        fields: [
            "search",
            "cost_range",
            "acknowledgement_status",
            "asset_condition_id",
            "room_id",
        ],
        placeholder: "Search item...",
    },

    disposal: {
        url: "/disposal",
        fields: [
            "search",
            "cost_range",
            "acknowledgement_status",
            "asset_condition_id",
        ],
        placeholder: "Search Item...",
    },

    // Item approvals. acknowledgement_status carries the approval status.
    "item-approvals": {
        url: "/item-approvals",
        fields: ["search", "acknowledgement_status"],
        requestOptions: { only: ["items"] },
        placeholder: "Search item, property number...",
    },

    transactions: {
        url: "/inventory/transactions",
        fields: ["search", "cost_range", "status"],
        placeholder: "Search",
    },

    reports: {
        url: "/report",
        fields: ["search", "cost_range", "status"],
        placeholder: "Search item",
    },

    suppliers: {
        url: "/suppliers",
        fields: ["search"],
        placeholder: "Search supplier...",
    },

    categories: {
        url: "/categories",
        fields: ["search"],
        placeholder: "Search categories...",
    },

    "item-history": {
        url: "/item-histories",
        // acknowledgement_status carries the accountable-person filter.
        fields: ["search", "acknowledgement_status", "room_id"],
        requestOptions: { only: ["items"] },
        placeholder: "Search item, property number...",
    },

    // One ledger per article: the search box and the room/condition filters.
    "property-cards": {
        url: "/property-cards",
        fields: ["search", "room_id", "asset_condition_id"],
        requestOptions: { only: ["items"] },
        placeholder: "Search item, property number, serial...",
    },

    // Archives. Same filter controls as the module they belong to.
    "item-archive": {
        url: "/inventory/items/archive",
        fields: [
            "search",
            "cost_range",
            "acknowledgement_status",
            "asset_condition_id",
            "room_id",
        ],
        requestOptions: { only: ["items"] },
        placeholder: "Search archived item, property number...",
    },

    "supplier-archive": {
        url: "/suppliers/archive",
        fields: ["search"],
        requestOptions: { only: ["suppliers"] },
        placeholder: "Search archived supplier...",
    },

    "category-archive": {
        url: "/categories/archive",
        fields: ["search"],
        requestOptions: { only: ["categories"] },
        placeholder: "Search archived classification...",
    },

    "user-archive": {
        url: () => route("user_management.archive.index"),
        fields: ["search"],
        requestOptions: { only: ["users"] },
        placeholder: "Search archived user...",
    },

    borrowings: {
        url: "/borrowings",
        // acknowledgement_status carries the borrow status filter.
        fields: ["search", "acknowledgement_status"],
        requestOptions: { only: ["borrowings"] },
        placeholder: "Search borrower, reference code...",
    },

    "borrow-items": {
        url: "/borrowings/borrow-items",
        fields: [
            "search",
            "cost_range",
            "acknowledgement_status",
            "asset_condition_id",
            "room_id",
        ],
        requestOptions: { only: ["items"] },
        placeholder: "Search an available item, property number...",
    },

    "borrowings-history": {
        url: "/borrowings/history",
        fields: ["search", "acknowledgement_status"],
        requestOptions: { only: ["lines"] },
        placeholder: "Search reference, borrower or property number...",
    },

    "accountable-person": {
        url: "/accountable-person",
        fields: ["search"],
        debounceMs: 300,
        placeholder: "Search item",
    },

    users: {
        url: () => route("user_management.index"),
        fields: ["search", "status"],
        transformParams: (params) => ({
            ...params,
            status: params.status !== "" ? params.status : undefined,
        }),
        placeholder: "Search user...",
    },
};

export const DEFAULT_DEBOUNCE_MS = 1000;
export const DEFAULT_PLACEHOLDER = "Search item";
