<?php

namespace SearchSpring\Feed\Model;

use SearchSpring\Feed\Api\Data\CustomersDataInterface;

class CustomersData implements CustomersDataInterface
{
    private $id;
    private $email;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param $value string
     */
    public function setId(string $value)
    {
        $this->id = $value;
    }

    /**
     * @param $value string
     */
    public function setEmail(string $value)
    {
        $this->email = $value;
    }
}