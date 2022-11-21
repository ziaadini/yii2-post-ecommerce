<?php

namespace sadi01\postecommerce\components;

use Yii;
use yii\base\Component;

/**
 *
 * @property string $order_id
 * @property Price $price
 * @property string $customer_nid
 * @property string $customer_name
 * @property string $customer_family
 * @property string $customer_mobile
 * @property string $customer_email
 * @property string $customer_postal_code
 * @property string $customer_address
 * @property string $content
 */
class Packet extends Component
{
    const RESULT_SUCCESS = 0;
    const RESULT_NO_BARCODE_OWNER = 1;
    const RESULT_IMPOSSIBLE = 2;
    const RESULT_NOT_FOUND = 3;

    public $order_id;
    public $price;
    public $customer_nid;
    public $customer_name;
    public $customer_family;
    public $customer_mobile;
    public $customer_email;
    public $customer_postal_code;
    public $customer_address;
    public $content;
    public $parcel_category_id;

    public static function itemAlias($type, $code = NULL)
    {
        $_items = [
            'Result' => [
                self::RESULT_SUCCESS => Yii::t("postService", "The action was successful"),
                self::RESULT_NO_BARCODE_OWNER => Yii::t("postService", "The barcode does not belong to the company"),
                self::RESULT_IMPOSSIBLE => Yii::t("postService", "It is not possible to perform this action"),
                self::RESULT_NOT_FOUND => Yii::t("postService", "No barcode information"),
            ],
        ];

        if (isset($code))
            return isset($_items[$type][$code]) ? $_items[$type][$code] : false;
        else
            return isset($_items[$type]) ? $_items[$type] : false;
    }
}