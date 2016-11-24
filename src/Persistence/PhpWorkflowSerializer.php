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

class PhpWorkflowSerializer implements WorkflowSerializerInterface
{
    public function serializeWorkflow($workflow)
    {
        return json_encode($this->serialize($workflow));
    }

    public function unserializeWorkflow($string)
    {

    }

    public function serialize($data)
    {
        if (is_array($data) || $data instanceof \Traversable) {
            $object = [];
            foreach ($data as $key => $value) {
                $object[$key] = $this->serialize($value);
            }
            return $object;
        }
        if ($data instanceof \DateTime) {
            return $data->format('d-m-Y'); // hurrrr, fix it later
        }
        if ($data instanceof WorkflowSerializable) {
            $inputData = $data->workflowSerialize($this);
            $inputData['$type'] = get_class($data);
            return $inputData;
        }
        if (is_numeric($data) || is_string($data)) return $data;
        if ($data instanceof \Serializable) {
            return serialize($data); // Stagehand
        }
        return $data;
    }

    public function unserialize($string)
    {
        return unserialize($string);
    }
}
