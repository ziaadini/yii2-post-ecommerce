<?php

namespace sadi01\postecommerce\components;

use common\models\OauthAccessTokens;
use common\models\OauthClients;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\httpclient\Client;

/**@property Client $client */
class PostServiceRest extends Component
{
    public $oauthClient;
    public $debug = false;
    public $baseUrl = 'https://ecommrestapi.post.ir';
    private $version = 'api/v1';
    private $client;

    const API_PATH = "%s://%s/%s/%s/%s%s";

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        $this->oauthClient = OauthClients::findOne('MOBIT');

        $this->client = $client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
    }

    protected function get_path($method = "New", $base = "Parcel", $params = [])
    {
        $baseUrl = parse_url($this->baseUrl);
        return sprintf(self::API_PATH, ($baseUrl['scheme'] ?? ''), ($baseUrl['host'] ?? ''), $this->version, $base, $method, $params ? ('?' . http_build_query($params)) : '');
    }

    /**
     * @param integer $user_id
     * @param array|null $scopes
     * @param string $grant_type => 'client_credentials', 'authorization_code'
     * @param null|string $code (Required when $grant_type == 'authorization_code')
     * @param null|string $redirect_uri (Required when $grant_type == 'authorization_code')
     * @return null|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getToken($user_id, $scopes = [], $grant_type = "password", $code = null, $redirect_uri = null)
    {
        $scopes = is_array($scopes) ? implode(",", $scopes) : $scopes;
        $accessToken = OauthAccessTokens::find()->notExpire()->byUser($user_id)->byClientId($this->oauthClient->client_id)->byScope($scopes)->one();

        if (!$accessToken instanceof OauthAccessTokens) {
            $path = $this->get_path("Token", 'Users');
            $data = array(
                "grant_type" => $grant_type,
                "scopes" => $scopes,
                "code" => $code,
                "redirect_uri" => $redirect_uri,
                'username' => $this->oauthClient->client_id,
                'password' => $this->oauthClient->client_secret
            );

            $response = $this->execute(url: $path, data: $data, format: Client::FORMAT_URLENCODED);

            if ($response['status'] === '200') {
                $result = $response['body'];
                $accessToken = new OauthAccessTokens([
                    'access_token' => $result->access_token,
                    'client_id' => $this->oauthClient->client_id,
                    'user_id' => $user_id,
                    'expires' => date("Y-m-d H:i:s", (time() + $result->expires_in)),
                    'scope' => $scopes,
                    'normal_token_attempt' => 0
                ]);
                $accessToken->save();

                return $accessToken->access_token;
            }
        } else {
            return $accessToken->access_token;
        }

        return null;
    }

    /**
     * @param $url
     * @param null $data
     * @param array $headers
     * @return mixed
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function execute($url, $data = null, $multipart = null, $headers = [], $verb = "POST", $format = Client::FORMAT_JSON)
    {
        $headers = ArrayHelper::merge([
            'Accept' => 'application/json',
            'content-type' => 'application/json',
            'charset' => 'utf-8',
            'debug' => $this->debug,
        ], $headers);

        try {
            $response = $this->client->createRequest()
                ->setOptions([
                    CURLOPT_SSL_CIPHER_LIST => "DEFAULT:!DH"
                ])
                ->setFormat($format)
                ->addHeaders($headers)
                ->setMethod($verb)
                ->setUrl($url)
                ->setData($data)
                ->send();

            if (!$response->isOk) {
                $responseContent = json_decode($response->content);
                Yii::error(($message = Yii::t('postServiceRest', ($responseContent ? ($responseContent->ResMsg ?? ($responseContent->Message ?? '')) : ''))) . PHP_EOL . VarDumper::dumpAsString($response), 'PostServiceRest-Exception-Details');
                return [
                    'status' => $response->getStatusCode(),
                    'body' => $message,
                ];
            }
        } catch (\Exception $e) {
            Yii::error($e->getMessage() . PHP_EOL . $e->getTraceAsString(), 'PostServiceRest-Exception');
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
        $path = $this->get_path('Provinces', 'BaseInfo');

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        return $this->execute(url: $path, headers: $headers, verb: 'GET');
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCities($state_id)
    {
        $params = [
            'ProvinceID' => $state_id
        ];

        $path = $this->get_path('City', 'BaseInfo', $params);

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        return $this->execute(url: $path, headers: $headers, verb: 'GET');
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getShops()
    {
        $path = $this->get_path('List', 'Shop');

        $data = [
            'FromContractEndDate' => "",
            'ToContractEndDate' => "",
            'Name' => "",
            'Page' => 1,
            'PageSize' => 20
        ];

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        return $this->execute(url: $path, data: $data, headers: $headers);
    }

    /**
     * @propperty int $shop_id
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getShopsDetails(int $shop_id)
    {
        $params = ['shopID' => $shop_id];
        $path = $this->get_path('Info', 'Shop', $params);

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        return $this->execute(url: $path, headers: $headers, verb: 'GET');
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
            'ToCityID' => $price->destination_city_id,
            'Weight' => $price->weight,
            'ServiceTypeID' => $price->service_type,
            'ParcelValue' => $price->value,
            'PayTypeID' => $price->payment_type,
            'NonStandardPackage' => $price->non_standard_package,
            'SMSService' => $price->sms_service,
            'CollectNeed' => $price->is_collect_need
        ];
        $path = $this->get_path('Price', 'Parcel');

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        return $this->execute(url: $path, data: $data, headers: $headers);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getBarcodes($shop_id, $parcel_status, $from_date, $to_date, $page = 1, $page_size = 20)
    {
        $data = [
            'ShopID' => $shop_id,
            'ParcelStatusID' => $parcel_status,
            'FromDate' => $from_date,
            'ToDate' => $to_date,
            'Page' => $page,
            'PageSize' => $page_size,
        ];

        $path = $this->get_path('List', 'Parcel', $params);

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        return $this->execute(url: $path, data: $data, headers: $headers);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function barcodeTrace($barcode)
    {
        $params = [
            'parcelCode' => $barcode
        ];

        $path = $this->get_path('Track', 'Parcel', $params);

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        return $this->execute(url: $path, headers: $headers, verb: "GET");
    }

    /**
     * @param string $barcode
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function barcodeInfo($barcode)
    {
        $data = [
            $barcode
        ];

        $path = $this->get_path('Status', 'Parcel');

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        return $this->execute(url: $path, data: $data, headers: $headers);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createPacket(Packet $packet)
    {
        $path = $this->get_path('New', 'Parcel');
        $data = [
            'Price' => [
                'ShopID' => $packet->price->shop_id,
                'Weight' => $packet->price->weight,
                'ServiceTypeID' => $packet->price->service_type,
                'ToCityID' => $packet->price->destination_city_id,
                'ParcelValue' => $packet->price->value,
                'PayTypeID' => $packet->price->payment_type,
                'NonStandardPackage' => $packet->price->non_standard_package,
                'SMSService' => $packet->price->sms_service,
                'CollectNeed' => $packet->price->is_collect_need
            ],
            'ClientOrderID' => $packet->order_id,
            'CustomerNID' => $packet->customer_nid,
            'CustomerName' => $packet->customer_name,
            'CustomerFamily' => $packet->customer_family,
            'CustomerMobile' => $packet->customer_mobile,
            'CustomerEmail' => $packet->customer_email,
            'CustomerPostalCode' => $packet->customer_postal_code,
            'CustomerAddress' => $packet->customer_address,
            'ParcelContent' => $packet->content,
            'ParcelCategoryID' => $packet->parcel_category_id
        ];

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        return $this->execute(url: $path, data: $data, headers: $headers);
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function updatePacket(Packet $packet)
    {
        $path = $this->get_path('Edit', 'Parcel');
        $data = [
            'ParcelCode' => $packet->barcode,
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
        $path = $this->get_path('Delete', 'Parcel');
        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));
        $response = $this->execute(url: $path, data: $barcodes, headers: $headers);

        foreach ($response['body']?->Data ?? [] as $result) {
            $result->ResultCode = $result->Result;
            $result->Result = Packet::itemAlias('Result', $result->Result);
        }

        return $response;
    }

    /**
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setReadyToCollectPackets($barcodes)
    {
        $path = $this->get_path('ReadyToCollect', 'Parcel');

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        $response = $this->execute(url: $path, data: $barcodes, headers: $headers);

        foreach ($response['body']?->Data ?? [] as $result) {
            $result->ResultCode = $result->Result;
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
        $path = $this->get_path('Suspend', 'Parcel');

        $headers["Authorization"] = sprintf("Bearer %s", $this->getToken(15031));

        $response = $this->execute(url: $path, data: $barcodes, headers: $headers);

        foreach ($response['body'] as $result) {
            $result->ResultCode = $result->Result;
            $result->Result = Packet::itemAlias('Result', $result->Result);
        }

        return $response;
    }
}
?>