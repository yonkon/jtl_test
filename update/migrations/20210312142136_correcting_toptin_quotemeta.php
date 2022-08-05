<?php declare(strict_types=1);
/**
 * correcting toptin quotemeta
 *
 * @author cr
 * @created Fri, 12 Mar 2021 14:21:36 +0100
 */

use JTL\Update\IMigration;
use JTL\Update\Migration;

/**
 * Class Migration_20210312142136
 */
class Migration_20210312142136 extends Migration implements IMigration
{
    protected $author      = 'cr';
    protected $description = 'correcting toptin quotemeta';

    /**
     * @param string $instr
     * @return string
     */
    private function stripquotes(string $instr): string
    {
        $pattern      = ['\.', '\\\\', '\+', '\*', '\?', '\[', '\^', '\]', '\(', '\$', '\)'];
        $replacements = ['.', '\\', '+', '*', '?', '[', '^', ']', '(', '$', ')'];

        return \str_replace($pattern, $replacements, $instr);
    }

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        foreach ($this->getDB()->getObjects('SELECT * FROM toptin') as $optin) {
            $optin->kOptinClass = $this->stripquotes($optin->kOptinClass);
            $optin->cRefData    = $this->stripquotes($optin->cRefData);
            $this->getDB()->queryPrepared(
                'UPDATE toptin
                    SET kOptinClass = :kOptinClass,
                        cRefData = :cRefData
                    WHERE kOptin = :kOptin',
                [
                    'kOptinClass' => $optin->kOptinClass,
                    'cRefData'    => $optin->cRefData,
                    'kOptin'      => $optin->kOptin
                ]
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function down(): void
    {
        // there is no way back
    }
}
