<?php declare(strict_types=1);

namespace JTL\Filesystem;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV2\SftpAdapter;
use League\Flysystem\PhpseclibV2\SftpConnectionProvider;

/**
 * Class AdapterFactory
 * @package JTL\Filesystem
 */
class AdapterFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * AdapterFactory constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return FilesystemAdapter
     */
    public function getAdapter(): FilesystemAdapter
    {
        switch ($this->config['fs_adapter'] ?? $this->config['fs']['fs_adapter']) {
            case 'ftp':
                return new FtpAdapter(FtpConnectionOptions::fromArray($this->getFtpConfig()));
            case 'sftp':
                return new SftpAdapter($this->getSftpConfig(), \rtrim($this->config['sftp_path'], '/') . '/');
            case 'local':
            default:
                return new LocalFilesystemAdapter(\PFAD_ROOT);
        }
    }

    /**
     * @param string $adapter
     */
    public function setAdapter(string $adapter): void
    {
        $this->config['fs_adapter'] = $adapter;
    }

    /**
     * @param array $config
     */
    public function setFtpConfig(array $config): void
    {
        $this->config = \array_merge($this->config, $config);
    }

    /**
     * @param array $config
     */
    public function setSftpConfig(array $config): void
    {
        $this->config = \array_merge($this->config, $config);
    }

    /**
     * @return array
     */
    private function getFtpConfig(): array
    {
        return [
            'host'                 => $this->config['ftp_hostname'],
            'port'                 => $this->config['ftp_port'],
            'username'             => $this->config['ftp_user'],
            'password'             => $this->config['ftp_pass'],
            'ssl'                  => (int)$this->config['ftp_ssl'] === 1,
            'root'                 => \rtrim($this->config['ftp_path'], '/') . '/',
            'timeout'              => $this->config['fs_timeout'],
            'passive'              => true,
            'ignorePassiveAddress' => false
        ];
    }

    /**
     * @return SftpConnectionProvider
     */
    public function getSftpConfig(): SftpConnectionProvider
    {
        return new SftpConnectionProvider(
            $this->config['sftp_hostname'],
            $this->config['sftp_user'],
            $this->config['sftp_pass'],
            $this->config['sftp_privkey'],
            null,
            $this->config['sftp_port'],
            false,
            $this->config['fs_timeout']
        );
    }
}
