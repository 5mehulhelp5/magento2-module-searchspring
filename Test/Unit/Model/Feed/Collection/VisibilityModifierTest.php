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

namespace SearchSpring\Feed\Test\Unit\Model\Feed\Collection;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Model\Feed\Collection\VisibilityModifier;

class VisibilityModifierTest extends \PHPUnit\Framework\TestCase
{
    private $visibilityMock;

    private $visibilityModifier;

    public function setUp(): void
    {
        $this->visibilityMock = $this->createMock(Visibility::class);
        $this->visibilityModifier = new VisibilityModifier($this->visibilityMock);
    }

    public function testModify()
    {
        $visibility = [2,4];
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $this->visibilityMock->expects($this->once())
            ->method('getVisibleInSiteIds')
            ->willReturn($visibility);
        $collectionMock = $this->createMock(Collection::class);
        $collectionMock->expects($this->once())
            ->method('setVisibility')
            ->with($visibility)
            ->willReturnSelf();

        $this->assertSame($collectionMock, $this->visibilityModifier->modify($collectionMock, $feedSpecificationMock));
    }
}
