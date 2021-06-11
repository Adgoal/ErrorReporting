<?php

declare(strict_types=1);

namespace AdgoalCommon\ErrorReporting\Tests\Unit\Infrastructure\Repository\Processor;

use AdgoalCommon\Base\Domain\Exception\LoggerException;
use AdgoalCommon\ErrorReporting\Domain\Repository\Processor\ErrorReportingProcessorInterface;
use AdgoalCommon\ErrorReporting\Infrastructure\Repository\Processor\ErrorReportingProcessor;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class ErrorReportingProcessorTest.
 *
 * @category Tests\Unit\Infrastructure\Repository\Processor
 */
class ErrorReportingProcessorTest extends TestCase
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
     * @throws LoggerException
     */
    public function handleExceptionTest(string $serializableException): void
    {
        $loggerMock = Mockery::mock(LoggerInterface::class);
        $loggerMock
            ->shouldReceive('emergency')
            ->times(1);
        $loggerMock
            ->shouldReceive('critical')
            ->times(1);
        $loggerMock
            ->shouldReceive('error')
            ->times(1);

        $errorReportingProcessor = new ErrorReportingProcessor();
        $errorReportingProcessor->setLogger($loggerMock);
        self::assertInstanceOf(ErrorReportingProcessorInterface::class, $errorReportingProcessor);

        $errorReportingProcessor->handleException(unserialize($serializableException));
    }
}
