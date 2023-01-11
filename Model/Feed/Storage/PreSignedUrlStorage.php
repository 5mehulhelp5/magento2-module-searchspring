<?php

declare(strict_types=1);

namespace SearchSpring\Feed\Model\Feed\Storage;

use Exception;
use Magento\Framework\App\ObjectManager;
use SearchSpring\Feed\Api\AppConfigInterface;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
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

        $formatter = $this->formatterPool->get($format);
        $data = $formatter->format($data, $specification);
        $file->appendData($data);
    }

    /**
     * @throws Exception
     */
    public function commit(): void
    {
        $file = $this->getFile();
        $file->commit();
        $data = [
            'type' => 'stream',
            'file' => $file->getAbsolutePath()
        ];

        try {
            $this->preSignedUrl->save($this->specification, $data);
        } finally {
            if (!$this->appConfig->isDebug() || $this->appConfig->getValue('product_delete_file')) {
                $file->delete();
            }
        }
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
    private function getFile() : FileInterface
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
