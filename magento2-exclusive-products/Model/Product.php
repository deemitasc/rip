<?php
namespace Ripen\ExclusiveProducts\Model;

class Product
{
    const PRODUCT_RESTRICTION_FIELDS = [
        'exclusive_to',
        'allowed_for',
        'blocked_for'
    ];
}
