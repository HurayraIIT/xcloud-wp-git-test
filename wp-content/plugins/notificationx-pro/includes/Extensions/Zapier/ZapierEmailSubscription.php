<?php
/**
 * Zapier Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Zapier;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\Zapier\ZapierEmailSubscription as ZapierEmailSubscriptionFree;

/**
 * Zapier Extension
 */
class ZapierEmailSubscription extends ZapierEmailSubscriptionFree {
    /**
     * Instance of Zapier
     *
     * @var Zapier
     */
    use Zapier;


}
