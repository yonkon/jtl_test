<?php

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210129122800
 */
class Migration_20210129122800 extends Migration implements IMigration
{
    protected $author      = 'mh';
    protected $description = 'Fix video lang tag';

    /**
     * @inheritDoc
     */
    public function up()
    {
        $this->setLocalization('ger', 'errorMessages', 'videoTagNotSupported', 'Das HTML5 video-Tag wird von Ihrem Browser nicht unterstützt.');
        $this->setLocalization('eng', 'errorMessages', 'videoTagNotSupported', 'Your browser does not support the HTML5 video-tag.');
        $this->setLocalization('ger', 'errorMessages', 'audioTagNotSupported', 'Das HTML5 audio-Tag wird von Ihrem Browser nicht unterstützt.');
        $this->setLocalization('eng', 'errorMessages', 'audioTagNotSupported', 'Your browser does not support the HTML5 audio-tag.');
    }

    /**
     * @inheritDoc
     */
    public function down()
    {
        $this->setLocalization('ger', 'errorMessages', 'videoTagNotSupported', 'Das HTML5 <video> Tag wird von Ihrem Browser nicht unterstützt.');
        $this->setLocalization('eng', 'errorMessages', 'videoTagNotSupported', 'Your browser does not support the HTML5 <video> tag.');
        $this->setLocalization('ger', 'errorMessages', 'audioTagNotSupported', 'Das HTML5 <audio> Tag wird von Ihrem Browser nicht unterstützt.');
        $this->setLocalization('eng', 'errorMessages', 'audioTagNotSupported', 'Your browser does not support the HTML5 <audio> tag.');
    }
}
