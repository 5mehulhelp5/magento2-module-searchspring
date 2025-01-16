<?php
declare(strict_types=1);

namespace SearchSpring\Feed\Api;
interface ResponseItemInterface
{
    const DATA_ID = 'id';
    const DATA_SKU = 'sku';
    const DATA_NAME = 'name';
    const DATA_DESCRIPTION = 'description';

    const ATTRIBUTE_SET_ID = 'attribute_set_id';

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getSku();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string|null
     */
    public function getDescription();

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id);

    /**
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku);

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name);

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description);

    /**
     * Product attribute set id
     *
     * @return int|null
     */
    public function getAttributeSetId(): ?int;

    /**
     * Set product attribute set id
     *
     * @param int $attributeSetId
     * @return $this
     */
    public function setAttributeSetId(int $attributeSetId): static;
}
