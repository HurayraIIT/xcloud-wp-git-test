<?php
/**
 * Zapier Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Zapier;

use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\Zapier\ZapierConversions as ZapierConversionsFree;

/**
 * Zapier Extension
 */
class ZapierConversions extends ZapierConversionsFree {
    /**
     * Instance of Zapier
     *
     * @var Zapier
     */
    use Zapier;

}
