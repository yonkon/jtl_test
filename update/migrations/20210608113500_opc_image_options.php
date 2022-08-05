<?php declare(strict_types=1);

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210608113500
 */
class Migration_20210608113500 extends Migration implements IMigration
{
    protected $author = 'dr';
    protected $description = 'Add OPC image size options';

    /**
     * @inheritdoc
     */
    public function up()
    {
        $this->setConfig(
            'bilder_opc_mini_breite',
            '480',
            CONF_BILDER,
            'OPC-Bilder Mini Breite',
            'number',
            330
        );
        $this->setConfig(
            'bilder_opc_mini_hoehe',
            '480',
            CONF_BILDER,
            'OPC-Bilder Mini Höhe',
            'number',
            331
        );
        $this->setConfig(
            'bilder_opc_klein_breite',
            '720',
            CONF_BILDER,
            'OPC-Bilder Klein Breite',
            'number',
            332
        );
        $this->setConfig(
            'bilder_opc_klein_hoehe',
            '720',
            CONF_BILDER,
            'OPC-Bilder Klein Höhe',
            'number',
            333
        );
        $this->setConfig(
            'bilder_opc_normal_breite',
            '1080',
            CONF_BILDER,
            'OPC-Bilder Normal Breite',
            'number',
            334
        );
        $this->setConfig(
            'bilder_opc_normal_hoehe',
            '1080',
            CONF_BILDER,
            'OPC-Bilder Normal Höhe',
            'number',
            335
        );
        $this->setConfig(
            'bilder_opc_gross_breite',
            '1440',
            CONF_BILDER,
            'OPC-Bilder Groß Breite',
            'number',
            336
        );
        $this->setConfig(
            'bilder_opc_gross_hoehe',
            '1440',
            CONF_BILDER,
            'OPC-Bilder Groß Höhe',
            'number',
            337
        );
    }

    /**
     * @inheritdoc
     */
    public function down()
    {
        $this->removeConfig('bilder_opc_mini_breite');
        $this->removeConfig('bilder_opc_mini_hoehe');
        $this->removeConfig('bilder_opc_klein_breite');
        $this->removeConfig('bilder_opc_klein_hoehe');
        $this->removeConfig('bilder_opc_normal_breite');
        $this->removeConfig('bilder_opc_normal_hoehe');
        $this->removeConfig('bilder_opc_gross_breite');
        $this->removeConfig('bilder_opc_gross_hoehe');
    }
}
