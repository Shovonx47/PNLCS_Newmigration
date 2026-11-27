# PNLCS User Manual

A quick guide to using every feature in the panel.

> **First step for any addon (Admin):** Go to **Extensions → Manage extensions**, find the addon, and click **Activate**. Once active, it appears under the **Extensions** menu.

---

## Billing, Payments & Store

### Paying an Invoice (Client)
- Open any unpaid invoice (or click the direct payment link sent in your email/WhatsApp/Telegram) → click **Pay** → choose **PipraPay**, **UddoktaPay**, **UddoktaPay International**, or another active method → finish checkout. The invoice marks itself paid automatically.

### Setting Up Payment Gateways & Servers (Admin)
- **Payment gateways:** Go to **Setup → Payment Gateways** → open **PipraPay**, **UddoktaPay**, or **UddoktaPay International** → enter your API credentials and activate → save.
- **Servers:** Go to **Setup → Servers** → add a server using **BDPBX Cloud PBX**, **BHPanel (cPanel)**, **Softcents Bulk SMS**, or **Softcents Voice SMS** → enter host and password or API token → save. Assign the server under **Setup → Products/Services → Edit → Module**.

### Product Free Trials & Compare Prices (Admin)
- Go to **Setup → Products/Services → Edit Product** → set **Free Trial Period** (number of free days) and **Compare / Regular Price** (crossed-out original price shown in the store) → save.

### Client Group Exemptions (Admin)
- Go to **Setup → Client Groups** → create or edit a group → tick **Suspend Exempt** and/or **Terminate Exempt** → save.
- Assign a client to that group from their client profile so automatic suspension or termination skips them.

### Bulk Price Updater (Admin)
- Go to **Extensions → Bulk Price Updater**.
- Pick **Product & Active Service Pricing** or **Domain TLD Pricing**, choose the adjustment mode and value, and optionally tick cascading to existing active services/domains.
- Click **Preview Dry-Run** to review the new prices, then click **Apply Product Update** / **Apply TLD Update**.

### Auto-Cancel Unpaid Invoices (Admin)
- Go to **Extensions → Auto-Cancel Invoices**.
- Under **Cancellation Rules**, set how many days past due unpaid invoices wait before auto-cancelling → click **Save Rules**.
- Add any clients or product groups that should be skipped under **Exclusions**, or click **Run Sweep Now** to run immediately.

### Mass Invoice Export (Admin)
- Go to **Extensions → Invoice PDF Export** → pick date range, status, optional client, and max limit (up to 300) → click **Generate & Download ZIP**.

---

## Messaging, Alerts & Payment Reminders

### SMS Notifications & Registration OTP (Admin)
- Go to **Extensions → Softcents SMS**.
- Under **Gateway & registration OTP**, enter **Client ID**, **Client secret**, **Sender ID**, and **Admin alert numbers**. To require mobile OTP verification on registration/checkout, tick the OTP options and configure length/expiry → click **Save settings**.
- Under **Templates**, tick **active** on any event you want sent, edit the text if needed, and click **Save**.
- Use **Send a test message** anytime to verify delivery.

### WhatsApp Notifications & Bulk Messaging (Admin & Client)
- **Admin setup:** Go to **Extensions → WhatsApp Pro** → under **Gateway**, enter **API key**, **Sender number**, **Gateway domain**, **Footer**, and **Admin numbers** → tick **Sending enabled** (and **Show message history to customers** if desired) → click **Save settings**. Click **Check now** to test the connection.
- **Templates:** Under **Templates**, tick **active** on each event you want sent → click **Save**.
- **Single or bulk send:** Use **Send one message** (**Queue message**), or **Bulk send** to pick an audience (all customers, open invoices, product group, client IDs, or pasted/uploaded CSV) and click **Queue to audience**.
- **Retry failed messages:** Scroll to **Recent messages** and click **Retry**.
- **Client:** Click **Message history** on the **WhatsApp** card on your **Dashboard**.

### Telegram Notifications (Admin & Client)
- **Admin:** Go to **Extensions → Telegram Bot** → enter **Bot token**, **Bot username**, and **Webhook secret** → tick **Bot enabled** and the alert types you want → click **Save settings** → click **Register webhook with Telegram**.
- **Client:** On your **Dashboard**, find the **Telegram** card and click **Connect** (or **Manage**) → click **Connect Telegram** → send the `/start` command with your code to the bot in Telegram. Use **Mute alerts** / **Unmute alerts**, **Send test message**, or **Disconnect** anytime on that page.

### Voice Call Notifications (Admin)
- Go to **Extensions → Softcents Voice**.
- Under **Gateway & calling window**, enter **API token**, **API base URL**, **Default caller id**, and the daily calling window hours/timezone → click **Save settings**.
- Under events, turn on the events you want and enter each recording's **Audio id** → click **Save**.
- To use a different recording for a specific product group, go to **Per-group recordings** → select group, event, audio ID, and caller ID → click **Add override**.

### Payment Reminders (Admin)
- Email payment reminders run automatically across upcoming and overdue invoice stages.
- To also send reminders over SMS, WhatsApp, or Voice: enable those extensions and turn on their payment reminder templates/events (`InvoicePaymentReminder`, `InvoicePaymentReminderFirst`, `InvoicePaymentReminderSecond`, `InvoicePaymentReminderThird` for SMS/WhatsApp; payment reminder events for Voice).

---

## Telecom & VoIP Tools

### BDPBX Revenue Accounting (Admin)
- Go to **Extensions → BDPBX Accounting**.
- View combined and per-server metrics (active MRR, paid this month, paid today, unpaid).
- Pick any date and click **Show day** to view history, or click **Run now** to recalculate that day.

### Checking VoIP Balance (Admin & Client)
- **Admin:** Go to **Extensions → iTalk / ICC Balance** → select which products belong to each program and set refresh cooldown → **Save settings**. Under **Balance endpoints**, add your HTTPS balance URL (**Add endpoint**). Under **Service IP number mappings**, link a service and IP number/PIN to an endpoint (**Save mapping**).
- **Client:** Open **My Services** → click a service → look at the **IP Number Balance** card and click **Refresh Balance** anytime.

### IPTSP / IP Number Balance Recharge (Admin & Client)
- **Client:** On your **Dashboard**, find **IP Number Calling Balance Recharge** (or click **Open recharge page**) → enter the IP number and amount → submit and pay the generated invoice.
- **Admin (automated recharge):** Go to **Extensions → IPTSP Recharge** → add your providers (**Add provider**) and map customer PIN accounts (**Save PIN**). When a top-up invoice is paid, it recharges automatically; you can also send a **Manual top-up** or click **Retry** on failed logs.
- **Admin (manual requests):** Go to **Extensions → Recharge Requests** to **Mark completed** or **Cancel** customer recharge requests.

---

## Client Portal, Support & Admin Tools

### Support PIN (Admin & Client)
- **Client:** On your **Dashboard**, look at **Support Verification PIN** → click **Show PIN** to reveal your 6-digit PIN (or **Regenerate** for a new one) and share it with support staff.
- **Admin:** View a client's current PIN on **Clients → View/Search Clients → open client**, or go to **Extensions → Support PIN Lookup**, enter the 6-digit PIN, and click **Verify PIN**.

### Server IP Unblock (Admin & Client)
- **Client:** On your **Dashboard**, click **Open IP Unblocker** on the **Connection / Firewall IP Unblocker** card → pick the active service, confirm the IP address, and click **Check & Unblock IP**.
- **Admin:** Go to **Extensions → IP Unblock Logs** to unblock an IP directly (**Direct Staff IP Unblock**) or review past requests.

### Service Self-Controls & Ownership Transfer (Admin & Client)
- **Client:** Open **My Services** → select a service → use **Suspend Service** (on active services), **Terminate Permanently** (on suspended services), or expand **Transfer Service Ownership** → enter recipient name, email, and reason → click **Submit Transfer Request**.
- **Admin:** Go to **Extensions → Service Transfers** → review pending requests and click **Approve Transfer** or **Reject**. Live summary strips and dashboard credit/account cards can be toggled in the **Portal Extras** extension settings.

### Scrolling Announcement Banner (Admin & Client)
- **Admin:** Go to **Extensions → Scrolling Notices** → under **Add Notice**, enter your message (plus optional start/end dates, link, or promo code) → click **Create Notice**. Use **Edit**, **Enable**/**Disable**, or **Delete** on existing notices.
- **Client:** Active notices scroll automatically across the top of the portal.

### Force Client Active (Admin)
- Go to **Extensions → Force Client Active**.
- Under **Global Enforcement Mode**, choose whether to restore Inactive accounts or keep all accounts Active globally → click **Save & Run Now**.
- Under **Protected Clients**, enter a **Client ID** and optional note → click **Add Protected Client** so that account stays active.

### Google Drive Automated Backup (Admin)
- Go to **Extensions → Google Drive Backup**.
- Under **OAuth2 & Retention Configuration**, enter your Google **OAuth2 Client ID**, **Client Secret**, exchange an authorization code for a **Refresh Token**, enter the **Drive Folder ID**, and choose what to include → click **Save Configuration**.
- Click **Run Backup Now** anytime to run a backup immediately, and check **Backup History** below.

---

## Identity Verification (KYC)

### Admin Setup & Review
- Go to **Extensions → Client Verification (KYC)** → open **Verification & Policy Settings** → choose the **Verification Mode**, **Checkout Requirement** (disabled, all clients, or **Restricted Product Group IDs**), upload rules, and deadline options → click **Save Settings**.
- Pending submissions appear in the queue below: open a submission to inspect documents and click **Approve** or **Decline** (approving unblocks checkout and automatically unsuspends services held for missing KYC). Click **Export** to download a CSV.

### Client Verification
- Go to **Account → Identity Verification** (or click **Verify Identity** on the **Dashboard** banner / when redirected at checkout) → complete instant verification or upload your documents and click **Submit Documents for Review**.

---

## Reseller Program

### Admin Management
- Go to **Extensions → Resellers Center**:
  - **Pricing Groups:** Create tiers (**Create Tier**) and add per-product wholesale/commission rules (**Add Rule**).
  - **Resellers:** Add a reseller account (**Create Reseller**), or open an existing reseller to edit branding/settings and assign or unlink end clients (**Assign** / **Unlink**).
  - **Payouts:** Review pending commissions and click **Mark Paid** or use **Pay Selected Resellers**.
  - **Utilities & Logs:** View record counts and audit history.

### Reseller Portal (Client)
- Resellers open **Account → Reseller Portal** to:
  - Update **White-Label Branding & Contact** (company name, email, CNAME, logo, color, custom SMTP if enabled) → **Save Branding**.
  - Configure **End-Client Payment Gateways**.
  - Create or delete **Reseller Promo Codes**.
  - **Suspend** or **Unsuspend** end-client services and track **Recent Commission Payouts**.

---

## Importing from WHMCS (Admin)

- Go to **Utilities → Import WHMCS** → enter your old WHMCS database host, name, username, password, and table prefix → click **Test connection** → click **Save and continue** and follow the on-screen import steps.
