# Deals Kanban Manual QA

Use this checklist after publishing CRM assets and logging into `/admin`.

1. Open `/admin/crm/deals`.
2. Verify columns follow Deal Stage position order.
3. Drag a deal to another open stage, refresh the page, and verify the deal remains in the new column and position.
4. Drag a deal inside the same column, refresh the page, and verify the card order is preserved.
5. Drag a deal to the won stage and verify the deal status becomes `won`.
6. Drag a deal to the lost stage, submit a lost reason in the dialog, and verify the deal status becomes `lost`.
7. Cancel the lost reason dialog and verify the card returns to its previous column.
8. Apply owner, tag, expected close date, value range and status filters; verify both Kanban and List views show the same filtered records.
9. Reduce the browser width to mobile size and verify the Kanban board scrolls horizontally.
