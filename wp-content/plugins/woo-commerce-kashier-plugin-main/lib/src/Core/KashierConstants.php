<?php

namespace ITeam\Kashier\Core;

/**
 * Class KashierConstants
 * Placeholder for Kashier Constants
 *
 * @package ITeam\Kashier\Core
 */
class KashierConstants
{
    const SDK_NAME = 'Kashier-PHP-SDK';
    const SDK_VERSION = '2.0.0';

    const REST_SANDBOX_ENDPOINT = 'https://test-api.kashier.io';
    const REST_LIVE_ENDPOINT = 'https://api.kashier.io';

    /**
     * base url for fep added
     * Summary of REST_FEP_SANDBOX_ENDPOINT
     * @var string
     * @author msamman <msamman@kashier.io>
     */
    const REST_FEP_SANDBOX_ENDPOINT = 'https://test-fep.kashier.io';
    const REST_FEP_LIVE_ENDPOINT = 'https://fep.kashier.io';
    const IFRAME_BASE_URL = 'https://payments.kashier.io';
    const URL_PATH_CHECKOUT = '/checkout';
    const URL_PATH_TOKENIZATION = '/tokenization';
}
