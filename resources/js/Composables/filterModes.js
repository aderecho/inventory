export const filterModes = {
    inventory: {
        url: "/inventory/items",
        fields: ["search", "cost_range", "status", "acknowledgement_status", "room_id"],
        requestOptions: { only: ["items"] },
        placeholder: "Search Item, Property Number, Serial Number...",
    },

    inspection: {
        url: "/inspection",
        fields: ["search", "cost_range", "status", "acknowledgement_status", "room_id"],
        placeholder: "Search item...",
    },

    disposal: {
        url: "/disposal",
        fields: ["search", "cost_range", "status", "acknowledgement_status"],
        placeholder: "Search Item...",
    },

    acknowledgements: {
        url: "/acknowledgements",
        fields: ["search", "cost_range", "status"],
        placeholder: "Search receipt...",
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
        fields: ["search", "acknowledgement_status","room_id"],
        placeholder: "Search item history...",
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