<?php

declare(strict_types=1);

namespace AdgoalCommon\ErrorReporting\Tests\Unit\Application\Processor;

use AdgoalCommon\ErrorReporting\Application\Processor\ErrorEventProcessor;
use AdgoalCommon\ErrorReporting\Domain\Repository\Processor\ErrorReportingProcessorInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Exception;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Mockery;
use PHPStan\Testing\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class ErrorEventConsumerTest.
 *
 * @category Tests\Unit\Infrastructure\Event\Consumer
 */
class ErrorEventConsumerTest extends TestCase
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
     * @throws Exception
     */
    public function processTest(string $serializableException): void
    {
        $errorReportingProcessorMock = Mockery::mock(ErrorReportingProcessorInterface::class);
        $errorReportingProcessorMock
            ->shouldReceive('handleException')
            ->times(1);

        $errorEventConsumer = new ErrorEventProcessor($errorReportingProcessorMock);
        self::assertInstanceOf(Processor::class, $errorEventConsumer);
        self::assertInstanceOf(TopicSubscriberInterface::class, $errorEventConsumer);

        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock
            ->shouldReceive('debug')
            ->times(0)
            ->andReturn('');
        $errorEventConsumer->setLogger($loggerMock);

        $messageMock = Mockery::mock(Message::class);
        $messageMock
            ->shouldReceive('getBody')
            ->times(1)
            ->andReturn($serializableException);

        $contextMock = Mockery::mock(Context::class);

        self::assertSame(Processor::ACK, $errorEventConsumer->process($messageMock, $contextMock));
    }
}
