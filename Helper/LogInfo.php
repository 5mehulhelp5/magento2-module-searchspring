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
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;
class LogInfo extends AbstractHelper
{

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var File
     */
    protected $fileDriver;
    /**
     * @var LoggerInterface
     */
    protected  $logger;

    /**
     * Constructor.
     *
     * @param DirectoryList $directoryList
     * @param File $fileDriver
     * @param LoggerInterface $logger
     */
    public function __construct( DirectoryList $directoryList, File $fileDriver, LoggerInterface $logger)
    {
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->logger = $logger;
    }

    public function deleteExtensionLogFile() : bool
    {
        $logPath = $this->directoryList->getPath(DirectoryList::LOG);
        $logFile = $logPath . '/searchspring_feed.log';

        if ($this->fileDriver->isExists($logFile)) {
            $this->logger->info("File searchspring feed log will be removed from the path:" . $logPath);
            unlink($logFile);
            $this->logger->info("File removed successfully" . $logPath . '/searchspring_feed.log');
        }
        $this->logger->error("File searchspring feed not present at the location" . $logFile);

        return true;
    }

    public function getExtensionLogFile(bool $compressOutput = false) : string
    {
        $result = '';

        $logPath = $this->directoryList->getPath(DirectoryList::LOG);
        $logFile = $logPath . '/searchspring_feed.log';

        if ($this->fileDriver->isExists($logFile)) {
            $this->logger->info("File searchspring feed log will be retrieved from the path: " . $logPath);
            $result = $this->fileDriver->fileGetContents($logFile);

            if (strlen($result) > 0 and $compressOutput){
                $result = rtrim(strtr(base64_encode(gzdeflate($result, 9)), '+/', '-_'), '=');
            }
        }
        $this->logger->error("File searchspring feed log  not present at the location" . $logPath);

        return $result;
    }

    public function deleteExceptionLogFile() : bool
    {
        $logPath = $this->directoryList->getPath(DirectoryList::LOG);
        $logFile = $logPath . '/exception.log';

        if ($this->fileDriver->isExists($logFile)) {
            $this->logger->info("File exception log will be removed from the path: " . $logPath);
            unlink($logFile);
            $this->logger->info("File removed from the path: " . $logPath);
        }
        $this->logger->error("File exception log not present at the location" . $logPath);

        return true;
    }

    public function getExceptionLogFile(bool $compressOutput = false) : string
    {
        $result = '';

        $logPath = $this->directoryList->getPath(DirectoryList::LOG);
        $logFile = $logPath . '/exception.log';

        if ($this->fileDriver->isExists($logFile)) {
            $this->logger->info("File exception log will be retrieved from the path: " . $logPath);
            $result = $this->fileDriver->fileGetContents($logFile);

            if (strlen($result) > 0 and $compressOutput){
                $result = rtrim(strtr(base64_encode(gzdeflate($result, 9)), '+/', '-_'), '=');
            }
        }
        $this->logger->error("File exception log not present at the location" . $logPath);

        return $result;
    }
}
