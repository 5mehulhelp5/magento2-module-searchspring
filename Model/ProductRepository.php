<?php

namespace SearchSpring\Feed\Model;

use SearchSpring\Feed\Api\ProductRepositoryInterface;
use SearchSpring\Feed\Api\RequestItemInterfaceFactory;
use SearchSpring\Feed\Api\ResponseItemInterface;
use SearchSpring\Feed\Api\ResponseItemInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product\Action;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface as MagentoProductRepository;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;


/**
 * Class ProductRepository
 */
class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @var Action
     */
    private $productAction;
    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var RequestItemInterfaceFactory
     */
    private $requestItemFactory;
    /**
     * @var ResponseItemInterfaceFactory
     */
    private $responseItemFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param Action $productAction
     * @param CollectionFactory $productCollectionFactory
     * @param RequestItemInterfaceFactory $requestItemFactory
     * @param ResponseItemInterfaceFactory $responseItemFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Action                       $productAction,
        CollectionFactory            $productCollectionFactory,
        RequestItemInterfaceFactory  $requestItemFactory,
        ResponseItemInterfaceFactory $responseItemFactory,
        StoreManagerInterface        $storeManager
    )
    {
        $this->productAction = $productAction;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->requestItemFactory = $requestItemFactory;
        $this->responseItemFactory = $responseItemFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param int $id
     * @return ResponseItemInterface
     * @throws NoSuchEntityException
     */
    public function getItem(int $id): mixed
    {
        $collection = $this->getProductCollection()
            ->addAttributeToFilter('entity_id', ['eq' => $id]);

        /** @var ProductInterface $product */
        $product = $collection->getFirstItem();

        if (!$product->getId()) {
            throw new NoSuchEntityException(__('Product not found'));
        }
        return $this->getResponseItemFromProduct($product);
    }

    /**
     * @return Collection
     */
    private function getProductCollection(): mixed
    {
        /** @var Collection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection
            ->addAttributeToSelect('*');
        return $collection;
    }

    /**
     * @param ProductInterface $product
     * @return ResponseItemInterface
     */
    private function getResponseItemFromProduct(ProductInterface $product): mixed
    {

        /** @var ResponseItemInterface $responseItem */
        $responseItem = $this->responseItemFactory->create();
        $responseItem->setId($product->getId())
            ->setSku($product->getSku())
            ->setName($product->getName())
            ->setDescription($product->getDescription())
            ->setAttributeSetId($product->getAttributeSetId());
        return $responseItem;
    }
}

