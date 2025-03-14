<?php
/**
 * Helper to fetch version data.
 *
 * This file is part of SearchSpring/Feed.
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace SearchSpring\Feed\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Module\ModuleListInterface;

class VersionInfo extends AbstractHelper
{
    const MODULE_NAME = 'SearchSpring_Feed';

    /** @var ProductMetadataInterface */
    private $productMetadata;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var ModuleListInterface
     */
    protected $moduleListInterface;


    /**
     * Constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param DirectoryList $directoryList
     * @param ModuleListInterface $moduleListInterface
     */
    public function __construct(ProductMetadataInterface $productMetadata, DirectoryList $directoryList, ModuleListInterface $moduleListInterface)
    {
        $this->productMetadata = $productMetadata;
        $this->directoryList = $directoryList;
        $this->moduleListInterface = $moduleListInterface;
    }

    public function getVersion()
    {
        $result = [];

        $version = null;
        $module = $this->moduleListInterface->getOne(self::MODULE_NAME);
        if (!empty($module)) {
            $version = $module['setup_version'];
        }

        $result[] = [
            'extensionVersion' => $version,
            'magento' => $this->productMetadata->getName() . '/' . $this->productMetadata->getVersion() . ' (' . $this->productMetadata->getEdition() . ')',
            'memLimit' => $this->getMemoryLimit(),
            'OSType' => php_uname($mode = "s"),
            'OSVersion' => php_uname($mode = "v"),
            'maxExecutionTime' => ini_get("max_execution_time"),
            'magentoName' => $this->productMetadata->getName(),
            'magentoVersion' => $this->productMetadata->getVersion(),
            'magentoEdition' => $this->productMetadata->getEdition(),
            'magentoRootPath' => $this->directoryList->getRoot(),
            'magentoLogPath' => $this->directoryList->getPath(DirectoryList::LOG)
        ];

        return $result;
    }

    public function getMemoryLimit()
    {
        $memoryLimit = trim(strtoupper(ini_get('memory_limit')));

        if (!isSet($memoryLimit[0])) {
            $memoryLimit = "128M";
        }

        if (substr($memoryLimit, -1) == 'K') {
            return substr($memoryLimit, 0, -1) * 1024;
        }
        if (substr($memoryLimit, -1) == 'M') {
            return substr($memoryLimit, 0, -1) * 1024 * 1024;
        }
        if (substr($memoryLimit, -1) == 'G') {
            return substr($memoryLimit, 0, -1) * 1024 * 1024 * 1024;
        }
        return $memoryLimit;
    }
}
