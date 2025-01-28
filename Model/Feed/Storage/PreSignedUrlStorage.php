<?php
/**
 * Copyright (C) 2023 Searchspring <https://searchspring.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace SearchSpring\Feed\Model\Feed\Storage;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use SearchSpring\Feed\Api\AppConfigInterface;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Api\MetadataInterface;
use SearchSpring\Feed\Model\Aws\PreSignedUrl;
use SearchSpring\Feed\Model\Feed\Storage\File\FileFactory;
use SearchSpring\Feed\Model\Feed\Storage\File\NameGenerator;
use SearchSpring\Feed\Model\Feed\StorageInterface;

class PreSignedUrlStorage implements StorageInterface
{
    /**
     * @var FormatterPool
     */
    private $formatterPool;
    /**
     * @var PreSignedUrl
     */
    private $preSignedUrl;
    /**
     * @var FileInterface
     */
    private $file;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $feedType;
    /**
     * @var NameGenerator
     */
    private $nameGenerator;

    /**
     * @var FeedSpecificationInterface
     */
    private $specification;
    /**
     * @var FileFactory
     */
    private $fileFactory;
    /**
     * @var AppConfigInterface
     */
    private $appConfig;

    /**
     * PreSignedUrlStorage constructor.
     * @param FormatterPool $formatterPool
     * @param PreSignedUrl $preSignedUrl
     * @param NameGenerator $nameGenerator
     * @param FileFactory $fileFactory
     * @param AppConfigInterface $appConfig
     * @param string $type
     * @param string $feedType
     */
    public function __construct(
        FormatterPool $formatterPool,
        PreSignedUrl $preSignedUrl,
        NameGenerator $nameGenerator,
        FileFactory $fileFactory,
        AppConfigInterface $appConfig,
        string $type = 'aws_presigned',
        string $feedType = 'product'
    ) {
        $this->formatterPool = $formatterPool;
        $this->preSignedUrl = $preSignedUrl;
        $this->type = $type;
        $this->feedType = $feedType;
        $this->nameGenerator = $nameGenerator;
        $this->fileFactory = $fileFactory;
        $this->appConfig = $appConfig;
    }

    /**
     * @param string $format
     * @return bool
     */
    public function isSupportedFormat(string $format): bool
    {
        return !is_null($this->formatterPool->get($format)) && $this->fileFactory->isSupportedFormat($format);
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @throws Exception
     */
    public function initiate(FeedSpecificationInterface $feedSpecification): void
    {
        $format = $feedSpecification->getFormat();
        if (!$format) {
            throw new Exception((string) __('format cannot be empty'));
        }

        if (!$this->isSupportedFormat($format)) {
            throw new Exception((string) __('%1 is not supported format', $format));
        }

        $this->initializeFile($feedSpecification);
        $this->specification = $feedSpecification;
    }

    /**
     * @param array $data
     * @throws Exception
     */
    public function addData(array $data): void
    {
        $file = $this->getFile();
        $specification = $this->getSpecification();
        $format = $specification->getFormat();
        if (!$format) {
            throw new Exception((string) __('format cannot be empty'));
        }

        if (!$this->isSupportedFormat($format)) {
            throw new Exception((string) __('%1 is not supported format', $format));
        }

        $formatter = $this->formatterPool->get($format);
        $data = $formatter->format($data, $specification);
        $file->appendData($data);
    }

    /**
     * @param bool $deleteFile
     * @throws FileSystemException
     * @throws RuntimeException
     * @throws Exception
     */
    public function commit(bool $deleteFile = true): void
    {
        $file = $this->getFile();
        $filePath = $file->getAbsolutePath();

        $urlPath = parse_url($this->specification->getPreSignedUrl(), PHP_URL_PATH);

        // For json.gz,csv.gz treat as JSON/ csv format for compression
        if (str_contains($urlPath, MetadataInterface::FORMAT_JSON_GZ) || str_contains($urlPath, MetadataInterface::FORMAT_CSV_GZ)) {
            $gzFilePath = $filePath . '.gz';
            $this->compressFile($filePath, $gzFilePath);
            $filePath = $gzFilePath;  // Use the gzipped file for saving
        }

        $file->commit();
        $data = [
            'type' => 'stream',
            'file' => $filePath,
        ];

        try {
            $this->preSignedUrl->save($this->specification, $data);
        } finally {
            if ((!$this->appConfig->isDebug() || $this->appConfig->getValue('product_delete_file'))
                && $deleteFile
            ) {
                $file->delete();
            }
        }
    }

    /**
     * Compress the file into GZ format
     *
     * @param string $sourceFile
     * @param string $targetFile
     * @return void
     * @throws RuntimeException
     */
    private function compressFile(string $sourceFile, string $targetFile): void
    {
        $source = fopen($sourceFile, 'rb');
        $destination = gzopen($targetFile, 'wb9'); // Open file for gz compression

        if ($source === false || $destination === false) {
            throw new RuntimeException('Unable to open file for compression.');
        }

        // Compress the file in chunks to avoid memory overflow
        while (!feof($source)) {
            gzwrite($destination, fread($source, 1024 * 512)); // 512 KB chunk size
        }

        fclose($source);
        gzclose($destination);
    }

    /**
     * @throws Exception
     */
    public function rollback(): void
    {
        $this->getFile()->rollback();
    }

    /**
     * @throws Exception
     */
    public function getAdditionalData(): array
    {
        $additionalData = $this->getFile()->getFileInfo();
        $additionalData['name'] = $this->getFile()->getName();
        return $additionalData;
    }

    /**
     * @param FeedSpecificationInterface $feedSpecification
     * @throws Exception
     */
    private function initializeFile(FeedSpecificationInterface $feedSpecification) : void
    {
        $format = $feedSpecification->getFormat();
        $file = $this->fileFactory->create($format);
        $options = [$this->feedType, $this->type];
        $name = $this->nameGenerator->generate($options);
        $file->initialize($name, $feedSpecification);
        $this->file = $file;
    }

    /**
     * @return FileInterface
     * @throws Exception
     */
    public function getFile() : FileInterface
    {
        if (!$this->file) {
            throw new Exception('file is not initialized yet');
        }

        return $this->file;
    }

    /**
     * @return FeedSpecificationInterface
     * @throws Exception
     */
    private function getSpecification() : FeedSpecificationInterface
    {
        if (!$this->specification) {
            throw new Exception('specification is not initialized yet');
        }

        return $this->specification;
    }
}
