# CRM User Guide

This guide is for sales and operations teams using the CRM day to day. For technical setup see `installation.md`.
> **Note:** Two-factor authentication, webhooks and the calendar feed are optional features; if you do not see them, they are not enabled for your installation.

## 1. Signing In and Account Security

- Sign in at `https://crm.yourcompany.com/admin` with email + password.
- **Two-factor authentication (recommended):** open **System → Security**, click *Enable two-factor*, scan the QR code with Google Authenticator/1Password and enter the 6-digit code. Save the **recovery codes** somewhere safe — they are shown only once.
- After 5 failed login attempts you must wait one minute.

## 2. Dashboard

The landing screen shows, for the selected period: open pipeline, deals won/lost, overdue tasks, quote status distribution, upcoming tasks and recent activities. Manager roles see the whole team; sales roles see their own records.

## 3. Contacts and Companies

- **Create:** the *New* button on each list. Contacts can belong to a company and carry a lifecycle stage (lead → customer).
- **Filter and search:** compact filter bar + *Advanced* panel. Save frequent filters with **Saved Filters**, optionally shared with the team.
- **Import:** *Import* → upload CSV/XLSX → review the preview mapping → confirm. Results arrive as a notification; a failed-rows report can be downloaded.
- **Export:** the *Export* button produces CSV with your chosen columns.
- **Tags:** colour-coded tags on records; filter lists by tag and bulk-tag via the selection bar.

## 4. Deals

- **Kanban:** drag cards between stages; stage totals update instantly. Keyboard users can open a deal and change its stage from the edit form.
- **Won / Lost:** *Close Won / Close Lost* on the deal page (lost asks for a reason). The deal owner gets a notification (and email, per preference).
- Add tasks, activities and quotes directly from the deal page; everything shows on the timeline.

## 5. Tasks

- Give tasks a priority, due date and reminder; reminders arrive as notification + email.
- **Calendar subscription:** create your private ICS link under **System → Security → Calendar feed** and add it to Google Calendar/Outlook via "subscribe by URL". Regenerating the link invalidates the old one.

## 6. Quotes

- Line items, VAT and discounts are calculated server-side; PDF preview and download are always available.
- **Sending:** *Send* marks the quote as sent and emails the customer a PDF with an **approval link**.
- **Customer approval:** the customer opens the link, then *Accepts* or *Declines* (with a reason). You are notified and the outcome is logged on the quote.
- **Status rules:** accepted/rejected quotes are locked; use *Duplicate* to create an editable draft copy. Expired quotes can be re-sent.

## 7. Notifications and Email Preferences

- The bell icon shows recent notifications; the **Notifications** page lists them all.
- Under **Email preferences** on the same page, choose which events also reach you by email. In-app notifications stay on.

## 8. Search

The global search bar looks across contacts, companies, deals, tasks and quotes at once.

## 9. Administration (System)

*(requires the settings-manage permission)*

- **Settings:** company profile, logo, currency/VAT, quote prefix, notification switches, AI configuration.
- **Users:** user and role management (owner/manager/sales/support/viewer).
- **API Tokens:** issue bearer tokens for integrations; shown once, revocable any time.
- **Webhooks:** push CRM events to external systems (including Zapier/Make); delivery history on the same screen.
- **Audit Log:** who changed what and when, with field-level old→new values.
- **Trash:** restore or permanently delete removed contacts/companies/deals/quotes.

## 10. FAQ

**Not receiving emails?** Check your own preferences first (Notifications → Email preferences), then ask an admin to verify the global email switch and SMTP settings.

**Deleted a record by mistake?** An admin can restore it from **System → Trash**.

**Need to change an accepted quote?** Accepted quotes are locked; *Duplicate* it, edit the draft and send again.
