<?php

namespace Netcash\PayNow;

use Netcash\PayNow\Types\FieldType;
use Netcash\PayNow\Types\SubscriptionFrequency;
use Netcash\PayNow\Exceptions\ValidationException;

/**
 * Class Form
 *
 * Assist in creating the form data to POST to Pay Now
 *
 * Documentation: https://api.netcash.co.za/inbound-payments/pay-now/pay-now-gateway/
 *
 */
class Form
{

    /**
     * @var string The URL to POST to
     */
    protected $actionUrl = "https://paynow.netcash.co.za/site/paynow.aspx";

    /**
     * @var string
     */
    protected $serviceKey = "";

    /**
     * @var bool Whether testing is on or off
     */
    protected $testing = false;

    protected $fields = [
        FieldType::SERVICE_KEY => "",
        FieldType::SOFTWARE_VENDOR_KEY => "",
        FieldType::UNIQUE_ID => "",
        FieldType::DESCRIPTION => "",
        FieldType::AMOUNT => 0,

        FieldType::BUDGET => "N",

        FieldType::EXTRA1 => "",
        FieldType::EXTRA2 => "",
        FieldType::EXTRA3 => "",

        FieldType::EMAIL  => "",
        FieldType::RETURN_STRING => "",
        FieldType::CELLPHONE => "",

        FieldType::RETURN_CARD_DETAIL => 0,
        FieldType::CARD_TOKEN => "",

        FieldType::SUBSCRIPTION_IS_SUBSCRIPTION  => 0,
        FieldType::SUBSCRIPTION_CYCLE            => "",
        FieldType::SUBSCRIPTION_FREQUENCY        => "",
        FieldType::SUBSCRIPTION_START_DATE       => "",
        FieldType::SUBSCRIPTION_RECURRING_AMOUNT => "",
    ];

    public function __construct($serviceKey)
    {
        $this->serviceKey = $serviceKey;
        $this->setServiceKey($serviceKey);
    }

    /**
     * @param bool $testing Whether testing is on or off
     */
    public function setTesting($testing)
    {
        $this->testing = $testing;
    }

    /**
     * Whether testing is on or off
     * @return bool True if test mode is on
     */
    public function getTesting()
    {
        return $this->testing;
    }

    /**
     * The URL to POST to
     * @return string The URL
     */
    public function getActionUrl()
    {
        return $this->actionUrl;
    }

    /**
     * Set a field value
     *
     * @param string $key The field key
     * @param string|int|float|bool $value The value
     *
     * @return bool
     */
    public function setField($key, $value)
    {
        if (!isset($this->fields[$key])) {
            return false;
//            throw new \Exception("Field '{$key}' does not exist.");
        }

        $this->fields[$key] = $value;

        return true;
    }

    /**
     * Get a field value
     *
     * @param string $key The field key
     *
     * @return mixed The value
     */
    public function getField($key)
    {
        return isset($this->fields[$key]) ? $this->fields[$key] : null;
    }

    /**
     * Get the fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get the hidden HTML input fields
     *
     * @return string
     */
    public function getInputFields()
    {
        $form = "";
        foreach ($this->getFields() as $key => $value) {
            $form .= "<input type='hidden' name='{$key}' value='{$value}' />";
        }

        return $form;
    }

    /**
     * Get the HTML form to submit
     *
     * @param bool $withSubmit Whether to include a submit button
     * @param string $submitText The text to show on the button
     *
     * @return string
     */
    public function makeForm($withSubmit = true, $submitText = 'Pay Now')
    {
        $form = sprintf(
            '<form id="%s" name="form" method="POST" action="%s" target="_top">',
            'netcash-paynow-form',
            $this->getActionUrl()
        );
        $form .= $this->getInputFields();
        if ($withSubmit) {
            $form .= sprintf(
                '<input id="netcash-paynow-submit" name="submit" type="submit" value="%s" />',
                $submitText
            );
        }
        $form .= '</form>';
        return $form;
    }

    /**
     * Set the Service key
     *
     * @param string $key
     *
     * @throws ValidationException
     */
    public function setServiceKey($key)
    {
        // Validate
        if (!$key) {
            throw new ValidationException(FieldType::SERVICE_KEY, "Service key is invalid");
        }
        $this->setField(FieldType::SERVICE_KEY, $key);
    }

    /**
     * Set an extra field
     *
     * @param int $index The index (1, 2, or 3)
     * @param mixed $value The value to set
     *
     * @throws ValidationException
     */
    public function setExtraField($value, $index = 1)
    {
        // Validate
        if (!$index || $index < 1 || $index > 3) {
            throw new ValidationException(FieldType::EXTRA1, "Index {$index} does not exist");
        }

        switch ($index) {
            case 3:
                $this->setField(FieldType::EXTRA3, $value);
                break;
            case 2:
                $this->setField(FieldType::EXTRA2, $value);
                break;
            case 1:
                // fall through
            default:
                $this->setField(FieldType::EXTRA1, $value);
                break;
        }
    }


    /**
     * Set a unique id for the transaction
     *
     * @param string $orderId The unique id. Will generate one if not set
     *
     */
    public function setOrderId($orderId)
    {
        // Make sure that it is unique
        $uniqueId = $orderId . "__" . date("Ymds");
        $this->setField(FieldType::UNIQUE_ID, $uniqueId);
    }

    /**
     * Set a unique id for the transaction
     *
     * @param string $id The unique id. Will generate one if not set
     *
     * @deprecated
     */
    public function setUniqueId($id = "")
    {
        // Validate
        if (!$id) {
            $id = md5(uniqid(rand(), true));
        }
        $this->setField(FieldType::UNIQUE_ID, $id);
    }

    /**
     * Set a description for the transaction
     *
     * @param string $description The unique id. Will generate one if not set
     *
     */
    public function setDescription($description = "")
    {
        $this->setField(FieldType::DESCRIPTION, $description);
    }

    /**
     * Whether to use the budgeting facility
     *
     * @param bool $useBudget True if yes. False if no
     *
     */
    public function setBudget($useBudget)
    {
        $this->setField(FieldType::BUDGET, $useBudget ? 'Y' : 'N');
    }

    /**
     * Set the parameters to return
     *
     * @param bool $string String with URL parameters
     *
     */
    public function setReturnString($string)
    {
        $this->setField(FieldType::RETURN_STRING, $string);
    }

    /**
     * Set the transaction amount in ZAR
     *
     * @param float $amount The amount
     *
     * @throws ValidationException
     */
    public function setAmount($amount)
    {
        $amount = floatval($amount);

        if (!$amount) {
            throw new ValidationException(FieldType::AMOUNT, "Amount cannot be 0.");
        }
        $this->setField(FieldType::AMOUNT, $amount);
    }

    /**
     * Set the software vendor key
     *
     * @param string $key
     *
     * @throws ValidationException
     */
    public function setVendorKey($key)
    {
        // Validate
        if (!$key) {
            throw new ValidationException(FieldType::SOFTWARE_VENDOR_KEY, "Vendor key is invalid");
        }
        $this->setField(FieldType::SOFTWARE_VENDOR_KEY, $key);
    }

    /**
     * Set the email address
     *
     * @param string $email An email address
     *
     * @throws ValidationException
     */
    public function setEmail($email)
    {
        // Validate
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException(FieldType::EMAIL, "Email is invalid");
        }
        $this->setField(FieldType::EMAIL, $email);
    }

    /**
     * Set the cellphone number
     *
     * @param string $cellphone An email address
     *
     * @throws ValidationException
     */
    public function setCellphone($cellphone)
    {
        // Remove special characters
        $cellphone = str_replace([ " ", "+", "-", "(", ")" ], "", trim($cellphone));
        // Remove "27"
        $formattedPhone = substr($cellphone, 0, 2) == '27' ? ("0" . substr($cellphone, 2) ) : $cellphone;

        // Validate
        if (!$formattedPhone || strlen($formattedPhone) !== 10 || substr($formattedPhone, 0, 1) !== "0") {
            throw new ValidationException("Cellphone number ({$cellphone}) is invalid", "Invalid format.");
        }
        $this->setField(FieldType::CELLPHONE, $formattedPhone);
    }

    /**
     * Set whether to return the card values or not
     *
     * @param bool $return Whether to return the card values or not
     *
     */
    public function setReturnCardDetail($return = false)
    {
        $this->setField(FieldType::RETURN_CARD_DETAIL, ((bool) $return ? 1 : 0));
    }

    /**
     * Set a card token to charge
     *
     * @param string $token A previously returned card token
     *
     */
    public function setCardToken($token)
    {
        $this->setField(FieldType::CARD_TOKEN, $token);
    }

    /**
     * Set whether this is a subscription
     *
     * @param bool $is_subscription Whether this is a subscription
     *
     */
    public function setIsSubscription($is_subscription = true)
    {
        $this->setField(FieldType::SUBSCRIPTION_IS_SUBSCRIPTION, ((bool) $is_subscription ? 1 : 0));
        if ((bool) $is_subscription) {
            // Has to be set for subscription to work
            $this->setField(FieldType::RETURN_CARD_DETAIL, 1);
        }
    }

    /**
     * Set the subscription cycle. I.e., how may times to bill
     *
     * @param int $times_to_bill How may times to bill
     *
     * @throws ValidationException
     */
    public function setSubscriptionCycle($times_to_bill)
    {
        if (intval($times_to_bill) < 0) {
            throw new ValidationException(
                FieldType::SUBSCRIPTION_CYCLE,
                "Invalid subscription cycle value '{$times_to_bill}'"
            );
        }
        $this->setField(FieldType::SUBSCRIPTION_CYCLE, (int) $times_to_bill);
    }

    /**
     * Set the subscription frequency. E.g., Monthly, weekly, etc.
     *
     * @param int|string $frequency The frequency. One of SubscriptionCycle::getConstants()
     *
     * @throws ValidationException|\ReflectionException
     */
    public function setSubscriptionFrequency($frequency)
    {
        if (SubscriptionFrequency::isValidValue($frequency)) {
            // Go on constant value. E.g., 1, 2, 3
            $this->setField(FieldType::SUBSCRIPTION_FREQUENCY, $frequency);
        } elseif (SubscriptionFrequency::isValidName($frequency)) {
            // Go on constant name. E.g., 'monthly', 'weekly', etc.
//            $frequency_const = strtoupper($frequency);
            $this->setField(FieldType::SUBSCRIPTION_FREQUENCY, SubscriptionFrequency::getConstantValue($frequency));
        } else {
            throw new ValidationException(
                FieldType::SUBSCRIPTION_FREQUENCY,
                "Invalid subscription frequency value '{$frequency}'"
            );
        }
    }

    /**
     * Set the subscription start date.
     *
     * @param \DateTime|string $date The date. If it is a string, will use strtotime() to parse
     *
     * @throws ValidationException
     */
    public function setSubscriptionStartDate($date)
    {
        if (is_string($date)) {
            // Parse the date string
            $date_ts = strtotime($date);
            $date = date_create_from_format('U', $date_ts);
            $date->setTime(0, 0, 0);
        }

        if ($date instanceof \DateTime) {
            $now = new \DateTime();
            $now->setTime(0, 0, 0);
            if ($date < $now) {
                throw new ValidationException(
                    FieldType::SUBSCRIPTION_START_DATE,
                    "Start date must be in the future " . $date->format('Y-m-d')
                );
            }

            $this->setField(FieldType::SUBSCRIPTION_START_DATE, $date->format('Y-m-d'));
        } else {
            throw new ValidationException(
                FieldType::SUBSCRIPTION_START_DATE,
                "Invalid subscription start date value " . (string) $date
            );
        }
    }

    /**
     * Set the recurring subscription amount.
     *
     * @param int|float $amount The amount to charge
     *
     * @throws ValidationException
     */
    public function setSubscriptionAmount($amount)
    {
        if (floatval($amount) < 0) {
            throw new ValidationException(
                FieldType::SUBSCRIPTION_RECURRING_AMOUNT,
                "Invalid subscription recurring amount '{$amount}'"
            );
        }

        $this->setField(FieldType::SUBSCRIPTION_RECURRING_AMOUNT, $amount);
    }


    /**
     * A wrapper to quickly create a subscription request.
     *
     * @param float|int         $recurring_amount The amount to charge
     * @param int|string        $frequency The frequency. One of SubscriptionCycle::getConstants()
     * @param \DateTime|string  $start_date The date to start
     * @param int $cycle        $cycle How many times to charge. Default = 999
     *
     * @throws ValidationException|\ReflectionException
     */
    public function createSubscription($recurring_amount, $frequency, $start_date, $cycle = 999)
    {
        $this->setIsSubscription(true);

        $this->setSubscriptionCycle($cycle);
        $this->setSubscriptionFrequency($frequency);
        $this->setSubscriptionStartDate($start_date);
        $this->setSubscriptionAmount($recurring_amount);
    }

    /**
     * A wrapper to quickly create a subscription request with adhoc amount.
     *
     * @param float|int         $adhoc_amount The once-off/adhoc amount
     * @param float|int         $recurring_amount The amount to charge
     * @param int|string        $frequency The frequency. One of SubscriptionCycle::getConstants()
     * @param \DateTime|string  $start_date The date to start
     * @param int $cycle        $cycle How many times to charge. Default = 999
     *
     * @throws ValidationException|\ReflectionException
     */
    public function createAdHocSubscription($adhoc_amount, $recurring_amount, $frequency, $start_date, $cycle = 999)
    {
        $this->setIsSubscription(true);

        if ($adhoc_amount) {
            $this->setAmount($adhoc_amount);
        }
        $this->createSubscription($recurring_amount, $frequency, $start_date, $cycle);
    }
}
