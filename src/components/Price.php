<?php

namespace sadi01\postecommerce\components;

use Yii;
use yii\base\Component;

/**
 *
 * @property int $shop_id
 * @property int $weight
 * @property int $service_type
 * @property int $destination_city_id
 * @property int $value
 * @property int $payment_type
 * @property boolean $non_standard_package
 * @property boolean $sms_service
 * @property boolean $is_collect_need
 */
class Price extends Component
{
    const SERVICE_TYPE_PISHTAZ = 1; 
    const SERVICE_TYPE_SEFARESHI = 2;
    
    const PAYMENT_TYPE_LOCAL = 0;
    const PAYMENT_TYPE_ONLINE = 1;
    const PAYMENT_TYPE_CARTI_TASHIMI = 2;
    const PAYMENT_TYPE_FREE = 88;
    
    public $shop_id;
    public $weight;
    public $service_type;
    public $destination_city_id;
    public $value;
    public $payment_type;
    public $non_standard_package;
    public $sms_service;
    public $is_collect_need;

}