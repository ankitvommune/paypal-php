<?php

namespace Ankitvommune\PaypalPhp;

/**
 * @package         PaypalPhp
 * @author          Ankit Verma <ankitv4087@gmail.com>
 * @link            https://github.com/ankitvommune/paypal-php
 * @website         http://www.example.com
 * @support         http://www.example.com/product/paypal-php/
 * @version         v1.0.0
 * @filesource
 */
class PaypalPhp
{
    protected const VERSION = '1.0';
    public const PAYPAL_CLIENT_ID_ENV_NAME = 'PAYPAL_CLIENT_ID'; //ClientId = 'WHATSAPP_CLOUD_API_FROM_PHONE_NUMBER';
    public const PAYPAL_CLIENT_SECRET_ENV_NAME = 'PAYPAL_CLIENT_SECRET';
    public const PAYPAL_TYPE_ENV_NAME = 'PAYPAL_TYPE'; //live or sandbox

    protected $version = '';
    protected $client_id = '';
    protected $secret_key = '';
    protected $type = '';
    protected $access_token = '';
    protected $URL_V1 = '';
    protected $URL_V2 = '';
    protected $OAUTH2_URL = '';

   
    /**
     * Constructor for initializing the PayPal client.
     *
     * @param array $config An optional array of configuration parameters
     */
    public function __construct($config = [])
    {
        $this->version = static::VERSION;
        $this->client_id = $config['paypal_client_id'] ?? $_ENV[static::PAYPAL_CLIENT_ID_ENV_NAME];
        $this->secret_key = $config['paypal_secret_key'] ?? $_ENV[static::PAYPAL_CLIENT_SECRET_ENV_NAME];
        $this->type = $config['paypal_type'] ?? $_ENV[static::PAYPAL_TYPE_ENV_NAME];
        $this->URL_V2 = $this->type == 'live' ? 'https://api-m.paypal.com/v2/' : 'https://api-m.sandbox.paypal.com/v2/';
        $this->URL_V1 = $this->type == 'live' ? 'https://api-m.paypal.com/v1/' : 'https://api-m.sandbox.paypal.com/v1/';
        $auth = $this->getAccessToken();
        $this->access_token = $auth->access_token;
    }

    /**
     * Retrieves an access token from the specified URL using client credentials.
     *
     * @return mixed
     */
    private function getAccessToken()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->URL_V1 . 'oauth2/token');
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($this->client_id . ':' . $this->secret_key),
        ]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        $result = json_decode(curl_exec($curl));
        curl_close($curl);

        if (isset($result->access_token)) {
            return $result;
        }
        return false;
    }

    /**
     * Create a Paypal order using the given post fields.
     *
     * @param array $post_feilds The post fields for creating the order
     * @return mixed The result of the order creation
     */
    public function CreateOrder($post_feilds)
    {
        if (@$post_feilds && @$this->access_token) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->URL_V2 . 'checkout/orders');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_feilds));

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer ' . $this->access_token;
            $headers[] = 'Paypal-Request-Id: ' . uniqid() . rand(100, 999);
            $headers[] = 'Prefer: return=representation';
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            return $result;
        }
        return false;
    }

    /**
     * Retrieves details of a PayPal order.
     *
     * @param datatype $order_id description
     * @return Some_Return_Value
     */
    public function OrderDetails($order_id)
    {
        if (@$order_id && @$this->access_token) {
            $curl = curl_init($this->URL_V2 . 'checkout/orders/' . $order_id);
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $this->access_token,
                'Accept: application/json',
            ));
            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            return $result;
        }
        return false;
    }

    /**
     * Paypal_Capture_Order_Payment function description.
     *
     * @param datatype $order_id description
     * @return mixed
     */
    public function CaptureOrderPayment($order_id)
    {
        if (@$order_id && @$this->access_token) {
            $return = [];
            $order = $this->OrderDetails($order_id);
            if (@$order->id) {
                $reference_id = $order->purchase_units[0]->reference_id;
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $this->URL_V2 . "checkout/orders/$order_id/capture");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_ENCODING, '');
                curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');

                $headers = array();
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Authorization: Bearer ' . $this->access_token;
                $headers[] = 'Paypal-Request-Id: ' . $reference_id;
                $headers[] = 'Prefer: return=representation';
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                $result = json_decode(curl_exec($curl));
                curl_close($curl);
                $return['order'] = $result;
                if (@$result->id) {
                    if (@$result->purchase_units[0]->payments->captures[0]->id) {
                        $capture_id = $result->purchase_units[0]->payments->captures[0]->id;
                        $curl = curl_init();
                        curl_setopt($curl, CURLOPT_URL, $this->URL_V2 . "payments/captures/$capture_id");
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($curl, CURLOPT_ENCODING, '');
                        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
                        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');

                        $headers = array();
                        $headers[] = 'Content-Type: application/json';
                        $headers[] = 'Authorization: Bearer ' . $this->access_token;
                        $headers[] = 'Paypal-Request-Id: ' . $reference_id;
                        $headers[] = 'Prefer: return=representation';
                        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                        $payments = json_decode(curl_exec($curl));
                        curl_close($curl);
                        $return['payment'] = $payments;
                    }
                }
            }
            return $return;
        }
        return false;
    }

    /**
     * Create a product using Paypal API.
     *
     * @param array $post_feilds The fields to be posted for creating the product
     * @return string The ID of the created product, or false if the creation fails
     */
    public function CreateProduct($post_feilds)
    {
        if (@$post_feilds && @$this->access_token) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->URL_V1 . 'catalogs/products');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_feilds));

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer ' . $this->access_token;
            $headers[] = 'Paypal-Request-Id: ' . uniqid() . rand(100, 999);
            $headers[] = 'Prefer: return=representation';
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            if (@$result->id) {
                return $result->id;
            }
        }
        return false;
    }

    /**
     * Paypal_Create_Plan function creates a new plan in Paypal billing.
     *
     * @param array $post_feilds The fields required to create the plan
     * @return string The ID of the newly created plan
     */
    public function CreatePlan($post_feilds)
    {
        if (@$post_feilds && @$this->access_token) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->URL_V1 . 'billing/plans');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_feilds));

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer ' . $this->access_token;
            $headers[] = 'Paypal-Request-Id: ' . uniqid() . rand(100, 999);
            $headers[] = 'Prefer: return=representation';
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            if (@$result->id) {
                return $result->id;
            }
            echo "<pre>";
            print_r($result);
        }
        return false;
    }

    /**
     * Paypal_Create_Subscription function creates a new subscription in PayPal.
     *
     * @param array $post_feilds The fields required to create the subscription
     * @return mixed The result of the subscription creation
     */
    public function CreateSubscription($post_feilds)
    {
        if (@$post_feilds && @$this->access_token) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->URL_V1 . 'billing/subscriptions');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_feilds));

            $headers = array();
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: Bearer ' . $this->access_token;
            $headers[] = 'Paypal-Request-Id: ' . uniqid() . rand(100, 999);
            $headers[] = 'Prefer: return=representation';
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            return $result;
        }
        return false;
    }

    /**
     * Retrieves details of a PayPal subscription.
     *
     * @param mixed $subscription_id The ID of the PayPal subscription
     * @return mixed The details of the subscription
     */
    public function SubscriptionDetails($subscription_id)
    {
        if (@$subscription_id && @$this->access_token) {
            $curl = curl_init($this->URL_V1 . 'billing/subscriptions/' . $subscription_id);
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $this->access_token,
                'Accept: application/json',
            ));
            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            return $result;
        }
        return false;
    }

    /**
     * A description of the entire PHP function.
     *
     * @param datatype $subscription_id description
     * @param datatype $end_time description
     * @throws Some_Exception_Class description of exception
     * @return Some_Return_Value
     */
    public function SubscriptionTransactions($subscription_id, $end_time = null)
    {
        if (@$subscription_id && @$this->access_token) {

            $subscription = $this->SubscriptionDetails($subscription_id);
            if ($end_time == null) {
                $end_time = date('Y-m-d', strtotime('+ 1 day')) . 'T00:00:00Z';
            }
            $create_time = date('Y-m-d', strtotime('- 2 day', strtotime($subscription->create_time))) . 'T00:00:00Z';

            $curl = curl_init($this->URL_V1 . "billing/subscriptions/$subscription_id/transactions?start_time=$create_time&end_time=$end_time");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_ENCODING, '');
            curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 0);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $this->access_token,
                'Accept: application/json',
            ));

            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            return $result;
        }

        return false;
    }

    /**
     * Get payment details from Paypal using the given capture ID.
     *
     * @param datatype $capture_id description
     * @return Some_Return_Value
     */
    public function GetPayment($capture_id)
    {
        if (@$capture_id && @$this->access_token) {
            $curl = curl_init($this->URL_V2 . 'payments/captures/' . $capture_id);
            curl_setopt($curl, CURLOPT_POST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Authorization: Bearer ' . $this->access_token,
                'Accept: application/json',
            ));

            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            return $result;
        }
        return false;
    }

    /**
     * A function to perform a refund using PayPal's API.
     *
     * @param datatype $payment_id description of the payment ID
     * @throws Some_Exception_Class description of exception
     * @return Some_Return_Value description of the return value
     */
    public function Refund($payment_id)
    {
        if (@$payment_id && @$this->access_token) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->URL_V2 . "payments/captures/$payment_id/refund",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Prefer: return=representation',
                    'Authorization: Bearer ' . $this->access_token,
                ),
            ));
            $result = json_decode(curl_exec($curl));
            curl_close($curl);
            return $result;
        }
        return false;
    }

    /**
     * Paypal_Cancel_Subscription function description.
     *
     * @param datatype $subscription_id description
     * @return Some_Return_Value
     */
    public function CancelSubscription($subscription_id)
    {
        if (@$subscription_id && @$this->access_token) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->URL_V1 . "billing/subscriptions/$subscription_id/cancel",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                                        "reason": "Cancelled"
                                    }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->access_token,
                ),
            ));
            $result = json_decode(curl_exec($curl));
            $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($status_code == 204) {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * A function to capture a Paypal payment.
     *
     * @param $result
     * @return mixed
     */
    public function PaymentCapture($result)
    {
        $amount = $result->amount;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->URL_V2 . 'payments/' . $result->id . '/capture');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "amount=" . $amount);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $this->client_id . ":" . $this->secret_key);
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = json_decode(curl_exec($ch));
        if (@$result->status) {
            return $result->status;
        } elseif (@$result->error->description == 'This payment has already been captured') {
            return 'captured';
        } else {
            false;
        }
    }

    function test()
    {
        return $this->version;
    }
}
