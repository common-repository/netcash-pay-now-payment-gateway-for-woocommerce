<?php
namespace Netcash\PayNow;

/**
 * A helper class to interact with the Netcash subscription update API
 * Class SubscriptionUpdate
 * @package Netcash
 */
class SubscriptionUpdate
{

    protected $debugging = false;
    // Default service key
    protected $serviceKey = '';
    protected $uniqueId = '';

    /**
     * @param string $service_key The Pay Now service key
     * @param string $unique_id Unique reference of the original invoice
     */
    public function __construct($service_key, $unique_id)
    {
        $this->setServiceKey($service_key);
        $this->setUniqueId($unique_id);
    }

    /**
     * Set the Service Key
     *
     * @param string $key The service key to use
     */
    public function setServiceKey($key)
    {
        $this->serviceKey = $key;
    }
    /**
     * Get the Service Key
     *
     * @return string The service key to use
     */
    public function getServiceKey()
    {
        return $this->serviceKey;
    }

    /**
     * Set the Order ID
     *
     * @param string $key The service key to use
     */
    public function setUniqueId($key)
    {
        $this->uniqueId = $key;
    }
    /**
     * Get the order ID
     *
     * @return string The order ID to use
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * Whether debugging is on or of
     *
     * @param boolean $bool
     */
    public function setDebugging($bool)
    {
        $this->debugging = $bool;
    }

    /**
     * Get the Netcash host to use for the API
     *
     * @return string The host
     */
    private function getHost()
    {

        return "https://ws.netcash.co.za/PayNow/";
    }

    /**
     * Set the subscription 'Active' state to false
     * Shorcut for updateSubscription(...)
     */
    public function deactivateSubscription() {
        return $this->updateSubscription(1, \Netcash\PayNow\Types\SubscriptionFrequency::ANNUALLY, date('Ymd'), 1, false);
    }

    // /**
    //  * Set the subscription 'Active' state to true
    //  */
    // public function activateSubscription() {
    //     return $this->updateSubscription([
    //         'Active' => true
    //     ]);
    // }

    /**
     * Updates a subscription
     * https://api.netcash.co.za/inbound-payments/pay-now/subscription-update-service/
     *
     * @param int   $cycles    Subscription cycle – number of subscription payments to be made
     * @param int   $frequency Subscription frequency
     * @param int   $start     Subscription start date CCYYMMDD
     * @param float $amount    Subscription recurring amount
     * @param bool  $active    Whether the subscription is active or not
     *
     * @return bool|string True on success. An error string if not successful
     */
    public function updateSubscription($cycles, $frequency, $start, $amount, $active = True)
    {
        $allowedFreqs = [
            \Netcash\PayNow\Types\SubscriptionFrequency::MONTHLY,
            \Netcash\PayNow\Types\SubscriptionFrequency::WEEKLY,
            \Netcash\PayNow\Types\SubscriptionFrequency::BI_WEEKLY,
            \Netcash\PayNow\Types\SubscriptionFrequency::QUARTERLY,
            \Netcash\PayNow\Types\SubscriptionFrequency::SIX_MONTHLY,
            \Netcash\PayNow\Types\SubscriptionFrequency::ANNUALLY,
            \Netcash\PayNow\Types\SubscriptionFrequency::DAILY,
        ];

        if($cycles<1) {
            throw new \Exception('Cycle must be more than 1');
        }
        if(!$frequency || !in_array($frequency, $allowedFreqs)) {
            throw new \Exception('Invalid frequency');
        }
        if(!$start) {
            throw new \Exception('Invalid start date');
        }
        if($amount<=0) {
            throw new \Exception('Amount must be more than 1');
        }

        $params = [
            'Active' => (bool) $active,
			'M1' => $this->getServiceKey() , //Pay Now Service Key
			'P2' => $this->getUniqueId(), //Unique reference of the original invoice
			'M17' => $cycles, //Subscription cycle – number of subscription payments to be made
			'M18' => $frequency, //Subscription frequency

			'M19' => $start, //Subscription start date CCYYMMDD
			'M20' => $amount, //Subscription recurring amount
        ];

        $soap_url = 'https://ws.netcash.co.za/Paynow/PayNow.svc?wsdl';

        $soap    = new \SoapClient(
            $soap_url,
            array(
                "trace"        => 1,
                'soap_version' => SOAP_1_1,
            )
        );
        $headers = array(
            new \SoapHeader(
                'http://www.w3.org/2005/08/addressing',
                'Action',
                'http://tempuri.org/IPayNow/UpdateSubscriptions'
            ),
            new \SOAPHeader(
                'http://www.w3.org/2005/08/addressing',
                'To',
                'https://ws.netcash.co.za/PayNow/PayNow.svc'
            )
        );


        // set the headers of Soap Client.
        $soap->__setSoapHeaders($headers);
        // text/xml; charset=utf-8
        $result = $soap->UpdateSubscriptions($params);

        // See status codes here:
        // https://www.netcash.co.za/netcash/partners_developers-technical_documents-sage_connect.asp
        if ($result && isset($result->UpdateSubscriptionsResult)) {
            $responseCode = $result->UpdateSubscriptionsResult;

            // Continue only if the response is success ('000')
            if ($responseCode == '000') {
                return true;
            }

            switch($responseCode) {
                case '100':
                    return 'Authentication failed. Ensure that the service key in the method call is correct';
                case '200':
                    return 'Web service error contact support@netcash.co.za';
                case '311':
                    return 'Merchant reference not found. Ensure that the value in P2 refers to an existing subscription';
                case '313':
                    return 'Invalid frequency.'; //Ensure that M18 contains one of the permitted values
                case '314':
                    return 'Invalid number of cycles.'; // M17 must be greater than 0
                case '315':
                    return 'Invalid subscription start date. Format is CCYYMMDD';
            }

            return false;
        }


        return 'Could not update subscription.';
    }


    /**
     * Given a subscription order, will return a Netcash Subscription
     *
     * @param string $service_key The Netcash service key
     * @param WC_Order $order The order
     *
     * @return Netcash\PayNow\Form A sub form
     */
    public static function orderToNetcashSubscription($service_key, $order) {

        $form = new \Netcash\PayNow\Form($service_key);
        $form->setIsSubscription( true );

        $form->setUniqueId( $order->get_id() );

        $subscriptions      = wcs_get_subscriptions_for_order( $order, array( 'order_type' => 'parent' ) );
        $first_subscription = $subscriptions && count( $subscriptions ) ? array_shift( $subscriptions ) : null;

        if ( ! $first_subscription ) {
            throw new \Exception( 'Expected subscription.' );
        }

        $subscription_start = gmdate( 'Y-m-d', $first_subscription->get_time( 'next_payment', 'site' ) );
        $period             = $first_subscription->get_billing_period();

        $subscription_interval = (int) $first_subscription->get_billing_interval();

        // Initial payment and sign up fee is already taken into account in $order->get_total().
        $price_per_period          = WC_Subscriptions_Order::get_recurring_total( $order );
        $subscription_installments = wcs_cart_pluck( WC()->cart, 'subscription_length' );

        // We have a recurring payment.
        $infinite_installments = intval( $subscription_installments ) === 0;

        switch ( strtolower( $period ) ) {
            case 'day':
                $form->setSubscriptionFrequency( \Netcash\PayNow\Types\SubscriptionFrequency::DAILY );

                break;
            case 'week':
                if ( 2 === $subscription_interval ) {
                    $form->setSubscriptionFrequency( \Netcash\PayNow\Types\SubscriptionFrequency::BI_WEEKLY );
                } else {
                    $form->setSubscriptionFrequency( \Netcash\PayNow\Types\SubscriptionFrequency::WEEKLY );
                }
                break;
            case 'year':
                $form->setSubscriptionFrequency( \Netcash\PayNow\Types\SubscriptionFrequency::ANNUALLY );
                break;
            case 'month':
                // fall through
            default:
                if ( 6 === $subscription_interval ) {
                    $form->setSubscriptionFrequency( \Netcash\PayNow\Types\SubscriptionFrequency::SIX_MONTHLY );
                } elseif ( 3 === $subscription_interval ) {
                    $form->setSubscriptionFrequency( \Netcash\PayNow\Types\SubscriptionFrequency::QUARTERLY );
                } else {
                    $form->setSubscriptionFrequency( \Netcash\PayNow\Types\SubscriptionFrequency::MONTHLY );
                }
                break;
        }


        $form->setSubscriptionStartDate( $subscription_start );
        $form->setSubscriptionAmount( $price_per_period );
        // Cannot be infinite. A max of 999 cyles can be set.
        $form->setSubscriptionCycle( $infinite_installments ? 999 : $subscription_installments );

        return $form;
    }
}
