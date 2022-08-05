<?php declare(strict_types=1);

namespace JTL\Smarty;

use JTL\DB\DbInterface;

/**
 * Class MailSmarty
 * @package JTL\Smarty
 */
class MailSmarty extends JTLSmarty
{
    /**
     * @var DbInterface
     */
    protected $db;

    /**
     * MailSmarty constructor.
     * @param DbInterface $db
     * @param string      $context
     * @throws \SmartyException
     */
    public function __construct(DbInterface $db, string $context = ContextType::MAIL)
    {
        $this->db = $db;
        parent::__construct(true, $context);
        $this->registerResource('db', new SmartyResourceNiceDB($db, $context))
             ->registerPlugin(\Smarty::PLUGIN_FUNCTION, 'includeMailTemplate', [$this, 'includeMailTemplate'])
             ->registerPlugin(\Smarty::PLUGIN_MODIFIER, 'maskPrivate', [$this, 'maskPrivate'])
             ->setCompileDir(\PFAD_ROOT . \PFAD_COMPILEDIR)
             ->setTemplateDir(\PFAD_ROOT . \PFAD_EMAILTEMPLATES)
             ->setDebugging(false)
             ->setCaching(false);
        if ($context === ContextType::MAIL && \MAILTEMPLATE_USE_SECURITY) {
            $this->activateBackendSecurityMode();
        } elseif ($context === ContextType::NEWSLETTER && \NEWSLETTER_USE_SECURITY) {
            $this->activateBackendSecurityMode();
        }
    }

    /**
     * @param array     $params
     * @param JTLSmarty $smarty
     * @return string
     */
    public function includeMailTemplate($params, $smarty): string
    {
        if (!isset($params['template'], $params['type']) || $smarty->getTemplateVars('int_lang') === null) {
            return '';
        }
        $tpl = $this->db->select(
            'temailvorlage',
            'cDateiname',
            $params['template']
        );
        if (isset($tpl->kEmailvorlage) && $tpl->kEmailvorlage > 0) {
            $lang = $smarty->getTemplateVars('int_lang');
            $row  = $params['type'] === 'html' ? 'cContentHtml' : 'cContentText';
            $res  = $this->db->getSingleObject(
                'SELECT ' . $row . ' AS content
                    FROM temailvorlagesprache
                    WHERE kSprache = :lid
                 AND kEmailvorlage = :tid',
                ['lid' => $lang->kSprache, 'tid' => $tpl->kEmailvorlage]
            );
            if (isset($res->content)) {
                return $smarty->fetch('db:' . $params['type'] . '_' . $tpl->kEmailvorlage . '_' . $lang->kSprache);
            }
        }

        return '';
    }

    /**
     * @param string $str
     * @param int    $pre
     * @param int    $post
     * @param string $mask
     * @return string
     */
    public function maskPrivate(string $str, int $pre = 0, int $post = 4, string $mask = '****'): string
    {
        if (\mb_strlen($str) <= $pre + $post) {
            return $str;
        }

        return \mb_substr($str, 0, $pre) . $mask . \mb_substr($str, -$post);
    }
}
