Magento 2 P21 Order Custom Attributes
==================

## Overview
This module adds the following fields at the checkout:
- Order Comments
- Order Special Instructions
- PO Number
- Checkbox for "Ship Complete Order Only"

Note that this module does not do anything with the data entered other than saving it through to the order record. It is expected that an external module such as `Ripen_Prophet21` will pick up these attributes and transfer them to an ERP.

## Configuration

Each field may be enabled or disabled individually at *Stores > Configuration > Sales > Checkout > Checkout Options*.
