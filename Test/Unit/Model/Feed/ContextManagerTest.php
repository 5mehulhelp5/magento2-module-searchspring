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

namespace SearchSpring\Feed\Test\Unit\Model\Feed;

use SearchSpring\Feed\Model\Feed\Context\CustomerContextManager;
use SearchSpring\Feed\Model\Feed\Context\StoreContextManager;
use SearchSpring\Feed\Model\Feed\ContextManager;
use SearchSpring\Feed\Model\Feed\Specification\Feed;

class ContextManagerTest extends \PHPUnit\Framework\TestCase
{
    private $customerContextManagerMock;
    private $storeContextManagerMock;
    private $contextManager;

    public function setUp(): void
    {
        $this->customerContextManagerMock = $this->createMock(CustomerContextManager::class);
        $this->storeContextManagerMock = $this->createMock(StoreContextManager::class);
        $processors = [
            $this->customerContextManagerMock,
            $this->storeContextManagerMock
        ];
        $this->contextManager = new ContextManager($processors);
    }

    public function testSetContextFromSpecification()
    {
        $feedSpecificationMock = $this->createMock(Feed::class);
        $this->customerContextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);
        $this->storeContextManagerMock->expects($this->once())
            ->method('setContextFromSpecification')
            ->with($feedSpecificationMock);

        $this->contextManager->setContextFromSpecification($feedSpecificationMock);
    }

    public function testResetContext()
    {
        $this->customerContextManagerMock->expects($this->once())
            ->method('resetContext');
        $this->storeContextManagerMock->expects($this->once())
            ->method('resetContext');

        $this->contextManager->resetContext();
    }
}
