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

namespace PHPMentors\Workflower\Workflow\Participant;

use PHPMentors\Workflower\Persistence\WorkflowSerializable;
use PHPMentors\Workflower\Persistence\WorkflowSerializerInterface;

class RoleCollection implements WorkflowSerializable
{
    /**
     * @var array
     */
    private $roles = array();

    /**
     * {@inheritdoc}
     */
    public function workflowSerialize(WorkflowSerializerInterface $serializer)
    {
        return $serializer->serialize(array(
            'roles' => $this->roles,
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
    public function add(Role $entity)
    {
        $this->roles[$entity->getId()] = $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @return Role|null
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->roles)) {
            return $this->roles[$key];
        } else {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Role $entity)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->roles);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->roles);
    }

    /*
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->roles;
    }
}
