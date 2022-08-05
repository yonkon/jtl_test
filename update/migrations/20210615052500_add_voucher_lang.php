<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210615052500
 */
class Migration_20210615052500 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add voucher lang';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->setLocalization('ger', 'productDetails', 'voucherFlexPlaceholder', 'Gutscheinwert in %s');
        $this->setLocalization('eng', 'productDetails', 'voucherFlexPlaceholder', 'Voucher value in %s');
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function down()
    {
        $this->removeLocalization('voucherFlexPlaceholder', 'productDetails');
    }
}
