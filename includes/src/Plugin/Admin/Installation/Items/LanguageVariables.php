<?php declare(strict_types=1);

namespace JTL\Plugin\Admin\Installation\Items;

use JTL\Helpers\GeneralObject;
use JTL\Language\LanguageHelper;
use JTL\Plugin\Admin\InputType;
use JTL\Plugin\InstallCode;
use stdClass;

/**
 * Class LanguageVariables
 * @package JTL\Plugin\Admin\Installation\Items
 */
class LanguageVariables extends AbstractItem
{
    /**
     * @inheritdoc
     */
    public function getNode(): array
    {
        return $this->baseNode['Install'][0]['Locales'][0]['Variable'] ?? [];
    }

    /**
     * @inheritdoc
     */
    public function install(): int
    {
        $languages = LanguageHelper::getAllLanguages(2, true);
        foreach ($this->getNode() as $t => $langVar) {
            $nonPluginLanguages = $languages;
            $t                  = (string)$t;
            \preg_match('/[\d]+/', $t, $hits1);
            if (\mb_strlen($hits1[0]) !== \mb_strlen($t)) {
                continue;
            }
            $pluginLangVar                = new stdClass();
            $pluginLangVar->kPlugin       = $this->plugin->kPlugin;
            $pluginLangVar->cName         = $langVar['Name'];
            $pluginLangVar->type          = $langVar['Type'] ?? InputType::TEXT;
            $pluginLangVar->cBeschreibung = '';
            if (isset($langVar['Description']) && !GeneralObject::isCountable('Description', $langVar)) {
                $pluginLangVar->cBeschreibung = \preg_replace('/\s+/', ' ', $langVar['Description']);
            }
            $id = $this->db->insert('tpluginsprachvariable', $pluginLangVar);
            if ($id <= 0) {
                return InstallCode::SQL_CANNOT_SAVE_LANG_VAR;
            }
            // Ist der erste Standard Link gesetzt worden? => wird etwas weiter unten gebraucht
            // Falls Shopsprachen vom Plugin nicht berücksichtigt wurden, werden diese weiter unten
            // nachgetragen. Dafür wird die erste Sprache vom Plugin als Standard genutzt.
            $isDefault  = false;
            $defaultVar = new stdClass();
            // Nur eine Sprache vorhanden
            if (GeneralObject::hasCount('VariableLocalized attr', $langVar)) {
                // tpluginsprachvariablesprache füllen
                $localized                        = new stdClass();
                $localized->kPluginSprachvariable = $id;
                $localized->cISO                  = $langVar['VariableLocalized attr']['iso'];
                $localized->cName                 = \preg_replace('/\s+/', ' ', $langVar['VariableLocalized']);
                $isoKey                           = \mb_convert_case($localized->cISO, \MB_CASE_LOWER);

                $this->db->insert('tpluginsprachvariablesprache', $localized);

                // Erste PluginSprachVariableSprache vom Plugin als Standard setzen
                $defaultVar = $localized;

                if (isset($nonPluginLanguages[$isoKey])) {
                    // Resette aktuelle Sprache
                    unset($nonPluginLanguages[$isoKey]);
                } elseif (isset($languages[$isoKey])) {
                    $nonPluginLanguages[$isoKey] = $languages[$isoKey];
                }
            } elseif (GeneralObject::hasCount('VariableLocalized', $langVar)) {
                foreach ($langVar['VariableLocalized'] as $i => $loc) {
                    $i = (string)$i;
                    \preg_match('/[\d]+\sattr/', $i, $hits1);

                    if (isset($hits1[0]) && \mb_strlen($hits1[0]) === \mb_strlen($i)) {
                        $iso                              = $loc['iso'];
                        $yx                               = \mb_substr($i, 0, \mb_strpos($i, ' '));
                        $name                             = $langVar['VariableLocalized'][$yx];
                        $localized                        = new stdClass();
                        $localized->kPluginSprachvariable = $id;
                        $localized->cISO                  = $iso;
                        $localized->cName                 = \preg_replace('/\s+/', ' ', $name);
                        $isoKey                           = \mb_convert_case($localized->cISO, \MB_CASE_LOWER);

                        $this->db->insert('tpluginsprachvariablesprache', $localized);
                        // Erste PluginSprachVariableSprache vom Plugin als Standard setzen
                        if (!$isDefault) {
                            $defaultVar = $localized;
                            $isDefault  = true;
                        }

                        if (isset($nonPluginLanguages[$isoKey])) {
                            // Resette aktuelle Sprache
                            unset($nonPluginLanguages[$isoKey]);
                        } elseif (isset($languages[$isoKey])) {
                            $nonPluginLanguages[$isoKey] = $languages[$isoKey];
                        }
                    }
                }
            }
            foreach ($nonPluginLanguages as $language) {
                $defaultVar->cISO = \mb_convert_case($language->cISO, \MB_CASE_UPPER);
                if (!$this->db->insert('tpluginsprachvariablesprache', $defaultVar)) {
                    return InstallCode::SQL_CANNOT_SAVE_LANG_VAR_LOCALIZATION;
                }
            }
        }

        return InstallCode::OK;
    }
}
