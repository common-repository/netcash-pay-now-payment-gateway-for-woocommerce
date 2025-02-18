<?php
namespace Netcash\PayNow;

/**
 * A helper class to interact with the Sage validation API
 * Class PayNowValidator
 * @package Netcash
 */
class KeysValidator
{

    const SERVICE_ID_PAYNOW = 14;
    const SERVICE_ID_CREDITOR_PAYMENTS = 2;
    const SERVICE_ID_RISK_REPORTS = 3;
    const SERVICE_ID_ACCOUNT_SERVICE = 5;
    const SERVICE_ID_SALARY_PAYMENTS = 10;

    const ACCOUNT_TYPE_CURRENT_CHECKING = 1;
    const ACCOUNT_TYPE_SAVINGS = 2;
    const ACCOUNT_TYPE_TRANSMISSION = 3;

    protected $debugging = false;
    // Default vendor key
    protected $vendorKey = '7f7a86f8-5642-4595-8824-aa837fc584f2';

    public function __construct()
    {
    }

    /**
     * Set the Software Vendor Key
     *
     * @param string $key The Software Vendor Key to use
     */
    public function setVendorKey($key)
    {
        $this->vendorKey = $key;
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

    private function soapInit($url = null)
    {
        $soap = new \SoapClient(
            $url ? $url : 'https://ws.netcash.co.za/niws/niws_validation.svc?wsdl',
            array(
                "trace"        => 1,
                'soap_version' => SOAP_1_1,
            )
        );

        return $soap;
    }

    /**
     * Get the Netcash host to use for the API
     *
     * @param string $which For authentication, use 'auth'
     *
     * @return string The host
     */
    private function getHost($which = '')
    {
        switch ($which) {
            case 'auth':
                $host = "https://mobi.netcash.co.za/";
                // authenticate.aspx?key={NetcashUsername}&message={Encrypted xml message}
                if ($this->debugging) {
                    $host = "http://mobi.staging.netcash.co.za:7999/";
                    // authenticate.aspx?key={NetcashUsername}&message={Encrypted xml message}
                }
                break;

            default:
                $host = "https://ws.netcash.co.za/niws/";
                if ($this->debugging) {
                    $host = "https://ws.netcash.co.za/niws/";
                }
                break;
        }

        return $host;
    }

    /**
     * Validates an array of service keys
     * Docs: https://www.netcash.co.za/netcash/partners_developers-technical_documents-sage_connect.asp
     *       https://ws.netcash.co.za/niws/niws_partner.svc
     *
     * @param string $merchant_account The merchant account
     * @param array $keys The keys. ServiceId as key and the ServiceKey as the value
     *
     * @return array An array of boolean or strings. With the Service Key as the key and bool TRUE if success.
     *               An error string if not successful
     */
    public function validateServiceKeys($merchant_account, $keys = array())
    {
        if (! is_array($keys) || empty($keys)) {
            return false;
        }

        $service_info_array = array();
        $keys_count         = 0;
        foreach ($keys as $id => $key) {
            $service_info_array[] = [
                /*
                 * Service Ids:
                    1   Dated debit orders
                    2   Creditor payments
                    3   Risk reports
                    5   Account service
                    8   NAEDO
                    10  Salary payments
                    11  Cashnet
                    12  Sage Pastel Payroll
                    13  Forex
                    14  Pay Now
                 */
                "ServiceId"  => $id,
                "ServiceKey" => $key,
            ];
            $keys_count ++;
        }

        $xml_arr = [
            "SoftwareVendorKey" => $this->vendorKey,
            "MerchantAccount"   => $merchant_account,
            "ServiceInfoList"   => $service_info_array
        ];

        $soap    = new \SoapClient(
            'https://ws.netcash.co.za/niws/niws_partner.svc?wsdl',
            array(
                "trace"        => 1,
                'soap_version' => SOAP_1_2,
            )
        );
        $headers = array(
            new \SoapHeader(
                'http://www.w3.org/2005/08/addressing',
                'Action',
                'http://tempuri.org/INIWS_Partner/ValidateServiceKey'
            ),
            new \SOAPHeader(
                'http://www.w3.org/2005/08/addressing',
                'To',
                'https://ws.netcash.co.za/NIWS/NIWS_Partner.svc'
            )
        );

        // set the headers of Soap Client.
        $soap->__setSoapHeaders($headers);
        $result = $soap->ValidateServiceKey([ 'request' => $xml_arr ]);

        // See status codes here:
        // https://www.netcash.co.za/netcash/partners_developers-technical_documents-sage_connect.asp
        if ($result && isset($result->ValidateServiceKeyResult)) {
            $accountStatus = $result->ValidateServiceKeyResult->AccountStatus;

            // Continue only if the account is active
            if ($accountStatus !== '001') {
                foreach ($keys as $serviceId => $serviceKey) {
                    return array( $serviceKey => self::getAccountError($accountStatus) );
                }
            }

            if (count($keys) === 1) {
                // A single result

                // Check the key
                $service_info_result = $result->ValidateServiceKeyResult
                    ->ServiceInfo->ServiceInfoResponse
                    ->ServiceStatus;
                $key                 = $result->ValidateServiceKeyResult
                    ->ServiceInfo->ServiceInfoResponse
                    ->ServiceKey;

                if ($service_info_result === '001') {
                    return array( $key => true );
                } else {
                    return array( $key => self::getServiceKeyError($service_info_result) );
                }
            } else {
                // Loop through the keys
                $service_info_results = $result->ValidateServiceKeyResult
                    ->ServiceInfo->ServiceInfoResponse;

                $return = array();

                foreach ($service_info_results as $result) {
                    if ($result->ServiceStatus === '001') {
                        $return[ $result->ServiceKey ] = true;
                    } else {
                        $return[ $result->ServiceKey ] = self::getServiceKeyError($result->ServiceStatus);
                    }
                }

                return $return;
            }
        }

        $sid = $service_info_array[0];

        return [ $sid['ServiceId'] => 'Could not validate the service key.' ];
    }

    /**
     * Validates a paynow service key
     *
     * @param string $account_number [description]
     * @param string $service_key [description]
     *
     * @return bool|string True on success. An error message on failure
     */
    public function validatePaynowServiceKey($account_number, $service_key)
    {
        $result = $this->validateServiceKeys($account_number, [
            self::SERVICE_ID_PAYNOW => $service_key
        ]);

        if (! is_bool($result[ $service_key ]) || $result[ $service_key ] !== true) {
            // An error occurred
            return $result[ $service_key ];
        }

        // Success
        return true;
    }

    public static function getServiceKeyErrors()
    {
        return array(
            '001' => 'Validated',
            '105' => 'No active service found for this Account Number / Service ID combination',
            '106' => 'No active service key found for this Account Number / Service ID / Service Key combination',
        );
    }

    public static function getServiceKeyError($key)
    {

        $errors = self::getServiceKeyErrors();

        if (array_key_exists($key, $errors)) {
            return $errors[ $key ];
        }

        return 'Unknown service key error.';
    }

    public static function getAccountErrors()
    {
        return array(
            '001' => 'Authenticated',
            '103' => 'No active partner found for this Software vendor key',
            '104' => 'No active client found for this Account number',
            '200' => 'General service error – contact Netcash support',
            '201' => 'Account locked out for 10 minutes due to unsuccessful validation',
        );
    }

    public static function getAccountError($key)
    {
        $errors = self::getAccountErrors();
        if (array_key_exists($key, $errors)) {
            return $errors[ $key ];
        }

        return 'Unknown account error.';
    }
}
