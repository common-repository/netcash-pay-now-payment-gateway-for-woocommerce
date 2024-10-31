<?php
namespace Netcash\PayNow;

class PayNow
{
    /**
     * @param array $responseData The $_POST data
     * @param $expectedOderId
     * @param $expectedAmount
     *
     * @return bool True on success. False on failure
     */
    public static function validateResponse($responseData, $expectedOderId, $expectedAmount)
    {
        $response = new Response($responseData);

        // Make sure the data was from Netcash
        if (empty($responseData)) {
            // Error.
            return false;
        }

        // Make sure the order id matches
        if (!$expectedOderId || strval($expectedOderId) !== strval($response->getOrderID())) {
            return false;
        }

        // Make sure the amounts match
        if (!self::checkEqualAmounts($response->getAmount(), $expectedAmount)) {
            return false;
        }

        return true;
    }

    /**
     *
     * Checks to see whether the given amounts are equal using a proper floating
     * point comparison with an Epsilon which ensures that insignificant decimal
     * places are ignored in the comparison.
     *
     * eg. 100.00 is equal to 100.0001
     *
     * @param $amount1 Float 1st amount for comparison
     * @param $amount2 Float 2nd amount for comparison
     *
     * @return bool
     */
    public static function checkEqualAmounts($amount1, $amount2)
    {
        $epsilon = 0.01;
        if (abs(floatval($amount1) - floatval($amount2)) > $epsilon) {
            return false;
        } else {
            return true;
        }
    }
}
