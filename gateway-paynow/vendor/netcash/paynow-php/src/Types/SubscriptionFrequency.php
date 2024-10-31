<?php

namespace Netcash\PayNow\Types;

use Netcash\PayNow\Exceptions\ValidationException;

/**
 * Class SubscriptionCycle
 *
 * Assist in using subscriptions
 *
 * Documentation: https://api.netcash.co.za/inbound-payments/pay-now/pay-now-invoice-statement-quotation/#invoicing
 *
 */
abstract class SubscriptionFrequency extends BasicEnum
{
    const MONTHLY = 1;
    const WEEKLY = 2;
    const BI_WEEKLY = 3;
    const QUARTERLY = 4;
    const SIX_MONTHLY = 5;
    const ANNUALLY = 6;
    const DAILY = 7;
}
