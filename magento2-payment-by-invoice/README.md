Magento 2 Payment by Invoice
============================

## Overview

A Magento module provides a custom offline Magento payment for payment on invoice that looks up and respects payment terms eligibility as configured within P21. All eligibility data is pulled live from P21 at the point of checkout so is not subject to an asynchronous data sync that be be out of date.

## Configuration

Accessible via: *Admin > Stores > Configuration > Sales > Payment Methods > Payment by Invoice*

The module is able to take following flags or statuses from P21 into account to determine eligibility for payment on acccount, but since not every business may want to enforce all of them, configuration is provided to enable or disable them:

* **Validate Customer:** Allow only if customer is eligible for payment by invoice _at all_ in P21. This is determined by making sure the P21 account both has a "net days" value set and that it does not have the "payment required upon release" flag set.
* **Validate Customer Credit Status**: Allow only if credit status is not set to "COD."
* **Validate Customer Credit Limit Balance**: Allow only if this order would not push the customer past their configured credit limit, taking into account credit already used.
