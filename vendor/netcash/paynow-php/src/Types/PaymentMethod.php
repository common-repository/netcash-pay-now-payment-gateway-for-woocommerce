<?php

namespace Netcash\PayNow\Types;

/**
 * Class PaymentMethod
 *
 * Assist in using subscriptions
 *
 */
abstract class PaymentMethod extends BasicEnum
{
    const CREDIT_CARD = 1;
    const EFT = 2;
    const RETAIL = 3;
    const OZOW = 4;
    const MASTERPASS = 5;
    const VISA_CHECKOUT = 6;
    const MASTERPASS_QR = 7;
}
