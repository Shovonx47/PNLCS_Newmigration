<?php

/**
 * The twice-daily-lifecycle templates the legacy bdhost_wa addon shipped,
 * copied verbatim. Names match the hook points that fire them.
 */

namespace Modules\Addons\WhatsappPro\Support;

final class DefaultTemplates
{
    public static function all(): array
    {
        return [
            [
                'name' => 'InvoicePaymentReminder',
                'type' => 'client',
                'template' => 'â° *Friendly Payment Reminder*

Hi {fullname},

This is a gentle reminder that your invoice is due soon.

ðŸ“„ Invoice #: {invoicenum}
ðŸ’° Amount Due: {amountdue}
ðŸ“… Due Date: {duedate}
ðŸ’³ Payment Method: {paymentmethod}

ðŸ”— Pay Now: {invoiceurl}

Thank you for choosing *{companyname}*! ðŸ’š',
                'variables' => '{firstname},{lastname},{fullname},{email},{phonenumber},{companyname},{clientcompany},{invoicenum},{invoiceid},{amountdue},{total},{duedate},{paymentmethod},{invoiceurl}',
                'description' => 'Invoice due reminder',
            ],
            [
                'name' => 'TicketUserReplyAdmin',
                'type' => 'admin',
                'template' => 'ðŸ’¬ *Client Replied to Ticket*

{fullname} has replied to their support ticket.

ðŸ“§ Email: {email}
ðŸ“ž Phone: {phonenumber}
ðŸ¢ Company: {company}

ðŸŽ« Ticket #: {ticketid}
ðŸ“ Subject: {subject}
ðŸ“‹ Status: {status}

ðŸ’¬ Reply:
{reply}

Previous Reply: {lastreply}

Please check the admin panel to respond.',
                'variables' => '{clientname},{firstname},{lastname},{fullname},{email},{phonenumber},{company},{companyname},{country},{ticketid},{subject},{status},{reply},{lastreply}',
                'description' => 'Admin alert for ticket reply',
            ],
            [
                'name' => 'TicketAdminReply',
                'type' => 'client',
                'template' => 'ðŸ’¬ *New Reply on Your Support Ticket*

Hi {fullname},

Our support team has replied to your ticket:

ðŸŽ« Ticket #: {ticketid}
ðŸ“ Subject: {subject}
ðŸ“‹ Status: {status}

ðŸ’¬ Reply:
{reply}

Please log in to your client area to view the full conversation and respond.

- *{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{email},{phonenumber},{companyname},{ticketid},{subject},{status},{reply}',
                'description' => 'Ticket admin reply',
            ],
            [
                'name' => 'ClientAdd',
                'type' => 'client',
                'template' => 'ðŸŽ‰ *Welcome to {companyname}!*

Hi {fullname},

Thank you for creating an account with us. We\'re excited to have you on board! ðŸ’š

ðŸ“§ Email: {email}
ðŸ†” Client ID: {userid}

If you ever need help, just reply to this message or open a support ticket - our team is always here for you.

Warm regards,
*{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{email},{userid},{companyname},{password}',
                'description' => 'New client welcome',
            ],
            [
                'name' => 'AfterModuleChangePassword',
                'type' => 'client',
                'template' => 'ðŸ”‘ *Service Password Updated*

Hi {fullname},

The login password for your service has been changed successfully.

ðŸŒ Domain: {domain}
ðŸ“¦ Package: {productname}
ðŸ‘¤ Username: {username}
ðŸ”‘ New Password: {password}
ðŸ–¥ï¸ Server: {serverhostname}

ðŸ”’ Please keep this information secure and don\'t share it with anyone.

- *{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{userid},{domain},{username},{password},{productname},{serverhostname}',
                'description' => 'Service password change',
            ],
            [
                'name' => 'TicketClose',
                'type' => 'client',
                'template' => 'âœ… *Support Ticket Closed*

Hi {fullname},

Your ticket has been marked as resolved and closed. ðŸŽ‰

ðŸŽ« Ticket #: {ticketid}
ðŸ“ Subject: {subject}
ðŸ“… Closed On: {date}
ðŸ’¬ Last Reply: {lastreply}

If you need further help, feel free to open a new ticket anytime - we\'re always here for you.

- *{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{email},{companyname},{ticketid},{subject},{date},{lastreply}',
                'description' => 'Ticket closed notice',
            ],
            [
                'name' => 'DomainRenewalNotice',
                'type' => 'client',
                'template' => 'ðŸŒ *Domain Renewal Reminder*

Hi {fullname},

Your domain is expiring soon:

ðŸŒ Domain: {domain}
ðŸ“… Expiry Date: {expirydate}
â³ Days Remaining: {daysbefore}

Please renew soon to avoid losing your domain and any associated services. Need help? Contact *{companyname}* support anytime. ðŸ’¬',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{domain},{expirydate},{daysbefore}',
                'description' => 'Domain renewal notice',
            ],
            [
                'name' => 'AfterRegistrarRegistrationAdmin',
                'type' => 'admin',
                'template' => 'ðŸŒ *New Domain Registered*

A domain has been successfully registered.

ðŸŒ Domain: {sld}.{tld}
ðŸ“… Period: {regperiod} year(s)

- *{companyname}* System Notice',
                'variables' => '{domain},{sld},{tld},{regperiod}',
                'description' => 'Domain registration admin notice',
            ],
            [
                'name' => 'AcceptOrder',
                'type' => 'client',
                'template' => 'ðŸŽŠ *Order Confirmed - Thank You!*

Hi {fullname},

Thank you for your order with *{companyname}*! Here\'s a summary:

ðŸ§¾ Order #: {ordernum}
ðŸ“… Order Date: {orderdate}
ðŸ’° Total: {invoice_total}
ðŸ’³ Payment Method: {paymentmethod}
ðŸ“„ Invoice #: {invoicenum}

ðŸ”— View Invoice: {invoiceurl}

We\'re processing your order now and will notify you once it\'s ready. Welcome aboard! ðŸ’š
*{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{clientcompany},{ordernum},{orderdate},{invoice_total},{paymentmethod},{invoicenum},{invoiceurl}',
                'description' => 'Order acceptance',
            ],
            [
                'name' => 'InvoiceCreated',
                'type' => 'client',
                'template' => 'ðŸ§¾ *New Invoice Generated*

Hi {fullname},

A new invoice has been created for your account.

ðŸ“„ Invoice #: {invoicenum}
ðŸ’° Subtotal: {subtotal}
âž• Tax ({taxrate}%): {tax}
ðŸ’µ Total Due: {total}
ðŸ“… Due Date: {duedate}
ðŸ’³ Payment Method: {paymentmethod}

ðŸ”— View & Pay: {invoiceurl}

ðŸ“Ž The PDF invoice is attached to this message for your records.

Thank you for choosing *{companyname}*! ðŸ’š',
                'variables' => '{firstname},{lastname},{fullname},{email},{phonenumber},{companyname},{clientcompany},{invoicenum},{invoiceid},{subtotal},{taxrate},{tax},{total},{duedate},{paymentmethod},{invoiceurl}',
                'description' => 'Invoice creation notice',
            ],
            [
                'name' => 'InvoicePaid',
                'type' => 'client',
                'template' => 'âœ… *Payment Received - Thank You!* ðŸŽ‰

Hi {fullname},

We\'ve successfully received your payment. Here\'s your receipt summary:

ðŸ“„ Invoice #: {invoicenum}
ðŸ’° Amount Paid: {amountpaid}
ðŸ’³ Payment Method: {paymentmethod}
ðŸ“… Paid On: {paiddate}

ðŸ“Ž Your PDF receipt is attached to this message.

Thank you for your prompt payment and for being a valued customer of *{companyname}*! ðŸ’š',
                'variables' => '{firstname},{lastname},{fullname},{email},{companyname},{clientcompany},{invoicenum},{amountpaid},{paymentmethod},{paiddate}',
                'description' => 'Payment confirmation',
            ],
            [
                'name' => 'AfterModuleCreate',
                'type' => 'client',
                'template' => 'âœ… *Your Service is Now Active!* ðŸŽŠ

Hi {fullname},

Great news - your new service with *{companyname}* has been successfully activated and is ready to use!

ðŸŒ Domain: {domain}
ðŸ“¦ Package: {productname}
ðŸ‘¤ Username: {username}
ðŸ”‘ Password: {password}
ðŸ–¥ï¸ Server: {serverhostname}
ðŸŒ Server IP: {serverip}

ðŸ”’ Please keep these login details safe and secure.

Welcome aboard, and thank you for choosing us! ðŸ’š
*{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{domain},{productname},{username},{password},{serverhostname},{serverip}',
                'description' => 'Service activation',
            ],
            [
                'name' => 'InvoicePaymentReminderFirst',
                'type' => 'client',
                'template' => 'â° *Reminder: Invoice Overdue*

Hi {fullname},

Your invoice below was due on {duedate} and is now overdue:

ðŸ“„ Invoice #: {invoicenum}
ðŸ’° Amount Due: {amountdue}
ðŸ’µ Total: {total}

ðŸ“‹ Invoice Details:
{invoiceitems}

ðŸ”— Please pay at your earliest convenience: {invoiceurl}

ðŸ“Ž The invoice PDF is attached for your reference.

- *{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{clientcompany},{invoicenum},{amountdue},{total},{duedate},{invoiceitems},{invoiceurl}',
                'description' => '1st invoice reminder',
            ],
            [
                'name' => 'AfterModuleSuspend',
                'type' => 'client',
                'template' => 'âš ï¸ *Service Suspended*

Hi {fullname},

Your service has been temporarily suspended. Details below:

ðŸŒ Domain: {domain}
ðŸ“¦ Package: {productname}
ðŸ’° Amount Due: {amount}
ðŸ“… Next Due Date: {nextduedate}
ðŸ“‹ Reason: {suspendreason}

This is usually due to an unpaid invoice. Please settle any outstanding balance to restore your service as soon as possible.

Need help? Contact *{companyname}* support anytime. ðŸ’¬',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{domain},{productname},{amount},{nextduedate},{suspendreason}',
                'description' => 'Service suspension',
            ],
            [
                'name' => 'AdminLogin',
                'type' => 'admin',
                'template' => 'ðŸ” *Admin Login Alert*

An admin login was detected on *{companyname}*.

ðŸ‘¤ Admin: {username}
ðŸ†” Admin ID: {adminid}
ðŸ“… Date: {logindate}
â° Time: {logintime}
ðŸŒ IP Address: {ipaddress}
ðŸ“ Location: {location}
ðŸ’» Browser: {browser}
ðŸ“± Platform: {platform}

âš ï¸ If this wasn\'t expected, please review admin access immediately.',
                'variables' => '{username},{adminid},{logindate},{logintime},{ipaddress},{location},{browser},{platform}',
                'description' => 'Admin login alert',
            ],
            [
                'name' => 'AfterModuleUnsuspend',
                'type' => 'client',
                'template' => 'âœ… *Service Restored!* ðŸŽ‰

Great news, {fullname}! Your service has been reactivated and is fully operational again.

ðŸŒ Domain: {domain}
ðŸ“¦ Package: {productname}
ðŸ“… Next Due Date: {nextduedate}

Thank you for your prompt action - we\'re glad to have you back! ðŸ’š
*{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{domain},{productname},{nextduedate}',
                'description' => 'Service unsuspension',
            ],
            [
                'name' => 'AfterRegistrarRegistration',
                'type' => 'client',
                'template' => 'ðŸŽ‰ *Domain Registered Successfully!*

Hi {fullname},

Great news - your domain has been registered successfully!

ðŸŒ Domain: {sld}.{tld}
ðŸ“… Registration Period: {regperiod} year(s)

Thank you for choosing *{companyname}*! ðŸ’š',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{sld},{tld},{regperiod}',
                'description' => 'Domain registration success',
            ],
            [
                'name' => 'TicketOpenAdmin',
                'type' => 'admin',
                'template' => '? *New Support Ticket Opened*

A new ticket has been submitted.

? Client: {fullname}
? Email: {email}
? Phone: {phonenumber}
? Company: {company}
? Country: {country}

? Ticket #: {ticketid}
? Subject: {subject}
?ï¸ Department: {department}
? Priority: {priority}
? Status: {status}
? Date: {date}

? Message:
{message}

? View Ticket: {ticketurl}

Please review and respond promptly.',
                'variables' => '{firstname},{lastname},{fullname},{email},{phonenumber},{company},{companyname},{country},{ticketid},{subject},{status},{date},{message},{department},{priority},{ticketurl}',
                'description' => 'Admin new ticket notice',
            ],
            [
                'name' => 'InvoicePaymentReminderSecond',
                'type' => 'client',
                'template' => 'ðŸ”” *Second Reminder: Payment Still Due*

Hi {fullname},

We still haven\'t received your payment for the invoice below, due since {duedate}:

ðŸ“„ Invoice #: {invoicenum}
ðŸ’° Amount Due: {amountdue}
ðŸ’µ Total: {total}

ðŸ“‹ Invoice Details:
{invoiceitems}

ðŸ”— Please settle this soon to avoid any service interruption: {invoiceurl}

ðŸ“Ž Invoice PDF attached.

- *{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{clientcompany},{invoicenum},{amountdue},{total},{duedate},{invoiceitems},{invoiceurl}',
                'description' => '2nd invoice reminder',
            ],
            [
                'name' => 'ClientChangePassword',
                'type' => 'client',
                'template' => 'ðŸ”‘ *Password Changed Successfully*

Hi {fullname},

This is a confirmation that the password for your *{companyname}* account has just been changed.

ðŸ“§ Account Email: {email}
ðŸ•“ Changed At: {datetime}

âš ï¸ If you didn\'t make this change, please contact our support team right away to secure your account.

Stay safe,
*{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{email},{companyname}',
                'description' => 'Client password change notice',
            ],
            [
                'name' => 'AfterRegistrarRenewal',
                'type' => 'client',
                'template' => 'âœ… *Domain Renewed Successfully*

Hi {fullname},

Your domain has been renewed for another term:

ðŸŒ Domain: {sld}.{tld}
ðŸ“… Renewal Period: {regperiod} year(s)

Thank you for staying with *{companyname}*! ðŸ’š',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{sld},{tld},{regperiod}',
                'description' => 'Domain renewal success',
            ],
            [
                'name' => 'AfterRegistrarRegistrationFailedAdmin',
                'type' => 'admin',
                'template' => 'ðŸš¨ *Domain Registration Failed*

Registration FAILED for a client\'s domain.

ðŸŒ Domain: {sld}.{tld}
âš ï¸ Reason: {reason}

Please investigate and assist the client as needed.',
                'variables' => '{domain},{sld},{tld},{reason}',
                'description' => 'Domain reg failure admin',
            ],
            [
                'name' => 'AfterRegistrarRenewalFailedAdmin',
                'type' => 'admin',
                'template' => 'ðŸš¨ *Domain Renewal Failed*

Renewal FAILED for a client\'s domain.

ðŸŒ Domain: {sld}.{tld}
âš ï¸ Reason: {reason}

Please check the registrar account and contact the client if needed.',
                'variables' => '{domain},{sld},{tld},{reason}',
                'description' => 'Domain renewal failure admin',
            ],
            [
                'name' => 'InvoiceCancelled',
                'type' => 'client',
                'template' => 'ðŸš« *Invoice Cancelled*

Hi {fullname},

The following invoice has been cancelled:

ðŸ“„ Invoice #: {invoicenum}
ðŸ’° Amount: {total}
ðŸ“… Date: {date}

If you believe this was a mistake, please contact *{companyname}* support and we\'ll sort it out right away. ðŸ’¬',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{clientcompany},{invoicenum},{total},{date}',
                'description' => 'Invoice cancellation',
            ],
            [
                'name' => 'AfterModuleChangePackage',
                'type' => 'client',
                'template' => 'ðŸ”„ *Package Upgraded Successfully*

Hi {fullname},

Your service has been changed to a new package:

ðŸŒ Domain: {domain}
ðŸ“¦ New Package: {productname}
ðŸ’° Amount: {amount}
ðŸ“… Next Due Date: {nextduedate}
ðŸ’³ Billing Cycle: {billingcycle}

Thank you for growing with *{companyname}*! ðŸš€',
                'variables' => '{firstname},{lastname},{fullname},{domain},{productname},{amount},{nextduedate},{billingcycle}',
                'description' => 'Service package change',
            ],
            [
                'name' => 'InvoicePaymentReminderThird',
                'type' => 'client',
                'template' => 'ðŸš¨ *Final Notice: Invoice Overdue*

Hi {fullname},

This is a final reminder regarding your overdue invoice:

ðŸ“„ Invoice #: {invoicenum}
ðŸ’° Amount Due: {amountdue}
ðŸ’µ Total: {total}
ðŸ“… Was Due: {duedate}

ðŸ“‹ Invoice Details:
{invoiceitems}

âš ï¸ Please pay immediately to avoid service suspension: {invoiceurl}

ðŸ“Ž Invoice PDF attached.

- *{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{clientcompany},{invoicenum},{amountdue},{total},{duedate},{invoiceitems},{invoiceurl}',
                'description' => 'Final invoice reminder',
            ],
            [
                'name' => 'AfterModuleTerminate',
                'type' => 'client',
                'template' => 'âŒ *Service Terminated*

Hi {fullname},

Your service below has been terminated:

ðŸŒ Domain: {domain}
ðŸ“¦ Package: {productname}

If this was unexpected or you\'d like to discuss reactivation, please reach out to *{companyname}* support - we\'re happy to help. ðŸ’¬',
                'variables' => '{firstname},{lastname},{fullname},{domain},{productname}',
                'description' => 'Service termination.',
            ],
            [
                'name' => 'InvoiceReminderManual',
                'type' => 'client',
                'template' => 'Hi {firstname}, this is a friendly reminder that your invoice {invoicenum} for {invoicetotal} is still unpaid (due {invoiceduedate}). Please pay at your earliest convenience: {invoiceurl}',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{invoicenum},{invoiceid},{invoicetotal},{invoiceduedate},{invoicestatus},{invoiceurl},{invoiceitems}',
                'description' => 'Manual invoice reminder (sent from the Invoices tab)',
            ],
            [
                'name' => 'AfterRegistrarRegistrationFailed',
                'type' => 'client',
                'template' => 'âŒ *Domain Registration Failed*

Hi {fullname},

Unfortunately, we were unable to register your domain:

ðŸŒ Domain: {sld}.{tld}

Our team has been notified and will reach out to assist you shortly. We apologize for the inconvenience.

- *{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{sld},{tld}',
                'description' => 'Domain registration failed (client notice)',
            ],
            [
                'name' => 'AfterRegistrarRenewalAdmin',
                'type' => 'admin',
                'template' => '? *Domain Renewed*

A domain renewal was processed.

? Domain: {sld}.{tld}

- *{companyname}* System Notice',
                'variables' => '{sld},{tld},{reason}',
                'description' => 'Domain renewal admin notice',
            ],
            [
                'name' => 'CancellationRequestAdmin',
                'type' => 'admin',
                'template' => 'ðŸš« *New Cancellation Request*

A client has requested a cancellation.

ðŸ‘¤ Client: {clientname}
ðŸ“¦ Product/Service: {productname}
ðŸŒ Domain: {domain}
ðŸ“‹ Type: {type}
ðŸ“ Reason: {reason}

Please review this request in the admin panel.',
                'variables' => '{clientname},{productname},{reason},{type},{domain}',
                'description' => 'Cancellation request admin alert',
            ],
            [
                'name' => 'ClientAddAdmin',
                'type' => 'admin',
                'template' => 'ðŸ‘¤ *New Client Registered*

A new client has signed up on *{companyname}*.

ðŸ“› Name: {firstname} {lastname}
ðŸ“§ Email: {email}

Welcome them and review their account when you get a chance. ðŸ’š',
                'variables' => '{firstname},{lastname},{email},{companyname}',
                'description' => 'New client admin notice',
            ],
            [
                'name' => 'TicketOpen',
                'type' => 'client',
                'template' => 'ðŸŽ« *We\'ve Received Your Ticket!*

Hi {fullname},

Thank you for reaching out. Your support ticket has been received and our team will get back to you shortly.

ðŸŽ« Ticket #: {ticketid}
ðŸ“ Subject: {subject}
ðŸ—‚ï¸ Department: {department}
ðŸ”¥ Priority: {priority}
ðŸ“‹ Status: {status}
ðŸ“… Opened: {date}

ðŸ’¬ Your Message:
{message}

ðŸ”— Track your ticket: {ticketurl}

We appreciate your patience - *{companyname}* support is on it! ðŸ’š',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{ticketid},{subject},{message},{status},{date},{department},{priority},{ticketurl}',
                'description' => 'Client ticket opened confirmation',
            ],
            [
                'name' => 'InvoiceRefunded',
                'type' => 'client',
                'template' => 'ðŸ’¸ *Refund Processed*

Hi {fullname},

We\'ve processed a refund for your invoice.

ðŸ“„ Invoice #: {invoicenum}
ðŸ’° Refund Amount: {refundamount}
ðŸ’³ Method: {refundmethod}
ðŸ“… Date: {date}

ðŸ”— View Invoice: {invoiceurl}

Please allow a few business days for the refund to reflect depending on your payment method.

Thank you for your patience! ðŸ’š
*{companyname}*',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{invoicenum},{invoiceid},{total},{refundamount},{refundmethod},{invoiceurl},{date}',
                'description' => 'Invoice refund notice',
            ],
            [
                'name' => 'ClientLoginNotify',
                'type' => 'client',
                'template' => '',
                'variables' => '{firstname},{lastname},{fullname},{companyname},{ipaddress},{hostname},{location},{isp},{datetime},{date},{time}',
                'description' => 'Client area login security alert',
            ],
        ];
    }
}
