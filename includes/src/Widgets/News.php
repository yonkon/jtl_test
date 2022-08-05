<?php declare(strict_types=1);

namespace JTL\Widgets;

/**
 * Class News
 * @package JTL\Widgets
 */
class News extends AbstractWidget
{
    /**
     *
     */
    public function init()
    {
        $this->setPermission('DASHBOARD_ALL');
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->oSmarty->fetch('tpl_inc/widgets/news.tpl');
    }
}
