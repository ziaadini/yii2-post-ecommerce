<?php

namespace sadi01\postecommerce\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

/**@property Client $client */
class PostService extends Component
{
    public $username;
    public $password;
    public $secretKey;
    public $debug = false;
    public $baseUrl = 'https://ecommerceapi.post.ir';
    private $version = 'api';
    private $client;

    const API_PATH = "%s://%s/%s/%s/%s%s";

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (!$this->username) {
            throw new InvalidConfigException('POST SERVICE API username is required!');
        }
        if (!$this->password) {
            throw new InvalidConfigException('POST SERVICE API password is required!');
        }
        if (!$this->secretKey) {
            throw new InvalidConfigException('POST SERVICE API secretKey is required!');
        }

        $this->client = $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
    }

    protected function get_path($method = "AddRequest", $base = "company", $params = [])
    {
        $baseUrl = parse_url($this->baseUrl);
        return sprintf(self::API_PATH, ($baseUrl['scheme'] ?? ''), ($baseUrl['host'] ?? ''), $this->version, $base, $method, $params ? ('?' . http_build_query($params)) : '');
    }

    /**
     * @param $url
     * @param null $data
     * @param array $headers
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute($url, $data = null, $multipart = null, $headers = [], $verb = "POST")
    {
        $headers = ArrayHelper::merge([
            'Accept' => 'application/json',
            'content-type' => 'application/json',
            'charset' => 'utf-8',
            'username' => $this->username,
            'password' => $this->password,
            'apikey' => md5($this->username . $this->password . $this->secretKey),
            'debug' => $this->debug,
        ], $headers);

        try {
            $response = $this->client->createRequest()
                ->setOptions([
                    CURLOPT_SSL_CIPHER_LIST => "DEFAULT:!DH"
                ])
                ->setFormat(Client::FORMAT_JSON)
                ->addHeaders($headers)
                ->setMethod($verb)
                ->setUrl($url)
                ->setData($data)
                ->send();

            if (!$response->isOk) {
                Yii::error(($message = Yii::t('postService', json_decode($response->content)->Message)), 'PostService-Exception-Details');
                return [
                    'status' => $response->getStatusCode(),
                    'body' => $message,
                ];
            }
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), 'PostService-Exception');
            Yii::error($e->getTraceAsString(), 'PostService-Exception-Details');
            return [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
            ];
        }

        return [
            'status' => $response->getStatusCode(),
            'body' => json_decode($response->content),
        ];
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getStates()
    {
        $path = $this->get_path('GetStates', 'company');

        return $this->execute($path);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCities($state_id)
    {
        $params = [
            'stateid' => $state_id
        ];

        $path = $this->get_path('GetStateCities', 'company', $params);

        return $this->execute($path);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getShops()
    {
        $path = $this->get_path('GetShopList', 'company');

        return $this->execute($path);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getShopsDetails()
    {
        $path = $this->get_path('GetShopFullList', 'company');

        return $this->execute($path);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createShop(Shop $shop)
    {
        $path = $this->get_path('RegisterShop', 'company');
        $data = [
            'Shopname' => $shop->name,
            'Phone' => $shop->phone,
            'PostalCode' => $shop->postal_code,
            'ManagerNationalID' => $shop->manager_national_id,
            'ManagerNationalIDSerial' => $shop->manager_national_id_serial,
            'ManagerBirthDate' => $shop->manager_birth_date,
            'Mobile' => $shop->mobile,
            'Email' => $shop->email,
            'StartDate' => $shop->start_date,
            'EndDate' => $shop->end_date,
            'WebSiteURL' => $shop->website_url,
            'CityID' => $shop->city_id,
            'ShopUsername' => $shop->username,
            'NeedToCollect' => $shop->need_to_collect
        ];

        return $this->execute($path, $data);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updateShop(Shop $shop)
    {
        $path = $this->get_path('RegisterShop', 'company');
        $data = [
            'ShopID' => $shop->id,
            'Shopname' => $shop->name,
            'Phone' => $shop->phone,
            'PostalCode' => $shop->postal_code,
            'ManagerNationalID' => $shop->manager_national_id,
            'ManagerNationalIDSerial' => $shop->manager_national_id_serial,
            'ManagerBirthDate' => $shop->manager_birth_date,
            'Mobile' => $shop->mobile,
            'Email' => $shop->email,
            'StartDate' => $shop->start_date,
            'EndDate' => $shop->end_date,
            'WebSiteURL' => $shop->website_url,
            'CityID' => $shop->city_id,
            'ShopUsername' => $shop->username,
            'NeedToCollect' => $shop->need_to_collect,
        ];

        return $this->execute($path, $data);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPrice(Price $price)
    {
        $data = [
            'ShopID' => $price->shop_id,
            'Weight' => $price->weight,
            'ParcelServiceType' => $price->service_type,
            'DestinationCityID' => $price->destination_city_id,
            'ParcelValue' => $price->value,
            'PaymentType' => $price->payment_type,
            'NonStandardPackage' => $price->non_standard_package,
            'SMSService' => $price->sms_service,
            'IsCollectNeed' => $price->is_collect_need
        ];
        $path = $this->get_path('GetPrice', 'company');

        return $this->execute($path, $data);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBarcodes($shop_id, $report_date)
    {
        $params = [
            'ShopID' => $shop_id,
            'ReportDate' => $report_date
        ];

        $path = $this->get_path('GetParcelList', 'company', $params);

        return $this->execute($path);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function barcodeTrace($barcode)
    {
        $params = [
            'barcode' => $barcode
        ];

        $path = $this->get_path('GetParcelTrace', 'company', $params);

        return $this->execute($path);
    }

    /**
     * @param string $barcode
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function barcodeInfo($barcode)
    {
        $params = [
            'barcode' => $barcode
        ];

        $path = $this->get_path('GetParcelTrack', 'company', $params);

        return $this->execute($path);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createPacket(Packet $packet)
    {
        $path = $this->get_path('AddRequest', 'company');
        $data = [
            'Price' => [
                'ShopID' => $packet->price->shop_id,
                'Weight' => $packet->price->weight,
                'ParcelServiceType' => $packet->price->service_type,
                'DestinationCityID' => $packet->price->destination_city_id,
                'ParcelValue' => $packet->price->value,
                'PaymentType' => $packet->price->payment_type,
                'NonStandardPackage' => $packet->price->non_standard_package,
                'SMSService' => $packet->price->sms_service,
                'IsCollectNeed' => $packet->price->is_collect_need
            ],
            'CustomerNID' => $packet->customer_nid,
            'CustomerName' => $packet->customer_name,
            'CustomerFamily' => $packet->customer_family,
            'CustomerMobile' => $packet->customer_mobile,
            'CustomerEmail' => $packet->customer_email,
            'CustomerPostalCode' => $packet->customer_postal_code,
            'CustomerAddress' => $packet->customer_address,
            'ParcelContent' => $packet->content
        ];

        return $this->execute($path, $data);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updatePacket(Packet $packet)
    {
        $path = $this->get_path('EditRequest', 'company');
        $data = [
            'Barcode' => $packet->barcode,
            'ShopID' => $packet->shop_id,
            'CustomerNID' => $packet->customer_nid,
            'CustomerName' => $packet->customer_name,
            'CustomerFamily' => $packet->customer_family,
            'CustomerMobile' => $packet->customer_mobile,
            'CustomerEmail' => $packet->customer_email,
            'CustomerPostalCode' => $packet->customer_postal_code,
            'CustomerAddress' => $packet->customer_address,
            'ParcelContent' => $packet->content
        ];

        return $this->execute($path, $data);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deletePackets($barcodes)
    {
        $path = $this->get_path('DeleteRequests', 'company');
        foreach ($barcodes as $barcode) {
            $data[] = [
                'Barcode' => $barcode
            ];
        }

        return $this->execute($path, $data);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setReadyToCollectPackets($barcodes)
    {
        $path = $this->get_path('ReadyToCollectRequests', 'company');
        foreach ($barcodes as $barcode) {
            $data[] = [
                'Barcode' => $barcode
            ];
        }

        $response = $this->execute($path, $data);

        foreach ($response['body'] as $result) {
            $result->Result = Packet::itemAlias('Result', $result->Result);
        }

        return $response;
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setsuspendPackets($barcodes)
    {
        $path = $this->get_path('SuspendRequests', 'company');
        foreach ($barcodes as $barcode) {
            $data[] = [
                'Barcode' => $barcode
            ];
        }

        return $this->execute($path, $data);
    }

    /**
     * @param Packing $pack
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBarcode(Packing $pack)
    {
        $path = $this->get_path();
        $dispatch = $pack->ownerModel instanceof Dispatch ? $pack->ownerModel : $pack->ownerModel->backRequest->dispatch;

        $data = [
            'typecode' => 11,
            'parceltype' => "VANGUARD_BOX", // 2
            'sourcecode' => $dispatch->warehouseParent->destination->city->post_code,
            'destcode' => $pack->deliverInfo['city']->post_code,
            'sendername' => SettingsAccount::get('POST_SERVICE_SENDER_TITLE'),
            'senderpostalcode' => SettingsAccount::get('POST_SERVICE_SENDER_POSTAL_CODE'),
            'receivername' => $pack->deliverInfo['deliveryName'],
            'receiverpostalcode' => $pack->deliverInfo['zipcode'],
            'weight' => $pack->weight ?: 200,
            'postalcostcategoryid' => "NO_STAMP",
            'postalcosttypeflag' => "CASH_M2",
            'relationalkey' => $pack->id,
            'senderid' => SettingsAccount::get('POST_SERVICE_SENDER_NATIONAL_CODE'),
            'receiverid' => $dispatch->order->user->nationalCode ?: null,
            'sendermobile' => SettingsAccount::get('POST_SERVICE_SENDER_MOBILE'),
            'receivermobile' => $pack->deliverInfo['deliveryMobile'],
            'senderaddress' => SettingsAccount::get('POST_SERVICE_SENDER_ADDRESS'),
            'receiveraddress' => $pack->deliverInfo['fullAddress'],
            'insurancetype' => "REGULAR",
            'insuranceamount' => 8000,
            'spsreceivertimetype' => "REGULAR",
            'spsparcletype' => "REGULAR",
            'tlsservicetype' => "WORKING_HOURS",
            'tworeceiptant' => false,
            'electroreceiptant' => true,
            'iscot' => false,
            'smsservice' => false,
            'isnonstandard' => false,
            'sendplacetype' => "OTHER_CITIES",
            'Contractorportion' => 0,
        ];

        return $this->execute($path, $data);
    }

}

?>