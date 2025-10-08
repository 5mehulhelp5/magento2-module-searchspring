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

namespace SearchSpring\Feed\Model;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use SearchSpring\Feed\Api\GetAllTasksInterface;
use SearchSpring\Feed\Api\TaskRepositoryInterface;
use SearchSpring\Feed\Api\Data\TaskInterface;

class GetAllTasks implements GetAllTasksInterface
{
    /**
     * @var TaskRepositoryInterface
     */
    private $taskRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @param TaskRepositoryInterface $taskRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     */
    public function __construct(
        TaskRepositoryInterface $taskRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        SortOrderBuilder $sortOrderBuilder
    ) {
        $this->taskRepository = $taskRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
    }

    /**
     * Get all tasks with optional status filter and pagination
     *
     * @param string $status
     * @param int $currentPage
     * @param int $pageSize
     * @return array
     */
    public function getList(string $status = '', int $currentPage = 1, int $pageSize = 20): array
    {
        // Apply status filter if provided
        if (!empty($status)) {
            $statusFilter = $this->filterBuilder
                ->setField(TaskInterface::STATUS)
                ->setConditionType('eq')
                ->setValue($status)
                ->create();
            $this->searchCriteriaBuilder->addFilters([$statusFilter]);
        }

        // Default ordering - latest first
        $sortOrder = $this->sortOrderBuilder
            ->setField(TaskInterface::CREATED_AT)
            ->setDirection('DESC')
            ->create();
        $this->searchCriteriaBuilder->addSortOrder($sortOrder);

        // Apply pagination
        $this->searchCriteriaBuilder->setPageSize($pageSize);
        $this->searchCriteriaBuilder->setCurrentPage($currentPage);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->taskRepository->getList($searchCriteria);

        $taskItems = [];
        /** @var TaskInterface $task */
        foreach ($searchResults->getItems() as $task) {
            $taskItems[] = [
                'entity_id' => (int)$task->getEntityId(),
                'type' => $task->getType(),
                'status' => $task->getStatus(),
                'payload' => $task->getPayload(),
                'created_at' => $task->getCreatedAt(),
                'started_at' => $task->getStartedAt(),
                'ended_at' => $task->getEndedAt(),
                'product_count' => $task->getProductCount(),
                'file_size' => $task->getFileSize(),
                'error' => $task->getError() ? [
                    'message' => $task->getError()->getMessage() ?? '',
                    'code' => $task->getError()->getCode() ?? ''
                ] : null
            ];
        }

        return [
            'data' => [
                'tasks' => $taskItems,
                'total' => $searchResults->getTotalCount(),
                'currentPage' => $currentPage,
                'pageSize' => $pageSize
            ]
        ];
    }
}
