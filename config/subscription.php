<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Subscription Plans
|--------------------------------------------------------------------------
|
| Per-plan limits and pricing. `listing_limit` of null means unlimited.
| Prices are in BDT. Plans drive the listing-creation quota enforced by the
| ListingService.
|
*/

return [
    'default' => 'free',

    'plans' => [
        'free' => [
            'label' => 'Free',
            'price' => 0,
            'duration_days' => null, // never expires
            'listing_limit' => 2,
            'featured' => false,
        ],
        'standard' => [
            'label' => 'Standard',
            'price' => 500,
            'duration_days' => 30,
            'listing_limit' => 10,
            'featured' => false,
        ],
        'premium' => [
            'label' => 'Premium',
            'price' => 1500,
            'duration_days' => 30,
            'listing_limit' => null, // unlimited
            'featured' => true,
        ],
    ],
];
