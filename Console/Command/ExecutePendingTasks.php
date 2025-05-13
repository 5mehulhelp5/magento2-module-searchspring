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

namespace SearchSpring\Feed\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Stdlib\DateTime\DateTimeFactory;
use SearchSpring\Feed\Api\ExecutePendingTasksInterfaceFactory;
use SearchSpring\Feed\Model\Metric\CollectorInterface;
use SearchSpring\Feed\Model\Metric\Output\CliOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecutePendingTasks extends Command
{
    const COMMAND_NAME = 'searchspring:feed:execute-pending-tasks';
    /**
     * @var ExecutePendingTasksInterfaceFactory
     */
    private $executePendingTasksFactory;
    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;
    /**
     * @var State
     */
    private $state;
    /**
     * @var CliOutput
     */
    private $cliOutput;
    /**
     * @var CollectorInterface
     */
    private $metricCollector;

    /**
     * ExecutePendingTasks constructor.
     * @param ExecutePendingTasksInterfaceFactory $executePendingTasksFactory
     * @param DateTimeFactory $dateTimeFactory
     * @param State $state
     * @param CliOutput $cliOutput
     * @param CollectorInterface $metricCollector
     * @param string|null $name
     */
    public function __construct(
        ExecutePendingTasksInterfaceFactory $executePendingTasksFactory,
        DateTimeFactory $dateTimeFactory,
        State $state,
        CliOutput $cliOutput,
        CollectorInterface $metricCollector,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->executePendingTasksFactory = $executePendingTasksFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->state = $state;
        $this->cliOutput = $cliOutput;
        $this->metricCollector = $metricCollector;
    }
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Execute Pending Tasks');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_FRONTEND);
        $dateTime = $this->dateTimeFactory->create();
        $currentDate = $dateTime->gmtDate();
        $output->writeln("<info>execution started: $currentDate</info>");
        $this->cliOutput->setOutput($output);
        $this->metricCollector->setOutput($this->cliOutput);
        $this->executePendingTasksFactory->create()->execute();
        $currentDate = $dateTime->gmtDate();
        $output->writeln("<info>execution ended: $currentDate</info>");
    }
}
