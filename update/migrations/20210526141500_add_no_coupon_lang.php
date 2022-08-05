<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210526141500
 */
class Migration_20210526141500 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add no coupon lang';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->setLocalization('ger', 'checkout', 'couponUnavailable', 'Für den aktuellen Inhalt des Warenkorbs ' .
            'existiert kein verfügbarer Coupon.');
        $this->setLocalization('eng', 'checkout', 'couponUnavailable', 'No coupon available for current basket.');
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->removeLocalization('couponUnavailable', 'checkout');
    }
}
