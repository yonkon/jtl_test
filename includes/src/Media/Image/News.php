<?php declare(strict_types=1);

namespace JTL\Media\Image;

use FilesystemIterator;
use Generator;
use JTL\Media\Image;
use JTL\Media\MediaImageRequest;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use stdClass;

/**
 * Class News
 * @package JTL\Media\Image
 */
class News extends AbstractImage
{
    public const TYPE = Image::TYPE_NEWS;

    /**
     * @var string
     */
    public const REGEX = '/^media\/image'
    . '\/(?P<type>news)'
    . '\/(?P<id>\d+)'
    . '\/(?P<size>xs|sm|md|lg|xl)'
    . '\/(?P<name>[' . self::REGEX_ALLOWED_CHARS . ']+)'
    . '(?:(?:~(?P<number>\d+))?)\.(?P<ext>jpg|jpeg|png|gif|webp)$/';

    /**
     * @inheritdoc
     */
    public static function getImageStmt(string $type, int $id): ?stdClass
    {
        return (object)[
            'stmt' => 'SELECT kNews, 0 AS number  
                           FROM tnews 
                           WHERE kNews = :nid',
            'bind' => ['nid' => $id]
        ];
    }

    /**
     * @inheritdoc
     */
    public function getImageNames(MediaImageRequest $req): array
    {
        if (\mb_strpos($req->getName(), '_preview') === false && $req->getID() > 0) {
            $base     = \PFAD_ROOT . \PFAD_NEWSBILDER;
            $realPath = $req->getID() . '/' . $req->getName() . '.' . $req->getExt();
            if (\file_exists($base . $realPath)) {
                $req->setSourcePath($realPath);
            }

            return [$req->getName()];
        }
        return $this->db->getCollection(
            'SELECT a.kNews, a.cPreviewImage AS path, t.title
                FROM tnews AS a
                LEFT JOIN tnewssprache t
                    ON a.kNews = t.kNews
                WHERE a.kNews = :nid',
            ['nid' => $req->getID()]
        )->each(static function ($item, $key) use ($req) {
            if ($key === 0 && !empty($item->path)) {
                $req->setSourcePath(\str_replace(\PFAD_NEWSBILDER, '', $item->path));
            }
            $item->imageName = self::getCustomName($item);
        })->pluck('imageName')->push($req->getName())->toArray();
    }

    /**
     * @inheritdoc
     */
    public static function getThumb(string $type, $id, $mixed, $size, int $number = 1, string $source = null): string
    {
        if ($source !== null && $id === null && \mb_strpos($source, '/') !== false) {
            // we have a path like <newsid>/<some-image.ext>
            $exp = \explode('/', $source);
            if (isset($exp[0]) && \is_numeric($exp[0])) {
                $id = (int)$exp[0];
            }
        }
        $req   = static::getRequest($type, $id, $source, $size, $number, $source);
        $thumb = $req->getThumb($size);
        $raw   = $req->getRaw();
        if (!\file_exists(\PFAD_ROOT . $thumb) && ($raw === null || !\file_exists($raw))) {
            $thumb = \BILD_KEIN_ARTIKELBILD_VORHANDEN;
        }

        return $thumb;
    }

    /**
     * @inheritdoc
     */
    public static function getCustomName($mixed): string
    {
        if (\is_string($mixed)) {
            if (\strpos($mixed, '/') !== false) {
                $result = \explode('/', $mixed)[1];
            } else {
                $result = $mixed;
            }
            $result = \pathinfo($result)['filename'] ?? 'image';
        } else {
            $result = \method_exists($mixed, 'getTitle') ? $mixed->getTitle() : $mixed->title;
        }

        return empty($result) ? 'image' : Image::getCleanFilename($result);
    }

    /**
     * @inheritdoc
     */
    public function getPathByID($id, int $number = null): ?string
    {
        $item = $this->db->getSingleObject(
            'SELECT cPreviewImage AS path
                FROM tnews
                WHERE kNews = :cid LIMIT 1',
            ['cid' => $id]
        )->path ?? null;

        return empty($item->path)
            ? null
            : \str_replace(\PFAD_NEWSBILDER, '', $item->path);
    }

    /**
     * @inheritdoc
     */
    public static function getStoragePath(): string
    {
        return \PFAD_NEWSBILDER;
    }

    /**
     * @inheritdoc
     */
    public function getAllImages(int $offset = null, int $limit = null): Generator
    {
        $base    = \PFAD_ROOT . self::getStoragePath();
        $rdi     = new RecursiveDirectoryIterator(
            $base,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
        );
        $index   = 0;
        $yielded = 0;
        foreach (new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::CHILD_FIRST) as $fileinfo) {
            /** @var SplFileInfo $fileinfo */
            if ($fileinfo->isFile() && \in_array($fileinfo->getExtension(), self::$imageExtensions, true)) {
                if ($offset !== null && $offset > $index++) {
                    continue;
                }
                ++$yielded;
                if ($limit !== null && $yielded > $limit) {
                    return;
                }
                $path  = \str_replace($base, '', $fileinfo->getPathname());
                $parts = \explode('/', $path);
                $id    = 0;
                if (isset($parts[0]) && \is_numeric($parts[0])) {
                    $id = (int)$parts[0];
                }
                yield MediaImageRequest::create([
                    'id'         => $id,
                    'type'       => self::TYPE,
                    'name'       => $fileinfo->getBasename('.' . $fileinfo->getExtension()),
                    'number'     => 1,
                    'path'       => $path,
                    'sourcePath' => $path,
                    'ext'        => static::getFileExtension($path)
                ]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getTotalImageCount(): int
    {
        $rdi = new RecursiveDirectoryIterator(
            \PFAD_ROOT . self::getStoragePath(),
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
        );
        $cnt = 0;
        foreach (new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::CHILD_FIRST) as $fileinfo) {
            /** @var SplFileInfo $fileinfo */
            if ($fileinfo->isFile() && \in_array($fileinfo->getExtension(), self::$imageExtensions, true)) {
                ++$cnt;
            }
        }

        return $cnt;
    }
}
