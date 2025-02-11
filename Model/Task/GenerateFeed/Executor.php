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

namespace SearchSpring\Feed\Model\Task\GenerateFeed;

use Magento\Framework\Exception\CouldNotSaveException;
use SearchSpring\Feed\Api\Data\TaskInterface;
use SearchSpring\Feed\Api\GenerateFeedInterface;
use SearchSpring\Feed\Exception\GenericException;
use SearchSpring\Feed\Model\Feed\SpecificationBuilderInterface;
use SearchSpring\Feed\Model\Task\ExecutorInterface;

class Executor implements ExecutorInterface
{
    /**
     * @var SpecificationBuilderInterface
     */
    private $specificationBuilder;
    /**
     * @var GenerateFeedInterface
     */
    private $generateFeed;

    /**
     * Executor constructor.
     * @param SpecificationBuilderInterface $specificationBuilder
     * @param GenerateFeedInterface $generateFeed
     */
    public function __construct(
        SpecificationBuilderInterface $specificationBuilder,
        GenerateFeedInterface $generateFeed
    ) {
        $this->specificationBuilder = $specificationBuilder;
        $this->generateFeed = $generateFeed;
    }

    /**
     * @param TaskInterface $task
     * @return void
     * @throws GenericException
     */
    public function execute(TaskInterface $task)
    {
        $specification = $this->specificationBuilder->build($task->getPayload());
        $this->generateFeed->execute($specification, $task->getEntityId());
    }
}
