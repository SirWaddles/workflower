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

namespace PHPMentors\Workflower\Persistence;

use PHPMentors\Workflower\Workflow\Workflow;

interface WorkflowSerializerInterface
{
    /**
     * @param mixed $entity
     *
     * @return string
     */
    public function serialize($entity);

    /**
     * @param string $string
     *
     * @return mixed
     */
    public function unserialize($string);

    public function serializeWorkflow($workflow);

    public function unserializeWorkflow($string);
}
