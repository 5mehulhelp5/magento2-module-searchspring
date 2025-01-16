<?php
declare(strict_types=1);

namespace SearchSpring\Feed\Api;
interface RequestItemInterface
{
    const DATA_ID = 'id';
    const DATA_DESCRIPTION = 'description';

    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getDescription();

    /**
     * @param int $id
     * @return $this
     */
    public function setId(int $id);

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description);
}
