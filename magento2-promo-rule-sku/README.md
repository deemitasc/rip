Magento 2 - Promo Rule Sku
==================

## Overview

This module adds a `rule_sku` field to the admin that can be set when creating/managing catalog and cart price rules. Alone this module does not accomplish anything, as it does do anything with the data saved in this field. It is intended as a support for the `Ripen_Prophet21` module, which uses this field as the SKU to represent the given promotion when it transfers promotion discounts as negative-value line items.

## TODO

This module does not necessarily make sense as an independent module and ideally would be merged into `Ripen_Prophet21`.
