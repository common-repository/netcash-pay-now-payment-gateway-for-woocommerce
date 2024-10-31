<?php

namespace Netcash\PayNow;

use Netcash\PayNow\Types\PaymentMethod;

class Response
{
    public static $OFFLINE_CODES = [ PaymentMethod::EFT, PaymentMethod::RETAIL ]; // 2 for EFT, 3 for Retail

    const ORDER_STATUS_NONE = 'none';
    const ORDER_STATUS_SUCCESS = 'success';
    const ORDER_STATUS_PENDING = 'pending';
    const ORDER_STATUS_DECLINED = 'declined';
    const ORDER_STATUS_CANCELLED = 'cancelled';

    /**
     * @var string The order status
     */
    protected $orderStatus;

    /**
     * @var array The response data
     */
    private $responseData = [
        'TransactionAccepted'  => false,
        'Reason'               => "",
        'CardHolderIpAddr'     => null,
        'RequestTrace'         => null,
        'Reference'            => "",
        'Extra1'               => null,
        'Extra2'               => null,
        'Extra3'               => null,
        'Amount'               => 0,
        'Method'               => null,
        'type'                 => null,
        'SubscriptionAccepted' => null,
        'SubscriptionReason'   => "",
    ];

    /**
     * Response constructor.
     *
     * @param array $postData The POSTed data
     */
    public function __construct($postData)
    {
        $this->setOrderStatus(self::ORDER_STATUS_NONE);

        // Strip any slashes in data
        $strippedData = [];
        foreach (array_filter($postData) as $key => $val) {
            $strippedData[$key] = stripslashes($val);
        }

        $this->responseData = array_merge($this->responseData, $strippedData);

        if (isset($this->responseData['Reason'])) {

            $hasDeclinedReason = stristr($this->responseData['Reason'], 'declined') !== false;
            $hasPendingReason = stristr($this->responseData['Reason'], 'pending') !== false;
            $hasCancelledReason = stristr($this->responseData['Reason'], 'cancelled') !== false;

            if ($hasPendingReason) {
                $this->setOrderStatus(self::ORDER_STATUS_PENDING);
            }

            // Cancelled by user clicking cancel link on Pay Now
            if ($hasCancelledReason) {
                $this->setOrderStatus(self::ORDER_STATUS_CANCELLED);
            }

            if ($hasDeclinedReason) {
                // Payment declined
                $this->setOrderStatus(self::ORDER_STATUS_DECLINED);

                // Cancelled by user clicking cancel link on Ozow. They send "Declined" as reason
                // We can't set it to cancelled. It could've failed for some other reason as well
//                if($this->wasMethod(PaymentMethod::OZOW)) {
//                    $this->setOrderStatus( self::ORDER_STATUS_CANCELLED );
//                }
            }

        }

        if ($this->wasAccepted() && $this->responseData['Reason'] == 'Success') {
            $unsuccessfulStates = [
                self::ORDER_STATUS_PENDING,
                self::ORDER_STATUS_CANCELLED,
                self::ORDER_STATUS_DECLINED
            ];

            if (!in_array($this->getOrderStatus(), $unsuccessfulStates)) {
                $this->setOrderStatus(self::ORDER_STATUS_SUCCESS);
            }
        }
    }

    /**
     * Set the order status
     * @param string $status The new status
     */
    public function setOrderStatus($status)
    {
        $this->orderStatus = $status;
    }

    /**
     * Get the order status
     *
     * @return string The order status
     */
    public function getOrderStatus()
    {
        return $this->orderStatus;
    }

    /**
     * Whether the transaction was successfully handled by the gateway
     *
     * @return bool
     */
    public function wasAccepted()
    {
        if (isset($this->responseData['TransactionAccepted'])) {
            return $this->responseData['TransactionAccepted'] == 'true';
        }
        return false;
    }

    /**
     * Whether the subscription was successfully handled by the gateway
     *
     * @return bool
     */
    public function wasSubscriptionAccepted()
    {
        if (isset($this->responseData['SubscriptionAccepted'])) {
            return $this->responseData['SubscriptionAccepted'] == 'true';
        }
        return false;
    }

    /**
     * Whether the payment is still pending
     * @return bool
     */
    public function isPending()
    {
        return $this->getOrderStatus() === self::ORDER_STATUS_PENDING;
    }

    /**
     * Whether the transaction was declined
     * @return bool
     */
    public function wasDeclined()
    {
        return $this->getOrderStatus() === self::ORDER_STATUS_DECLINED;
    }

    /**
     * Whether the transaction was cancelled
     * @return bool
     */
    public function wasCancelled()
    {
        return $this->getOrderStatus() === self::ORDER_STATUS_CANCELLED;
    }

    /**
     * Check if this is a 'offline' payment like EFT or retail
     *
     * @return bool
     */
    public function wasOfflineTransaction()
    {
        // If !$accepted, means it's the callback OR a failed transaction.
        // If $accepted, and in array, means it's the actual called response
        return in_array($this->getMethod(), self::$OFFLINE_CODES);
    }

    /**
     * Check if this is was 'credit card' payment
     *
     * @return bool
     */
    public function wasCreditCardTransaction()
    {
        return $this->wasMethod(PaymentMethod::CREDIT_CARD);
    }

    /**
     * Get the data
     * @return array
     */
    public function getData()
    {
        return $this->responseData;
    }

    /**
     * The amount that was charged / attempted
     * @return float
     */
    public function getAmount()
    {
        return floatval($this->responseData['Amount']);
    }

    /**
     * Get the POSTed order ID
     * It was set by the p2 Unique ID field when POSTed
     *
     * @return mixed
     */
    public function getOrderID()
    {
        // Get actual order ID from the initial unique ID
        $pieces = explode("__", $this->responseData['Reference']);
        return $pieces[0];
    }

    /**
     * Get the POSTed Reason for the transaction
     *
     * @return string|null
     */
    public function getReason()
    {
        return isset($this->responseData['Reason']) ? $this->responseData['Reason'] : null;
    }

    /**
     * Get the POSTed Reason for the subscription
     *
     * @return string|null
     */
    public function getSubscriptionReason()
    {
        return isset($this->responseData['SubscriptionReason']) ? $this->responseData['SubscriptionReason'] : null;
    }

    /**
     * Get the payment method
     *
     * @return int|null The payment method code
     */
    public function getMethod()
    {
        return isset($this->responseData['Method']) ? intval($this->responseData['Method']) : null;
    }

    /**
     * Check the payment method
     *
     * @param PaymentMethod The payment method
     *
     * @return bool
     */
    public function wasMethod($method)
    {
        return $this->getMethod() === $method;
    }

    /**
     * Get extra data sent to the gateway
     * @param int $index 1 - 3
     *
     * @return mixed|null
     */
    public function getExtra($index)
    {
        switch ($index) {
            case 1:
                return $this->responseData['Extra1'];
            case 2:
                return $this->responseData['Extra2'];
            case 3:
                return $this->responseData['Extra3'];
        }

        return null;
    }

    /**
     * Get the credit card token
     *
     * @return string|null
     */
    public function getCreditCardToken()
    {
        return isset($this->responseData['ccToken']) ? intval($this->responseData['ccToken']) : null;
    }

    /**
     * Get the credit card holder's name
     *
     * @return string|null
     */
    public function getCreditCardHolder()
    {
        return isset($this->responseData['ccHolder']) ? intval($this->responseData['ccHolder']) : null;
    }

    /**
     * Get the credit card expiry date
     *
     * @return string|null The expiry date as MMYYYY
     */
    public function getCreditCardExpiry()
    {
        return isset($this->responseData['ccExpiry']) ? intval($this->responseData['ccExpiry']) : null;
    }

    /**
     * Get the masked credit card number
     *
     * @return string|null
     */
    public function getCreditCardMaskedNumber()
    {
        return isset($this->responseData['ccMasked']) ? intval($this->responseData['ccMasked']) : null;
    }
}
