<?php

declare(strict_types=1);

namespace AdgoalCommon\ErrorReporting\Tests\Unit\Infrastructure\Event\Publisher;

use AdgoalCommon\Base\Domain\Exception\LoggerException;
use AdgoalCommon\ErrorReporting\Application\Processor\ErrorEventProcessor;
use AdgoalCommon\ErrorReporting\Domain\Exception\EventListenerException;
use AdgoalCommon\ErrorReporting\Infrastructure\Event\Publisher\CommandErrorPublisher;
use Enqueue\Client\ProducerInterface;
use League\Event\AbstractListener;
use League\Tactician\CommandEvents\Event\CommandFailed;
use Mockery;
use PHPStan\Testing\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * Class CommandErrorPublisherTest.
 *
 * @category Tests\Unit\Infrastructure\Event\Publisher
 */
class CommandErrorPublisherTest extends TestCase
{
    /**
     * @test
     *
     * @group unit
     *
     * @dataProvider \AdgoalCommon\ErrorReporting\Tests\Unit\DataProvider\ErrorReportingDataProvider::getErrorData()
     *
     * @param string $serializableException
     *
     * @throws EventListenerException
     * @throws ReflectionException
     * @throws LoggerException
     */
    public function handleTest(string $serializableException): void
    {
        $producerMock = Mockery::mock(ProducerInterface::class);
        $producerMock
            ->shouldReceive('sendEvent')
            ->with(ErrorEventProcessor::QUEUE_COMMAND_FAILED_ERROR, $serializableException)
            ->times(1);

        $commandErrorPublisher = new CommandErrorPublisher($producerMock);
        self::assertInstanceOf(AbstractListener::class, $commandErrorPublisher);

        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock
            ->shouldReceive('debug')
            ->times(0)
            ->andReturn('');
        $commandErrorPublisher->setLogger($loggerMock);

        $eventMock = Mockery::mock(CommandFailed::class);
        $eventMock
            ->shouldReceive('getException')
            ->times(1)
            ->andReturn(unserialize($serializableException));

        $commandErrorPublisher->handle($eventMock);
    }
}
