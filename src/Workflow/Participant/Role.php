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

class Role implements WorkflowSerializable
{
    /**
     * @var int|string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @param int|string $id
     * @param string     $name
     */
    public function __construct($id, $name = null)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function workflowSerialize(WorkflowSerializerInterface $serializer)
    {
        return $serializer->serialize(array(
            'id' => $this->id,
            'name' => $this->name,
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
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
