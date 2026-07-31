<?php

declare(strict_types=1);

/**
 * SaintMonarc Marketplace Platform Configuration (VEYRA Architecture)
 */

return [
    'name' => getenv('PLATFORM_NAME') ?: 'VEYRA Marketplace',
    'short_name' => getenv('PLATFORM_SHORT_NAME') ?: 'VEYRA',
    'owner' => getenv('PLATFORM_OWNER') ?: 'Burkay',
    'domain' => getenv('PLATFORM_DOMAIN') ?: 'veyra.com',
    'support_email' => getenv('PLATFORM_SUPPORT_EMAIL') ?: 'support@veyra.com',
    'currency' => 'TRY',
    
    // Default Official Primary Vendor
    'primary_vendor' => [
        'id' => 1,
        'name' => 'SaintMonarc Official Store',
        'slug' => 'saintmonarc',
        'email' => 'official@saintmonarc.com'
    ],
    
    // Default Marketplace Settings
    'default_commission_rate' => 10.00, // percentage
    'auto_approve_vendors' => false,
    'auto_approve_products' => false,
    'payout_min_amount' => 100.00
];
