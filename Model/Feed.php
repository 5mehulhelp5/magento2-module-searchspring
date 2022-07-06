<?php

declare(strict_types=1);

namespace SearchSpring\Feed\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use SearchSpring\Feed\Api\Data\FeedInterface;
use SearchSpring\Feed\Api\Data\FeedExtensionInterface;
use SearchSpring\Feed\Model\ResourceModel\Feed as FeedResource;

class Feed extends AbstractExtensibleModel implements FeedInterface
{
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * Feed constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param DateTime $dateTime
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        DateTime $dateTime,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $extensionFactory, $customAttributeFactory, $resource, $resourceCollection, $data);
        $this->dateTime = $dateTime;
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->_init(FeedResource::class);
    }

    /**
     * @return int|null
     */
    public function getEntityId() : ?int
    {
        return !is_null($this->getData(self::ENTITY_ID))
            ? (int) $this->getData(self::ENTITY_ID)
            : null;
    }

    /**
     * @param int $id
     * @return FeedInterface
     */
    public function setEntityId($id) : FeedInterface
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @return int|null
     */
    public function getTaskId(): ?int
    {
        return !is_null($this->getData(self::TASK_ID))
            ? (int) $this->getData(self::TASK_ID)
            : null;
    }

    /**
     * @param int $id
     * @return FeedInterface
     */
    public function setTaskId(int $id): FeedInterface
    {
        return $this->setData(self::TASK_ID, $id);
    }

    /**
     * @return string|null
     */
    public function getDirectoryType(): ?string
    {
        return $this->getData(self::DIRECTORY_TYPE);
    }

    /**
     * @param string $type
     * @return FeedInterface
     */
    public function setDirectoryType(string $type): FeedInterface
    {
        return $this->setData(self::DIRECTORY_TYPE, $type);
    }

    /**
     * @return string|null
     */
    public function getFilePath(): ?string
    {
        return $this->getData(self::FILE_PATH);
    }

    /**
     * @param string $path
     * @return FeedInterface
     */
    public function setFilePath(string $path): FeedInterface
    {
        return $this->setData(self::FILE_PATH, $path);
    }

    /**
     * @return bool|null
     */
    public function getFetched(): ?bool
    {
        return !is_null($this->getData(self::FETCHED))
            ? (bool) $this->getData(self::FETCHED)
            : null;
    }

    /**
     * @param bool $flag
     * @return FeedInterface
     */
    public function setFetched(bool $flag): FeedInterface
    {
        return $this->setData(self::FETCHED, $flag);
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @param string $date
     * @return FeedInterface
     */
    public function setCreatedAt(string $date): FeedInterface
    {
        return $this->setData(self::CREATED_AT, $date);
    }

    /**
     * @return FeedExtensionInterface|null
     */
    public function getExtensionAttributes(): ?FeedExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @param FeedExtensionInterface $extensionAttributes
     * @return FeedInterface
     */
    public function setExtensionAttributes(FeedExtensionInterface $extensionAttributes): FeedInterface
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }

    public function beforeSave()
    {
        if (!$this->getCreatedAt()) {
            $this->setCreatedAt($this->dateTime->gmtDate());
        }
        return parent::beforeSave(); // TODO: Change the autogenerated stub
    }

    /**
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->getData(self::FORMAT);
    }

    /**
     * @param string $format
     * @return FeedInterface
     */
    public function setFormat(string $format): FeedInterface
    {
        return $this->setData(self::FORMAT, $format);
    }
}
