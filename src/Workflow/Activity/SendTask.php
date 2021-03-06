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

namespace PHPMentors\Workflower\Workflow\Activity;

use PHPMentors\Workflower\Workflow\Operation\OperationalInterface;
use PHPMentors\Workflower\Workflow\Participant\Role;
use PHPMentors\Workflower\Workflow\Resource\MessageInterface;
use PHPMentors\Workflower\Persistence\WorkflowSerializerInterface;

/**
 * @since Class available since Release 1.3.0
 */
class SendTask extends Task implements MessageInterface, OperationalInterface
{
    /**
     * @var int|string
     */
    private $message;

    /**
     * @var int|string
     */
    private $operation;

    /**
     * @param int|string $id
     * @param Role       $role
     * @param int|string $operation
     * @param string     $name
     */
    public function __construct($id, Role $role, $message, $operation, $name = null)
    {
        parent::__construct($id, $role, $name);

        $this->message = $message;
        $this->operation = $operation;
    }

    /**
     * {@inheritdoc}
     */
    public function workflowSerialize(WorkflowSerializerInterface $serializer)
    {
        return $serializer->serialize(array(
            get_parent_class($this) => parent::workflowSerialize($serializer),
            'operation' => $this->operation,
            'message' => $this->message,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function workflowUnserialize(WorkflowSerializerInterface $serializer, $serialized)
    {
        foreach ($serializer->unserialize($serialized) as $name => $value) {
            if ($name == get_parent_class($this)) {
                parent::workflowUnserialize($serializer, $value);
                continue;
            }

            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * {@inheritdoc}
     */
    public function getOperation()
    {
        return $this->operation;
    }
}
