<?php

namespace SearchSpring\Feed\Test\Unit\Model\Feed\DataProvider;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Api\Data\MediaGallerySpecificationInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\MediaGalleryProvider;

class MediaGalleryProviderTest extends \PHPUnit\Framework\TestCase
{
    private $imageHelperMock;

    private $jsonMock;

    private $mediaGalleryProvider;

    public function setUp(): void
    {
        $this->imageHelperMock = $this->createMock(Image::class);
        $this->jsonMock = $this->createMock(Json::class);
        $this->mediaGalleryProvider = new MediaGalleryProvider(
            $this->imageHelperMock,
            $this->jsonMock
        );
    }

    public function testGetData()
    {
        $productGalleryImageMock = $this->createMock(Product\Gallery\Entry::class);
        $imageMock = $this->createMock(\Magento\Catalog\Helper\Image::class);
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $products = [
            [
                'product_model' => $productMock
            ]
        ];
        $mediaGalleryResult = [
            'label' => 'label',
            'position' => 1,
            'disabled' => false,
            'image' => 'http://test.url/image.jpg'
        ];
        $mediaGallerySpecificationMock = $this->getMockForAbstractClass(MediaGallerySpecificationInterface::class);
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $feedSpecificationMock->expects($this->once())
            ->method('getMediaGallerySpecification')
            ->willReturn($mediaGallerySpecificationMock);
        $feedSpecificationMock->expects($this->once())
            ->method('getIgnoreFields')
            ->willReturn([]);
        $mediaGallerySpecificationMock->expects($this->once())
            ->method('getImageTypes')
            ->willReturn([]);
        $mediaGallerySpecificationMock->expects($this->any())
            ->method('getKeepAspectRatio')
            ->willReturn(false);
        $this->imageHelperMock->expects($this->any())
            ->method('init')
            ->with($productMock, 'product_thumbnail_image')
            ->willReturn($imageMock);
        $mediaGallerySpecificationMock->expects($this->any())
            ->method('getThumbHeight')
            ->willReturn(100);
        $mediaGallerySpecificationMock->expects($this->any())
            ->method('getThumbWidth')
            ->willReturn(100);
        $imageMock->expects($this->any())
            ->method('resize')
            ->with(100, 100)
            ->willReturnSelf();
        $imageMock->expects($this->any())
            ->method('getUrl')
            ->willReturn('http://test.url/image.jpg');
        $mediaGallerySpecificationMock->expects($this->once())
            ->method('getIncludeMediaGallery')
            ->willReturn(true);
        $productMock->expects($this->once())
            ->method('getMediaGalleryImages')
            ->willReturn([$productGalleryImageMock]);
        $productGalleryImageMock->expects($this->once())
            ->method('getMediaType')
            ->willReturn('image');
        $productGalleryImageMock->expects($this->once())
            ->method('getLabel')
            ->willReturn('label');
        $productGalleryImageMock->expects($this->once())
            ->method('getPosition')
            ->willReturn(1);
        $productGalleryImageMock->expects($this->once())
            ->method('__call')
            ->withAnyParameters()
            ->willReturn(false);
        $productGalleryImageMock->expects($this->once())
            ->method('getFile')
            ->willReturn('file.jpg');
        $imageMock->expects($this->once())
            ->method('setImageFile')
            ->with('file.jpg')
            ->willReturnSelf();
        $this->jsonMock->expects($this->once())
            ->method('serialize')
            ->with([$mediaGalleryResult])
            ->willReturn(json_encode($mediaGalleryResult));

        $this->assertSame(
            [
                array_merge(
                    $products[0],
                    [
                        'cached_thumbnail' => 'http://test.url/image.jpg',
                        'media_gallery_json' => json_encode($mediaGalleryResult),
                    ]
                )
            ],
            $this->mediaGalleryProvider->getData($products, $feedSpecificationMock)
        );
    }
}
