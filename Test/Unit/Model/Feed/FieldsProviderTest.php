<?php

namespace SearchSpring\Feed\Test\Unit\Model\Feed;

use Magento\Catalog\Model\Product\Option;
use Magento\Catalog\Model\ResourceModel\Product\Option\Collection as OptionCollection;
use Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Api\Data\MediaGallerySpecificationInterface;
use SearchSpring\Feed\Exception\NoSuchEntityException;
use SearchSpring\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;
use SearchSpring\Feed\Model\Feed\FieldsProvider;

class FieldsProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AttributesProviderInterface
     */
    private $attributesProviderMock;

    private $defaultFields = [
        // Core Magento ID Fields
        'entity_id',
        'type_id',
        'attribute_set_id',
        // SearchSpring Generated Fields
        'cached_thumbnail',
        'stock_qty',
        'in_stock',
        'categories',
        'category_hierarchy',
        'saleable',
        'url',
        'final_price',
        'regular_price',
        'max_price',
        'rating',
        'rating_count',
        'child_sku',
        'child_name'
    ];

    /**
     * @var CollectionFactory
     */
    private $collectionFactoryMock;

    /**
     * @var StoreManagerInterface
     */
    private $storeManagerMock;

    public function setUp(): void
    {
        $this->attributesProviderMock = $this->createMock(AttributesProviderInterface::class);
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->fieldsProvider = new FieldsProvider(
            $this->attributesProviderMock,
            $this->collectionFactoryMock,
            $this->storeManagerMock,
            $this->defaultFields
        );
    }

    public function testGetFields()
    {
        $storeMock = $this->createMock(Store::class);
        $mediaGallerySpecificationMock = $this->getMockForAbstractClass(MediaGallerySpecificationInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $optionMock = $this->createMock(Option::class);
        $collectionMock = $this->createMock(OptionCollection::class);
        $options = [$optionMock];

        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeMenuCategories')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeUrlHierarchy')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeChildPrices')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeJSONConfig')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeTierPricing')
            ->willReturn(false);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeChildPrices')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->any())
            ->method('getMediaGallerySpecification')
            ->willReturn($mediaGallerySpecificationMock);
        $mediaGallerySpecificationMock->expects($this->once())
            ->method('getIncludeMediaGallery')
            ->willReturn(true);
        $mediaGallerySpecificationMock->expects($this->once())
            ->method('getImageTypes')
            ->willReturn(['type']);
        $feedSpecificationMock->expects($this->once())
            ->method('getStoreCode')
            ->willReturn('default');

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willReturn($storeMock);
        $storeMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);
        $this->attributesProviderMock->expects($this->once())
            ->method('getAttributeCodes')
            ->with($feedSpecificationMock)
            ->willReturn([]);
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);
        $collectionMock->expects($this->once())
            ->method('addTitleToResult')
            ->willReturnSelf();
        $collectionMock->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($options));
        $optionMock->expects($this->once())
            ->method('getTitle')
            ->willReturn('option_title');
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);

        $this->assertSame(
            array_values(
                array_merge(
                    $this->defaultFields,
                    [
                        'menu_hierarchy',
                        'url_hierarchy',
                        'child_final_price',
                        'json_config',
                        'swatch_json_config',
                        'media_gallery_json',
                        'cached_type',
                        'option_option_title'
                    ]
                )
            ),
            array_values($this->fieldsProvider->getFields($feedSpecificationMock))
        );
    }

    public function testGetFieldsExceptionCase()
    {
        $mediaGallerySpecificationMock = $this->getMockForAbstractClass(MediaGallerySpecificationInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);

        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeMenuCategories')
            ->willReturn(false);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeUrlHierarchy')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeChildPrices')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeJSONConfig')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeTierPricing')
            ->willReturn(false);
        $feedSpecificationMock->expects($this->once())
            ->method('getIncludeChildPrices')
            ->willReturn(true);
        $feedSpecificationMock->expects($this->any())
            ->method('getMediaGallerySpecification')
            ->willReturn($mediaGallerySpecificationMock);
        $mediaGallerySpecificationMock->expects($this->once())
            ->method('getIncludeMediaGallery')
            ->willReturn(true);
        $mediaGallerySpecificationMock->expects($this->once())
            ->method('getImageTypes')
            ->willReturn(['type']);
        $feedSpecificationMock->expects($this->once())
            ->method('getStoreCode')
            ->willReturn('default');

        $this->storeManagerMock->expects($this->once())
            ->method('getStore')
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(NoSuchEntityException::class);
        $this->fieldsProvider->getFields($feedSpecificationMock);
    }
}
