Magento 2 SimpleApps API
==================

## Overview

This module is a Ripen-built SDK for working with the SimpleApps middleware API for Prophet 21. This module is intended to fully encapsulate the specifics of the API as much as possible. We have often followed the API's lead in the exposed methods, arguments and return data structures, but the intention is to do this only where the API has chosen natural representations of the P21 business domain. Where the SimpleApps API has made more idiosyncratic choices, we have exposed more ergonomic method signatures for use by consumers of this module.

## Design Goals

1. Though this is built as a Magento module and uses Magento framework capabilities (dependency injection, configuration storage, logging, etc.), this module should remain entirely agnostic to Magento data structures. This means that Magento native objects representing entities like products or orders should never be passed or returned from methods in this module.
2. Though for simplicity we have not actually created a separate interface for the `Api` class to implement, the public methods of the `Api` class are intended to consistute an implicit interface against which another implementation could be written that uses a different P21 API (such as the Epicor native API). This possibility should be kept in mind as changes are made.

## Base Configuration
Accessible via *Stores > Configuration > Services > Simple Apps*

* **Base URL:** https://api.simpleapps.net/ (default and no current need to ever override this)
* **API Key:** (provided by SimpleApps)
* **Site ID:** (provided by SimpleApps)

## Debugging

This module includes support for logging all calls to the API (both arguments passed and return values). Because this may cause performance impact and because the data logged may be sensitive (no capability for filtering out sensitive data is included), we chose not to implement a simple on/off toggle that could be accidentally left on. Instead, there is a configuration option "Debug Mode Active Until" whereby an end datetime must be entered, after which verbose logging will automatically stop.

Note that low-level connection or encoding errors will always be logged, regardless of the debug setting.
