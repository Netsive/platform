<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\EventListener;

use Oro\Bundle\BatchBundle\EventListener\LoggerSubscriber;
use Oro\Bundle\BatchBundle\Event\EventInterface;

/**
 * Test related class
 */
class LoggerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->logger = $this->getLoggerMock();
        $this->subscriber = new LoggerSubscriber($this->logger);
    }

    public function testIsAnEventSubscriber()
    {
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\EventSubscriberInterface', $this->subscriber);
    }

    public function testSubscribedEvents()
    {
        $this->assertEquals(
            array(
                EventInterface::BEFORE_JOB_EXECUTION       => 'beforeJobExecution',
                EventInterface::JOB_EXECUTION_STOPPED      => 'jobExecutionStopped',
                EventInterface::JOB_EXECUTION_INTERRUPTED  => 'jobExecutionInterrupted',
                EventInterface::JOB_EXECUTION_FATAL_ERROR  => 'jobExecutionFatalError',
                EventInterface::BEFORE_JOB_STATUS_UPGRADE  => 'beforeJobStatusUpgrade',
                EventInterface::BEFORE_STEP_EXECUTION      => 'beforeStepExecution',
                EventInterface::STEP_EXECUTION_SUCCEEDED   => 'stepExecutionSucceeded',
                EventInterface::STEP_EXECUTION_INTERRUPTED => 'stepExecutionInterrupted',
                EventInterface::STEP_EXECUTION_ERRORED     => 'stepExecutionErrored',
                EventInterface::STEP_EXECUTION_COMPLETED   => 'stepExecutionCompleted',
                EventInterface::INVALID_ITEM               => 'invalidItem',
            ),
            LoggerSubscriber::getSubscribedEvents()
        );
    }

    public function testBeforeJobExecution()
    {
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('Job execution starting'));

        $event = $this->getJobExecutionEventMock();
        $this->subscriber->beforeJobExecution($event);
    }

    public function testJobExecutionStopped()
    {
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('Job execution was stopped'));

        $event = $this->getJobExecutionEventMock();
        $this->subscriber->jobExecutionStopped($event);
    }

    public function testJobExecutionInterrupted()
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringStartsWith('Encountered interruption executing job'));

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('Full exception'));

        $jobExecution = $this->getJobExecutionMock();
        $event = $this->getJobExecutionEventMock($jobExecution);
        $this->subscriber->jobExecutionInterrupted($event);
    }

    public function testJobExecutionFatalError()
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Encountered fatal error executing job'));

        $jobExecution = $this->getJobExecutionMock();
        $event = $this->getJobExecutionEventMock($jobExecution);
        $this->subscriber->jobExecutionFatalError($event);
    }

    public function testBeforeJobStatusUpgrade()
    {
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('Upgrading JobExecution status'));

        $event = $this->getJobExecutionEventMock();
        $this->subscriber->beforeJobStatusUpgrade($event);
    }

    public function testBeforeStepExecution()
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringStartsWith('Step execution starting'));

        $event = $this->getStepExecutionEventMock();
        $this->subscriber->beforeStepExecution($event);
    }

    public function testStepExecutionSucceeded()
    {
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with('Step execution success: id= 1');

        $stepExecution = $this->getStepExecutionMock();
        $stepExecution->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));

        $event = $this->getStepExecutionEventMock($stepExecution);

        $this->subscriber->stepExecutionSucceeded($event);
    }

    public function testStepExecutionInterrupted()
    {
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with($this->stringStartsWith('Encountered interruption executing step'));

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('Full exception'));

        $stepExecution = $this->getStepExecutionMock();
        $event = $this->getStepExecutionEventMock($stepExecution);
        $this->subscriber->stepExecutionInterrupted($event);
    }

    public function testStepExecutionErrored()
    {
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringStartsWith('Encountered an error executing the step'));

        $stepExecution = $this->getStepExecutionMock();
        $event = $this->getStepExecutionEventMock($stepExecution);
        $this->subscriber->stepExecutionErrored($event);
    }

    public function testStepExecutionCompleted()
    {
        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringStartsWith('Step execution complete'));

        $stepExecution = $this->getStepExecutionMock();
        $event = $this->getStepExecutionEventMock($stepExecution);
        $this->subscriber->stepExecutionCompleted($event);
    }

    public function testInvalidItemExecution()
    {
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'The Oro\Bundle\BatchBundle\Item\ItemReaderInterface was unable ' .
                'to handle the following item: [foo => bar] (REASON: This is a valid reason.)'
            );

        $event = $this->getInvalidItemEvent(
            'Oro\Bundle\BatchBundle\Item\ItemReaderInterface',
            'This is a valid reason.',
            array('foo' => 'bar')
        );
        $this->subscriber->invalidItem($event);
    }

    private function getLoggerMock()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }

    private function getJobExecutionEventMock($jobExecution = null)
    {
        $event = $this
            ->getMockBuilder('Oro\Bundle\BatchBundle\Event\JobExecutionEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getJobExecution')
            ->will($this->returnValue($jobExecution));

        return $event;
    }

    private function getStepExecutionEventMock($stepExecution = null)
    {
        $event = $this
            ->getMockBuilder('Oro\Bundle\BatchBundle\Event\StepExecutionEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getStepExecution')
            ->will($this->returnValue($stepExecution));

        return $event;
    }

    private function getJobExecutionMock()
    {
        return $this
            ->getMockBuilder('Oro\Bundle\BatchBundle\Entity\JobExecution')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getStepExecutionMock()
    {
        return $this
            ->getMockBuilder('Oro\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $class
     * @param string $reason
     * @param array  $item
     *
     * @return PHPUnit_Framework_MockObject_MockObjec
     */
    private function getInvalidItemEvent($class, $reason, $item)
    {
        $invalidItem = $this->getMockBuilder('Oro\Bundle\BatchBundle\Event\InvalidItemEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $invalidItem->expects($this->any())
            ->method('getClass')
            ->will($this->returnValue($class));

        $invalidItem->expects($this->any())
            ->method('getReason')
            ->will($this->returnValue($reason));

        $invalidItem->expects($this->any())
            ->method('getItem')
            ->will($this->returnValue($item));

        return $invalidItem;
    }
}
