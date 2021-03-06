<?php
/*
 * Copyright (c) KUBO Atsuhiro <kubo@iteman.jp> and contributors,
 * All rights reserved.
 *
 * This file is part of Workflower.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace PHPMentors\Workflower\Workflow\Element;

use PHPMentors\Workflower\Persistence\WorkflowSerializable;
use PHPMentors\Workflower\Persistence\WorkflowSerializerInterface;

class FlowObjectCollection implements WorkflowSerializable, \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    private $flowObjects = array();

    /**
     * {@inheritdoc}
     */
    public function workflowSerialize(WorkflowSerializerInterface $serializer)
    {
        return $serializer->serialize(array(
            'flowObjects' => $this->flowObjects,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function workflowUnserialize(WorkflowSerializerInterface $serializer, $serialized)
    {
        foreach ($serializer->unserialize($serialized) as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function add(FlowObjectInterface $entity)
    {
        $this->flowObjects[$entity->getId()] = $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @return FlowObjectInterface|null
     */
    public function get($key)
    {
        if (!array_key_exists($key, $this->flowObjects)) {
            return null;
        }

        return $this->flowObjects[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function remove(FlowObjectInterface $entity)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->flowObjects);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->flowObjects);
    }

    /*
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->flowObjects;
    }
}
