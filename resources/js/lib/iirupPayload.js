/**
 * The IIRUP print payload, built from inventory rows. Shared by the disposal
 * module and the dispose action in the item list so both print the same thing.
 */
export function buildIirupPayload(items) {
    const rows = items ?? [];
    const first = rows[0] ?? {};
    const person = first.latest_acknowledgement_item?.accountable_person ?? null;

    const officerName = person
        ? [
              person.first_name,
              person.middle_name ? person.middle_name.charAt(0) + "." : null,
              person.last_name,
          ]
              .filter(Boolean)
              .join(" ")
        : "";

    return {
        fund_cluster: first.fund_source ?? "",
        as_at: new Date().toLocaleDateString("en-US", {
            month: "long",
            day: "2-digit",
            year: "numeric",
        }),
        accountable_officer: officerName,
        designation: person?.primary_unit_division_department ?? "",
        station: first.room_name ?? "",
        items: rows.map((item) => ({
            date_acquired: item.date_acquired ?? "",
            particulars: item.item_name ?? "",
            property_no: item.property_number ?? "",
            serial_number: item.serial_number ?? "",
            qty: item.quantity ?? 1,
            unit_measure: item.unit ?? "",
            unit_cost: item.unit_cost ?? 0,
            impairment_loss: item.impairment_loss ?? 0,
            remarks: item.remarks ?? "",
        })),
    };
}
