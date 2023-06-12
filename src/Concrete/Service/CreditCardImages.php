<?php

namespace Concrete\Package\CommunityStoreBccPayway\Service;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Package\PackageService;

defined('C5_EXECUTE') or die('Access Denied');

class CreditCardImages
{
    const RX_FILE_BASENAME = '[a-z]([\w\-]*[a-z])?';

    const RX_FILE_EXTENSION = 'png|jpe?g|gif';

    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * @var string
     */
    protected $pathPrefix;

    /**
     * @var string
     */
    protected $urlPrefix;

    /**
     * @var string[]|null
     */
    protected $wantedImageHandles;

    /**
     * @var array|null
     */
    private $availableImages;

    public function __construct(Repository $config, PackageService $packageService)
    {
        $this->config = $config;
        $packageEntity = $packageService->getByHandle('community_store_bcc_payway');
        $packageController = $packageEntity->getController();
        $suffix = '/images/credit-cards/';
        $this->pathPrefix = rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $packageController->getPackagePath()), '/') . $suffix;
        $this->urlPrefix = rtrim($packageController->getRelativePath(), '/') . $suffix;
    }

    /**
     * @return string[]
     */
    public function getAvailableImageHandles()
    {
        return array_keys($this->getAvailableImages());
    }

    /**
     * @return string[]
     */
    public function getWantedImageHandles()
    {
        if ($this->wantedImageHandles === null) {
            $this->setWantedImageHandles($this->extractWantedImagesFromConfig());
        }

        return $this->wantedImageHandles;
    }

    /**
     * @param string[] $handles
     * @param bool $save
     *
     * @return $this
     */
    public function setWantedImageHandles(array $handles, $save = false)
    {
        $availableHandles = array_keys($this->getAvailableImages());
        $filtered = [];
        foreach ($handles as $handle) {
            if (in_array($handle, $availableHandles, true) && !in_array($handle, $filtered, true)) {
                $filtered[] = $handle;
            }
        }
        if ($save) {
            $serialized = implode(' ', $filtered);
            $this->config->set('community_store_bcc_payway::options.creditCardImages', $serialized);
            $this->config->save('community_store_bcc_payway::options.creditCardImages', $serialized);
        }
        $this->wantedImageHandles = $filtered;

        return $this;
    }

    /**
     * @param string|mixed $handle
     * @param int|null $maxWidth
     * @param int|null $maxHeight
     *
     * @return string empty string if the handle is not valid
     */
    public function renderImage($handle, $maxWidth = null, $maxHeight = null)
    {
        if (!is_string($handle) || $handle === '') {
            return '';
        }
        $availableImages = $this->getAvailableImages();
        if (!isset($availableImages[$handle])) {
            return '';
        }
        $attrs = [
            'alt' => str_replace('_', ' ', $handle),
            'src' => $this->urlPrefix . $availableImages[$handle],
        ];
        $attrs['title'] = t('Pay with %s', $attrs['alt']);
        $styles = ['display: inline'];
        if (($maxWidth = (int) $maxWidth) > 0) {
            $styles[] = "max-width: {$maxWidth}px";
        }
        if (($maxHeight = (int) $maxHeight) > 0) {
            $styles[] = "max-height: {$maxHeight}px";
        }
        if ($styles !== []) {
            $attrs['style'] = implode('; ', $styles);
        }
        $result = '<img';
        foreach (array_map(static function ($value) { return h($value); }, $attrs) as $name => $value) {
            $result .= " {$name}=\"{$value}\"";
        }
        $result .= ' />';

        return $result;
    }

    /**
     * @param int|null $maxImagesWidth
     * @param int|null $maxImagesHeight
     * @param string $beforeImages
     * @param string $betweenImages
     * @param string $afterImages
     *
     * @return string
     */
    public function renderWantedImages($maxImagesWidth = null, $maxImagesHeight = null, $beforeImages = '', $betweenImages = '', $afterImages = '')
    {
        $chunks = [];
        foreach ($this->getWantedImageHandles() as $handle) {
            $item = $this->renderImage($handle, $maxImagesWidth, $maxImagesHeight);
            if ($item === '') {
                continue;
            }
            $chunks[] = $chunks === [] ? (string) $beforeImages : (string) $betweenImages;
            $chunks[] = $item;
        }
        if ($chunks !== []) {
            $chunks[] = (string) $afterImages;
        }

        return implode('', $chunks);
    }

    /**
     * @return array Array keys are the handles, array values are the file names
     */
    protected function getAvailableImages()
    {
        if ($this->availableImages === null) {
            $availableImages = [];
            if (is_dir($this->pathPrefix) && is_readable($this->pathPrefix)) {
                set_error_handler(static function () {}, -1);
                try {
                    $contents = scandir($this->pathPrefix);
                } finally {
                    restore_error_handler();
                }
                if ($contents) {
                    $rx = '/^(?<handle>' . static::RX_FILE_BASENAME . ')\.(' . static::RX_FILE_EXTENSION . ')$/i';
                    $match = null;
                    foreach ($contents as $item) {
                        if (preg_match($rx, $item, $match)) {
                            $availableImages[$match['handle']] = $item;
                        }
                    }
                }
            }
            $this->availableImages = $availableImages;
        }

        return $this->availableImages;
    }

    /**
     * @return string[]
     */
    protected function extractWantedImagesFromConfig()
    {
        $value = trim((string) $this->config->get('community_store_bcc_payway::options.creditCardImages', ''));
        $matches = null;

        return preg_match_all('/' . static::RX_FILE_BASENAME . '/i', $value, $matches) ? $matches[0] : [];
    }
}
