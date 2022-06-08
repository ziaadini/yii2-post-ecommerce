<?php

namespace sadi01\postecommerce\components;

use Yii;
use yii\base\Component;

/**
 *
 * @property int $id
 * @property int $city_id
 * @property string $username
 * @property string $name
 * @property string $phone
 * @property string $mobile
 * @property string $email
 * @property string $website_url
 * @property string $manager_name
 * @property string $manager_family
 * @property string $manager_national_id
 * @property string $manager_national_id_serial
 * @property string $manager_birth_date
 * @property string $receipt_number
 * @property string $receipt_date
 * @property string $start_date
 * @property string $end_date
 * @property string $account_number
 * @property string $account_iban
 * @property string $account_branch_name
 * @property int $postal_code
 * @property int $need_to_collect
 */
class Shop extends Component
{
    const STATUS_ACTIVE = 0;
    const STATUS_SUSPEND = 1;
    const STATUS_EXPIRED = 2;
    const STATUS_SUSPEND_EXPIRED = 3;
    const STATUS_INACTIVE = 4;
    const STATUS_WAITING_FOR_CONFIRM = 20;
    
    const NEED_TO_COLLECT_YES = 0;
    const NEED_TO_COLLECT_NO = 1;

    public $id;
    public $city_id;
    public $username;
    public $name;
    public $phone;
    public $mobile;
    public $email;
    public $postal_code;
    public $website_url;
    public $manager_name;
    public $manager_family;
    public $manager_birth_date;
    public $manager_national_id;
    public $manager_national_id_serial;
    public $receipt_date;
    public $start_date;
    public $end_date;
    public $receipt_number;
    public $account_number;
    public $account_iban;
    public $account_branch_name;
    public $need_to_collect;

}