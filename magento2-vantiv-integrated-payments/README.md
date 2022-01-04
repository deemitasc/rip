Magento 2 Worldplay Integrated Payments
==================

## Overview

This module contains a custom payment method to integrate Magento with Worldpay Integrated Payments.

A couple notes for disambiguation:
* The module is named Vantiv and this was offered by Vantiv prior to Worlpay aquiring it; before that it was operated by a company known as Elements.
* Integrated Payments is a distinct product from that used by the official Worldpay payments extension on the Magento Marketplace. There is no official Magento extension for Integrated Payments as of Nov 2021, likely due to being treated by Worldpay as a legacy product.

## Features

The module can operate in any of the following ways:

* Authorize
* Authorize & Capture (only partially implemented as of Nov 2021)
* Payment Account Creation Only

Data from the transaction, including the payment account ID is saved to the Magento order payment record. Data which does not have native Magento fields are serialized into the `additional_data` column. This data can then later be used by an ERP integration, such as with Prophet 21. (This module does not currently integrate with `Magento_Vault` for saving credit card methods, so those data structures are not used.)

## Configuration

Accessible via: *Admin > Stores > Configuration > Sales > Payment Methods > Worldpay (Vantiv) Integrated Payments*

The purpose of the configuration options for the module are commented inline in the admin section.

## PCI Compliance

Use of this plugin typically necessiates a SAQ-D level of compliance overhead due to the fact that cardholder data passes through the server. While this is true, it is important to note that cardholder data is only ever stored in memory before being passed to the Worldpay API. It is not stored to database or disk by the module in any form (encrypted or unencrypted).

## Certification Harness

Worldpay requires integration code to undergo a certification process, as part of which the functionality of the module must be demonstrated. This process requires making a standard series of API calls. For this purpose a certification harness is included that triggers these calls upon request to the harness URL. The harness is disabled by default and must be enabled only temporarily during a (re)certification process. Ideally access to this URL and/or the entire server is also restricted by an IP allowlist, though that is assumed to be handled externally to this module.

When enabled, this is available at `/certification/gateway/certification`.
