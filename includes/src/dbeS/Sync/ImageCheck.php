<?php

namespace JTL\dbeS\Sync;

use JTL\dbeS\Starter;
use SimpleXMLElement;
use stdClass;

/**
 * Class ImageCheck
 * @package JTL\dbeS\Sync
 */
final class ImageCheck extends AbstractSync
{
    /**
     * @param Starter $starter
     * @return mixed|null
     */
    public function handle(Starter $starter)
    {
        foreach ($starter->getXML(true) as $i => $item) {
            [$file, $xml] = [\key($item), \reset($item)];
            if (\strpos($file, 'bildercheck.xml') !== false) {
                $this->handleCheck($xml);
            }
        }

        return null;
    }

    /**
     * @param SimpleXMLElement $xml
     */
    private function handleCheck(SimpleXMLElement $xml): void
    {
        $found  = [];
        $sqls   = [];
        $object = $this->getObject($xml);
        foreach ($object->items as $item) {
            $hash   = $this->db->escape($item->hash);
            $sqls[] = '(kBild = ' . $item->id . " && cPfad = '" . $hash . "')";
        }
        $sqlOr = \implode(' || ', $sqls);
        foreach ($this->db->getObjects('SELECT kBild AS id, cPfad AS hash FROM tbild WHERE ' . $sqlOr) as $image) {
            $image->id = (int)$image->id;
            $storage   = \PFAD_ROOT . \PFAD_MEDIA_IMAGE_STORAGE . $image->hash;
            if (\file_exists($storage)) {
                $found[] = $image->id;
            } else {
                $this->logger->debug('Dropping orphan ' . $image->id . ' -> ' . $image->hash . ': no such file');
                $this->db->delete('tbild', 'kBild', $image->id);
                $this->db->delete('tartikelpict', 'kBild', $image->id);
            }
        }
        if ($object->cloud) {
            foreach ($object->items as $item) {
                if (\in_array($item->id, $found, true)) {
                    continue;
                }
            }
        }
        $missing = \array_filter($object->items, static function ($item) use ($found) {
            return !\in_array($item->id, $found, true);
        });

        $ids = \array_map(static function ($item) {
            return $item->id;
        }, $missing);

        $idlist = \implode(';', $ids);
        $this->pushResponse("0;\n<bildcheck><notfound>" . $idlist . '</notfound></bildcheck>');
    }

    /**
     * @param string $content
     */
    private function pushResponse(string $content): void
    {
        \ob_clean();
        echo $content;
        exit;
    }

    /**
     * @param SimpleXMLElement $xml
     * @return stdClass
     */
    private function getObject(SimpleXMLElement $xml): stdClass
    {
        $cloudURL = (string)$xml->attributes()->cloudURL;
        $check    = (object)[
            'url'   => $cloudURL,
            'cloud' => \strlen($cloudURL) > 0,
            'items' => []
        ];
        foreach ($xml->children() as $child) {
            $check->items[] = (object)[
                'id'   => (int)$child->attributes()->kBild,
                'hash' => (string)$child->attributes()->cHash
            ];
        }

        return $check;
    }
}
