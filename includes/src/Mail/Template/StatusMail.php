<?php declare(strict_types=1);

namespace JTL\Mail\Template;

use JTL\Smarty\JTLSmarty;

/**
 * Class StatusMail
 * @package JTL\Mail\Template
 */
class StatusMail extends AbstractTemplate
{
    protected $id = \MAILTEMPLATE_STATUSEMAIL;

    /**
     * @inheritdoc
     */
    public function preRender(JTLSmarty $smarty, $data): void
    {
        parent::preRender($smarty, $data);
        if ($data === null) {
            return;
        }
        $model = $this->getModel();
        if ($model !== null && $model->getSubject() === 'Status Email') {
            foreach ($model->getSubjects() as $langID => $subject) {
                $model->setSubject($data->tfirma->cName . ' ' . $data->cIntervall, $langID);
            }
        }
        if (isset($data->interval)) {
            $smarty->assign('interval', $data->interval)
                ->assign('intervalLoc', $data->cIntervall);
        }
        $smarty->assign('oMailObjekt', $data);
    }
}
