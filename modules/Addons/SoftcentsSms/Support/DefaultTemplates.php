<?php

/**
 * The 29 templates the legacy SOFTCENTS_SMS addon installed, copied verbatim so
 * an activation starts with the wording the operator already uses.
 */

namespace Modules\Addons\SoftcentsSms\Support;

final class DefaultTemplates
{
    public static function all(): array
    {
        return [
            [
                'name' => 'InvoicePaymentReminder',
                'type' => 'client',
                'template' => 'Hello {firstname},

Invoice: #{invoiceid} 
Amount: {currencysymbol}{amount} {currency} 
Due Date: {duedate} 
Status: {status}

Pay Online: {invoiceurl}

Thank you!',
                'variables' => '{firstname}, {lastname}, {fullname}, {email}, {invoiceid}, {amount}, {currency}, {currencysymbol}, {duedate}, {status}, {invoiceurl}',
                'description' => 'Invoice due reminder',
            ],
            [
                'name' => 'TicketUserReplyAdmin',
                'type' => 'admin',
                'template' => '[Admin Alert] New Ticket Reply

Client: {clientname} 
Ticket: #{ticketid} 
Sub: {subject}

Reply: {reply}

Check WHMCS for details.',
                'variables' => '{clientname}, {ticketid}, {subject}, {reply}',
                'description' => 'Admin alert for ticket reply',
            ],
            [
                'name' => 'UserLogin',
                'type' => 'client',
                'template' => 'Login Alert!

User: {firstname} {lastname} 
Email: {email} 
IP: {ip} 
Time: {time}

If this wasn\'t you, please secure your account.',
                'variables' => '{firstname}, {lastname}, {email}, {ip}, {time}',
                'description' => 'Client login alert',
            ],
            [
                'name' => 'TicketAdminReply',
                'type' => 'client',
                'template' => 'Support Update!

Hi {firstname}, 

Our staff {adminname} has replied to your ticket.

Ticket ID: #{ticketid} 
Subject: {subject}

View Reply: {ticketurl}

Thank you!',
                'variables' => '{firstname}, {lastname}, {ticketid}, {subject}, {adminname}, {ticketurl}',
                'description' => 'Ticket admin reply',
            ],
            [
                'name' => 'ClientAdd',
                'type' => 'client',
                'template' => 'Welcome to {companyname}!

Hi {firstname}, 
Thank you for registering with us.

Your Account Details: Email: {email} 
Password: {password}

Login Here: {loginurl}

We are excited to have you on board!',
                'variables' => '{firstname}, {lastname}, {email}, {password}, {companyname}, {loginurl}',
                'description' => 'New client welcome',
            ],
            [
                'name' => 'AfterModuleChangePassword',
                'type' => 'client',
                'template' => 'Security Update!

Hi {firstname}, 
The password for your hosting service has been changed successfully.

Service: {domain} 
Username: {username}
New Pass: {password}

If you did not make this change, please contact support immediately.

Thank you.',
                'variables' => '{firstname}, {lastname}, {domain}, {username}, {password}',
                'description' => 'Service password change',
            ],
            [
                'name' => 'TicketClose',
                'type' => 'client',
                'template' => 'Support Ticket Closed!

Hi {firstname}, 
Your ticket #{ticketid} regarding "{subject}" has been marked as resolved and closed.

We hope we were able to assist you effectively. If you need further help, feel free to open a new ticket or reply to this one.

Best Regards, 
Support Team',
                'variables' => '{firstname}, {lastname}, {ticketid}, {subject}, {email}',
                'description' => 'Ticket closed notice',
            ],
            [
                'name' => 'DomainRenewalNotice',
                'type' => 'client',
                'template' => 'Domain Expiry Alert!

Dear {firstname}, 
Your domain {domain} is expiring in {days} days.

Expiry Date: {expirydate}

Please renew your domain immediately to avoid service loss and redemption fees.

Login to our client area to renew now.

Thank you.',
                'variables' => '{firstname}, {lastname}, {domain}, {expirydate}, {days}',
                'description' => 'Domain renewal notice',
            ],
            [
                'name' => 'AfterRegistrarRegistrationAdmin',
                'type' => 'admin',
                'template' => 'Admin Alert: Domain {domain} has been successfully registered.',
                'variables' => '{domain}',
                'description' => 'Domain registration admin notice',
            ],
            [
                'name' => 'AcceptOrder',
                'type' => 'client',
                'template' => 'Order Confirmed!

Hi {firstname}, 
Great news! Your order has been accepted and is being processed.

Order Number: {ordernumber} 
Total Amount: {amount}

Thank you for choosing us!',
                'variables' => '{firstname}, {lastname}, {orderid}, {ordernumber}, {amount}',
                'description' => 'Order acceptance',
            ],
            [
                'name' => 'InvoiceCreated',
                'type' => 'client',
                'template' => 'New Invoice Generated!

Dear {firstname},
A new invoice #{invoiceid} has been created for your account.

Amount: {currencysymbol}{amount} {currency} 
Due Date: {duedate}

Please pay before the due date to avoid service interruption.

Pay Online: {invoiceurl}

Thank you!',
                'variables' => '{firstname}, {lastname}, {duedate}, {amount}, {invoiceid}, {currency}, {currencysymbol}, {invoiceurl}',
                'description' => 'Invoice creation notice',
            ],
            [
                'name' => 'InvoicePaid',
                'type' => 'client',
                'template' => 'Payment Received!

Dear {firstname}, 
Thank you! We have successfully received your payment for invoice #{invoiceid}.

Amount: {currencysymbol}{amount} {currency} 
Method: {paymentmethod} 
Date: {paiddate}

Your service is now active/renewed. You can download the receipt from the link below.

Link: {invoiceurl}

Best Regards!',
                'variables' => '{firstname}, {lastname}, {fullname}, {invoiceid}, {amount}, {paymentmethod}, {paiddate}, {currency}, {currencysymbol}, {invoiceurl}',
                'description' => 'Payment confirmation',
            ],
            [
                'name' => 'AfterModuleCreate',
                'type' => 'client',
                'template' => 'Service Activated!

Dear {firstname}, 
Your hosting account for {domain} is now active.

Login Details: 
User: {username} 
Pass: {password} 
Server IP: {serverip}

You can manage your service from the client area.

Happy Hosting!',
                'variables' => '{firstname}, {lastname}, {domain}, {username}, {password}, {serverip}',
                'description' => 'Service activation',
            ],
            [
                'name' => 'InvoicePaymentReminderFirst',
                'type' => 'client',
                'template' => 'Overdue Notice!

Dear {firstname}, 
Your invoice #{invoiceid} is now {overduedays} days overdue.

Amount: {currencysymbol}{amount} {currency} 
Original Due Date: {duedate}

Please settle the payment immediately to maintain your service.

Pay Now: {invoiceurl}

Thank you!',
                'variables' => '{firstname}, {lastname}, {email}, {invoiceid}, {amount}, {duedate}, {overduedays}, {currency}, {currencysymbol}, {invoiceurl}',
                'description' => '1st invoice reminder',
            ],
            [
                'name' => 'AfterModuleSuspend',
                'type' => 'client',
                'template' => 'Notice: Your service {domain} has been suspended due to an unpaid invoice. Please clear the dues to restore access.',
                'variables' => '{firstname},{domain}',
                'description' => 'Service suspension',
            ],
            [
                'name' => 'AdminLogin',
                'type' => 'admin',
                'template' => '???? Admin: {username}

 login Admin
Admin ID: {adminid}
 Date: {logindate}
Time: {logintime}
IP Address: {ipaddress}
Location: {location}
Browser: {browser}
Platform: {platform}',
                'variables' => '',
                'description' => 'Admin login alert',
            ],
            [
                'name' => 'AfterModuleUnsuspend',
                'type' => 'client',
                'template' => 'Service Restored: Your service {domain} has been unsuspended. Thank you for your payment.',
                'variables' => '{firstname},{domain}',
                'description' => 'Service unsuspension',
            ],
            [
                'name' => 'AfterRegistrarRegistration',
                'type' => 'client',
                'template' => 'Congratulations {firstname}! Your domain {domain} is now successfully registered.',
                'variables' => '{firstname},{domain}',
                'description' => 'Domain registration success',
            ],
            [
                'name' => 'TicketOpenAdmin',
                'type' => 'admin',
                'template' => '[Admin Alert] New Support Ticket

ID: #{ticketid} 
Client: {email} 
Subject: {subject}

A new ticket has been opened. Please check the admin panel to respond.',
                'variables' => '{subject}, {ticketid}, {email}',
                'description' => 'Admin new ticket notice',
            ],
            [
                'name' => 'InvoicePaymentReminderSecond',
                'type' => 'client',
                'template' => 'Overdue Invoice

Dear {firstname}, 
This is our second reminder. Your invoice #{invoiceid} is now {overduedays} days past due.

Total Amount: {currencysymbol}{amount} {currency} 
Due Date: {duedate}

To avoid service suspension, please clear your balance immediately.

Pay Securely: {invoiceurl}

Thank you.',
                'variables' => '{firstname}, {lastname}, {fullname}, {invoiceid}, {amount}, {duedate}, {overduedays}, {currency}, {currencysymbol}, {invoiceurl}',
                'description' => '2nd invoice reminder',
            ],
            [
                'name' => 'ClientChangePassword',
                'type' => 'client',
                'template' => 'Security Notice: Your account password was changed recently. If you did not do this, contact support.',
                'variables' => '',
                'description' => 'Client password change notice',
            ],
            [
                'name' => 'AfterRegistrarRenewal',
                'type' => 'client',
                'template' => 'Success: Your domain {domain} has been renewed. Thank you for staying with us.',
                'variables' => '{domain}',
                'description' => 'Domain renewal success',
            ],
            [
                'name' => 'AfterRegistrarRegistrationFailedAdmin',
                'type' => 'admin',
                'template' => 'Error: Registration for domain {domain} has failed. Please check the logs.',
                'variables' => '{domain}',
                'description' => 'Domain reg failure admin',
            ],
            [
                'name' => 'AfterRegistrarRenewalFailedAdmin',
                'type' => 'admin',
                'template' => 'Error: Renewal for domain {domain} has failed. Manual action required.',
                'variables' => '{domain}',
                'description' => 'Domain renewal failure admin',
            ],
            [
                'name' => 'InvoiceCancelled',
                'type' => 'client',
                'template' => 'Invoice Cancelled

Dear {firstname}, 
This is to inform you that invoice #{invoiceid} for {currencysymbol}{total} {currency} has been cancelled.

No further action is required for this invoice. If you have any questions, please open a support ticket.

Thank you for being with us.',
                'variables' => '{firstname}, {lastname}, {fullname}, {invoiceid}, {total}, {currency}, {currencysymbol}, {invoiceurl}',
                'description' => 'Invoice cancellation',
            ],
            [
                'name' => 'AfterModuleChangePackage',
                'type' => 'client',
                'template' => 'Package Updated: Your service {domain} has been successfully upgraded/downgraded.',
                'variables' => '{domain}',
                'description' => 'Service package change',
            ],
            [
                'name' => 'InvoicePaymentReminderThird',
                'type' => 'client',
                'template' => 'URGENT: Final Notice of Overdue Payment

Dear {firstname}, 
This is our final reminder. Invoice #{invoiceid} is now {overduedays} days past due and your service is at risk of termination.

Amount Due: {currencysymbol}{amount} {currency}

To prevent permanent data loss and service deletion, please pay NOW.

Pay Link: {invoiceurl}

Ignore if already paid.',
                'variables' => '{firstname}, {lastname}, {fullname}, {invoiceid}, {amount}, {duedate}, {overduedays}, {currency}, {currencysymbol}, {invoiceurl}',
                'description' => 'Final invoice reminder',
            ],
            [
                'name' => 'TicketOpen',
                'type' => 'client',
                'template' => 'Hello {firstname}, your ticket "{subject}" has been opened. Ticket ID: #{ticketid}. We will respond soon.',
                'variables' => '{firstname},{subject}',
                'description' => 'Ticket open confirmation',
            ],
            [
                'name' => 'AfterModuleTerminate',
                'type' => 'client',
                'template' => 'Service Terminated: Your {domain} has been terminated due to non-payment. All associated data has been removed.',
                'variables' => '{firstname},{domain}',
                'description' => 'Service termination.',
            ],
        ];
    }
}
