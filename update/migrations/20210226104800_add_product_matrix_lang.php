<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210226104800
 */
class Migration_20210226104800 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Add product matrix lang';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->setLocalization('ger', 'productDetails', 'productMatrixTitle', 'Warenkorbmatrix');
        $this->setLocalization('eng', 'productDetails', 'productMatrixTitle', 'Basket matrix');
        $this->setLocalization('ger', 'productDetails', 'productMatrixDesc', '');
        $this->setLocalization('eng', 'productDetails', 'productMatrixDesc', '');
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->removeLocalization('productMatrixTitle', 'productDetails');
        $this->removeLocalization('productMatrixDesc', 'productDetails');
    }
}
