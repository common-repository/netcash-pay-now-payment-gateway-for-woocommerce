<?php

namespace Netcash\PayNow\Types;

/**
 * Class SubscriptionCycle
 *
 * Assist in using subscriptions
 *
 * Documentation: https://api.netcash.co.za/inbound-payments/pay-now/pay-now-invoice-statement-quotation/#invoicing
 *
 */
abstract class FieldType extends BasicEnum
{
    const SERVICE_KEY = "m1"; // Service Key
    const SOFTWARE_VENDOR_KEY = "m2"; // Software Vendor Key
    const UNIQUE_ID = "p2"; // Unique ID
    const DESCRIPTION = "p3"; // Description of goods
    const AMOUNT = "p4"; // Amount to be settled

    const BUDGET = "Budget"; // Compulsory must be Y

    const EXTRA1 = "m4"; // An extra field
    const EXTRA2 = "m5"; // An extra field
    const EXTRA3 = "m6"; // An extra field

    const EMAIL = "m9"; // Cardholder email
    const RETURN_STRING = "m10"; // Data returned to cart via the Accept & Decline URLs
    const CELLPHONE = "m11"; // Cardholder mobile number (SA Only, 10 digits starting with a 0)

    const RETURN_CARD_DETAIL = "m14"; // Whether to return card details
    const CARD_TOKEN = "m15"; // A previously returned Credit Card token

    // Subscriptions
    const SUBSCRIPTION_IS_SUBSCRIPTION = "m16"; // Whether this is a subscription
    const SUBSCRIPTION_CYCLE = "m17"; // Number of subscription payments to be made
    const SUBSCRIPTION_FREQUENCY = "m18"; // Frequency: E.g., monthly, weekly, bi-weekly, etc
    const SUBSCRIPTION_START_DATE = "m19"; // Date to start: CCYYMMDD
    const SUBSCRIPTION_RECURRING_AMOUNT = "m20"; // The subscription amount
}
