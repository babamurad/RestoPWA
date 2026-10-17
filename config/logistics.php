<?php

return [
    // How long a courier has to accept an automatically offered order
    'offer_timeout_seconds' => (int) env('COURIER_OFFER_TIMEOUT', 30),
];
