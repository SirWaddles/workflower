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

namespace PHPMentors\Workflower\Workflow;

use PHPMentors\Workflower\Workflow\Activity\ActivityInterface;
use PHPMentors\Workflower\Workflow\Activity\UnexpectedActivityException;
use PHPMentors\Workflower\Workflow\Connection\SequenceFlow;
use PHPMentors\Workflower\Workflow\Element\ConditionalInterface;
use PHPMentors\Workflower\Workflow\Element\ConnectingObjectCollection;
use PHPMentors\Workflower\Workflow\Element\ConnectingObjectInterface;
use PHPMentors\Workflower\Workflow\Element\FlowObjectCollection;
use PHPMentors\Workflower\Workflow\Element\FlowObjectInterface;
use PHPMentors\Workflower\Workflow\Element\TransitionalInterface;
use PHPMentors\Workflower\Workflow\Event\EndEvent;
use PHPMentors\Workflower\Workflow\Event\StartEvent;
use PHPMentors\Workflower\Workflow\Gateway\GatewayInterface;
use PHPMentors\Workflower\Workflow\Operation\OperationalInterface;
use PHPMentors\Workflower\Workflow\Operation\OperationRunnerInterface;
use PHPMentors\Workflower\Workflow\Participant\ParticipantInterface;
use PHPMentors\Workflower\Workflow\Participant\Role;
use PHPMentors\Workflower\Workflow\Participant\RoleCollection;
use PHPMentors\Workflower\Persistence\WorkflowSerializable;
use PHPMentors\Workflower\Persistence\WorkflowSerializerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Workflow implements WorkflowSerializable
{
    const DEFAULT_ROLE_ID = '__ROLE__';

    /**
     * @var string
     */
    private static $STATE_START = '__START__';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $id;

    /**
     * @var ConnectingObjectCollection
     */
    private $connectingObjectCollection;

    /**
     * @var FlowObjectCollection
     */
    private $flowObjectCollection;

    /**
     * @var RoleCollection
     */
    private $roleCollection;

    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \DateTime
     */
    private $endDate;

    /**
     * @var array
     */
    private $processData;

    /**
     * @var ExpressionLanguage
     *
     * @since Property available since Release 1.1.0
     */
    private $expressionLanguage;

    /**
     * @var OperationRunnerInterface
     *
     * @since Property available since Release 1.2.0
     */
    private $operationRunner;

    /**
     * @var string
     */
    private $currentFlowId;

    /**
     * @var array
     */
    private $activityLog;

    /**
     * @param int|string $id
     * @param string     $name
     */
    public function __construct($id, $name)
    {
        $this->connectingObjectCollection = new ConnectingObjectCollection();
        $this->flowObjectCollection = new FlowObjectCollection();
        $this->roleCollection = new RoleCollection();
        $this->name = $name;
        $this->id = $id;
        $this->activityLog = [];
    }

    /**
     * {@inheritdoc}
     */
    public function workflowSerialize(WorkflowSerializerInterface $serializer)
    {
        return $serializer->serialize(array(
            'id' => $this->id,
            'name' => $this->name,
            'connectingObjectCollection' => $this->connectingObjectCollection,
            'flowObjectCollection' => $this->flowObjectCollection,
            'roleCollection' => $this->roleCollection,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'currentFlowId' => $this->currentFlowId,
            'activityLog' => $this->activityLog,
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ConnectingObjectInterface $connectingObject
     */
    public function addConnectingObject(ConnectingObjectInterface $connectingObject)
    {
        $this->connectingObjectCollection->add($connectingObject);
    }

    /**
     * @param FlowObjectInterface $flowObject
     */
    public function addFlowObject(FlowObjectInterface $flowObject)
    {
        $this->flowObjectCollection->add($flowObject);
    }

    /**
     * @param int|string $id
     *
     * @return ConnectingObjectInterface|null
     */
    public function getConnectingObject($id)
    {
        return $this->connectingObjectCollection->get($id);
    }

    /**
     * @param TransitionalInterface $flowObject
     *
     * @return ConnectingObjectCollection
     */
    public function getConnectingObjectCollectionBySource(TransitionalInterface $flowObject)
    {
        return $this->connectingObjectCollection->filterBySource($flowObject);
    }

    /**
     * @param int|string $id
     *
     * @return FlowObjectInterface|null
     */
    public function getFlowObject($id)
    {
        return $this->flowObjectCollection->get($id);
    }

    /**
     * @param Role $role
     */
    public function addRole(Role $role)
    {
        $this->roleCollection->add($role);
    }

    /**
     * @param int|string $id
     *
     * @return bool
     */
    public function hasRole($id)
    {
        return $this->roleCollection->get($id) !== null;
    }

    /**
     * @param int|string $id
     *
     * @return Role
     */
    public function getRole($id)
    {
        return $this->roleCollection->get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return $this->startDate && !$this->endDate;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnded()
    {
        return $this->endDate ? true : false;
    }

    /**
     * @return FlowObjectInterface|null
     */
    public function getCurrentFlowObject()
    {
        return $this->flowObjectCollection->get($this->currentFlowId);
    }

    /**
     * {@inheritdoc}
     */
    public function start(StartEvent $event)
    {
        $this->startDate = new \DateTime();
        $this->currentFlowId = $event->getId();
        $this->selectSequenceFlow($event);
        $this->next();
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     */
    public function allocateWorkItem(ActivityInterface $activity, ParticipantInterface $participant)
    {
        $this->assertParticipantHasRole($activity, $participant);
        $this->assertCurrentFlowObjectIsExpectedActivity($activity);

        $activity->allocate($participant);
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     */
    public function startWorkItem(ActivityInterface $activity, ParticipantInterface $participant)
    {
        $this->assertParticipantHasRole($activity, $participant);
        $this->assertCurrentFlowObjectIsExpectedActivity($activity);

        $activity->start();
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     */
    public function completeWorkItem(ActivityInterface $activity, ParticipantInterface $participant, FlowObjectInterface $target = null)
    {
        $this->assertParticipantHasRole($activity, $participant);
        $this->assertCurrentFlowObjectIsExpectedActivity($activity);

        $activity->complete($participant);
        $this->selectSequenceFlow($activity);
        $this->next($target);
    }

    /**
     * @param array $processData
     */
    public function setProcessData(array $processData)
    {
        $this->processData = $processData;
    }

    /**
     * @return array
     *
     * @since Method available since Release 1.2.0
     */
    public function getProcessData()
    {
        return $this->processData;
    }

    /**
     * @param ExpressionLanguage $expressionLanguage
     *
     * @since Method available since Release 1.1.0
     */
    public function setExpressionLanguage(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param OperationRunnerInterface $operationRunner
     *
     * @since Method available since Release 1.2.0
     */
    public function setOperationRunner(OperationRunnerInterface $operationRunner)
    {
        $this->operationRunner = $operationRunner;
    }

    /**
     * @param EndEvent $event
     */
    private function end(EndEvent $event)
    {
        $this->endDate = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @return ActivityLogCollection
     */
    public function getActivityLog()
    {
        $activityLogCollection = new ActivityLogCollection();
        foreach ($this->activityLog as $log) {
            $flowObject = $this->getFlowObject($log);
            if ($flowObject instanceof ActivityInterface) {
                $activityLogCollection->add(new ActivityLog($flowObject));
            }
        }

        return $activityLogCollection;
    }

    /**
     * @param TransitionalInterface $currentFlowObject
     *
     * @throws SequenceFlowNotSelectedException
     */
    private function selectSequenceFlow(TransitionalInterface $currentFlowObject)
    {
        foreach ($this->connectingObjectCollection->filterBySource($currentFlowObject) as $connectingObject) { /* @var $connectingObject ConnectingObjectInterface */
            if ($connectingObject instanceof SequenceFlow) {
                if (!($currentFlowObject instanceof ConditionalInterface) || $connectingObject->getId() !== $currentFlowObject->getDefaultSequenceFlowId()) {
                    $condition = $connectingObject->getCondition();
                    if ($condition === null) {
                        $selectedSequenceFlow = $connectingObject;
                        break;
                    } else {
                        $expressionLanguage = $this->expressionLanguage ?: new ExpressionLanguage();
                        if ($expressionLanguage->evaluate($condition, $this->processData)) {
                            $selectedSequenceFlow = $connectingObject;
                            break;
                        }
                    }
                }
            }
        }

        if (!isset($selectedSequenceFlow)) {
            if (!($currentFlowObject instanceof ConditionalInterface) || $currentFlowObject->getDefaultSequenceFlowId() === null) {
                throw new SequenceFlowNotSelectedException(sprintf('No sequence flow can be selected on "%s".', $currentFlowObject->getId()));
            }

            $selectedSequenceFlow = $this->connectingObjectCollection->get($currentFlowObject->getDefaultSequenceFlowId());
        }

        // move to next thingy?
        // $this->stateMachine->triggerEvent($selectedSequenceFlow->getDestination()->getId());

        if ($this->getCurrentFlowObject() instanceof GatewayInterface) {
            $gateway = $this->getCurrentFlowObject();
            $this->selectSequenceFlow(/* @var $gateway GatewayInterface */$gateway);
        }
    }

    /**
     * @param ActivityInterface    $activity
     * @param ParticipantInterface $participant
     *
     * @throws AccessDeniedException
     */
    private function assertParticipantHasRole(ActivityInterface $activity, ParticipantInterface $participant)
    {
        if (!$participant->hasRole($activity->getRole()->getId())) {
            throw new AccessDeniedException(sprintf('The participant "%s" does not have the role "%s" that is required to operate the activity "%s".', $participant->getId(), $activity->getRole()->getId(), $activity->getId()));
        }
    }

    /**
     * @param ActivityInterface $activity
     *
     * @throws UnexpectedActivityException
     */
    private function assertCurrentFlowObjectIsExpectedActivity(ActivityInterface $activity)
    {
        if (!$activity->equals($this->getCurrentFlowObject())) {
            throw new UnexpectedActivityException(sprintf('The current flow object is not equal to the expected activity "%s".', $activity->getId()));
        }
    }

    /**
     * @since Method available since Release 1.2.0
     */
    private function next(FlowObjectInterface $target = null)
    {
        $currentFlowObject = $this->getCurrentFlowObject();
        $connections = $this->getConnectingObjectCollectionBySource($currentFlowObject);
        if (count($connections) <= 0) {
            // I dunno
            return;
        }
        $connections = $connections->toArray();
        $this->activityLog[] = $this->currentFlowId;

        $this->currentFlowId = reset($connections)->getDestination()->getId();
        if ($target) {
            foreach ($connections as $connection) {
                if ($connection->getDestination() == $target) {
                    $this->currentFlowId = $target->getId();
                }
            }
        }
        $currentFlowObject = $this->getCurrentFlowObject();

        if ($currentFlowObject instanceof ActivityInterface) {
            $currentFlowObject->createWorkItem();

            if ($currentFlowObject instanceof OperationalInterface) {
                $this->executeOperationalActivity($currentFlowObject);
            }
        } elseif ($currentFlowObject instanceof EndEvent) {
            $this->end($currentFlowObject);
        }
    }

    /**
     * @since Method available since Release 1.2.0
     * @param ActivityInterface $operational
     */
    private function executeOperationalActivity(ActivityInterface $operational)
    {
        $participant = $this->operationRunner->provideParticipant(/* @var $operational OperationalInterface */ $operational, $this);
        $this->allocateWorkItem($operational, $participant);
        $this->startWorkItem($operational, $participant);
        $this->operationRunner->run(/* @var $operational OperationalInterface */ $operational, $this);
        $this->completeWorkItem($operational, $participant);
    }
}
