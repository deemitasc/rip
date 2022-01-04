Magento 2 P21 Pay My Bill
==================

## Overview

This module provides the ability for customers to use a credit card through the website to pay their open invoices as pulled from P21. This module serves to extend `Ripen_CurrentStatement`, adding in the payment functionality to the invoice view interface displayed there.

Payment is handled by direct integration with `Ripen_VantivIntegratedPayments`; other payment methods are not supported.

When an invoice is paid, an email is sent to a configurable admin email address (typically accounts receivable), who would then update the invoice in P21. Due to middleware API limits at the time, this module does not directly update the invoice within P21.

The module supports an optional/configurable credit card surcharge to be automatically added to the amount paid.

## Configuration

Accessible via *Stores > Configuration > Services > Pay My Bill*
