Magento 2 Current Statement
==================

## Overview

This module provides an interface within the customer account area to view their current statement (unpaid balance) as well as view invoices. Invoices may be filtered by date and/or by paid/unpaid status.

All data is pulled in realtime from Prophet 21 via API; it is not depending on Magento's native "invoice" data, which is typically incomplete. It connects with Prophet 21 via a third-party middleware API [provided by SimpleApps](https://www.simpleapps.com/products/prophet-21-api). The actual API calls are handled by another module `Ripen_SimpleApps`.

This module can operate standalone, or it can optionally by extended by a separate module `Ripen_PayMyBill` that allows for unpaid invoices to be paid through the website.

## Configuration

No specific setup of this module is needed past installation, though it relies on `Ripen_SimpleApps` to be installed and configured.
