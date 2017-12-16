<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Orderexport
 */

/**
 * Copyright © 2016 Amasty. All rights reserved.
 */

namespace Amasty\Orderexport\Model;

/**
 * Class OrderExportList
 *
 * @package Amasty\Orderexport\Model
 */
class OrderExportList implements \Iterator
{
    /**
     * current cursor position
     * @var int
     */
    private $position = 0;

    /**
     * array for iteration
     * @var array
     */
    private $array;

    /**
     * OrderExportList constructor.
     *
     * @param ResourceModel\Profiles\Collection $collection
     * @param array                             $data
     */
    public function __construct(
        \Amasty\Orderexport\Model\ResourceModel\Profiles\Collection $collection,
        array $data = []
    ) {
        $this->position = 0;
        $defaultOptions = isset($data['defaultOptions']) ? array_values($data['defaultOptions']) : [];
        $this->array = array_merge($defaultOptions, $collection->toExportButtonOptionArray());
    }

    /**
     * reset array position
     */
    public function rewind() {
        $this->position = 0;
    }

    /**
     * current item
     * @return mixed
     */
    public function current() {
        return $this->array[$this->position];
    }

    /**
     * current key
     * @return int
     */
    public function key() {
        return $this->position;
    }

    /**
     * set cursor to next element
     */
    public function next() {
        ++$this->position;
    }

    /**
     * @return bool
     */
    public function valid() {
        return isset($this->array[$this->position]);
    }
}
