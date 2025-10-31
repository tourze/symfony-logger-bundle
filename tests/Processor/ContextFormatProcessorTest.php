<?php

namespace Tourze\SymfonyLoggerBundle\Tests\Processor;

use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Tourze\Arrayable\Arrayable;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\AsyncContracts\AsyncMessageInterface;
use Tourze\BacktraceHelper\ContextAwareInterface;
use Tourze\BacktraceHelper\LogDataInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SymfonyLoggerBundle\Processor\ContextFormatProcessor;

/**
 * @internal
 */
#[CoversClass(ContextFormatProcessor::class)]
#[RunTestsInSeparateProcesses]
class ContextFormatProcessorTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // Empty implementation for abstract method
    }

    public function testProcessorCanBeInstantiated(): void
    {
        $processor = self::getService(ContextFormatProcessor::class);
        $this->assertInstanceOf(ContextFormatProcessor::class, $processor);
    }

    public function testInvokeWithEmptyContextReturnsUnchangedRecord(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test message',
            context: []
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        $this->assertEquals($record, $result);
    }

    public function testInvokeWithContextFormatsEachValue(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test message',
            context: ['key1' => 'simple_value', 'key2' => new \stdClass()]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        // The context should be formatted but simple values remain unchanged
        $this->assertEquals('simple_value', $result->context['key1']);
        $this->assertIsObject($result->context['key2']);
        // Verify the record was processed (new instance)
        $this->assertNotSame($record, $result);
    }

    public function testFormatsJsonSerializable(): void
    {
        $jsonSerializable = $this->createMock(\JsonSerializable::class);
        $jsonSerializable->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn(['serialized' => 'data'])
        ;

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test',
            context: ['obj' => $jsonSerializable]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        $this->assertEquals(['serialized' => 'data'], $result->context['obj']);
    }

    public function testFormatsLogDataInterface(): void
    {
        $logData = $this->createMock(LogDataInterface::class);
        $logData->expects($this->once())
            ->method('generateLogData')
            ->willReturn(['log' => 'data'])
        ;

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test',
            context: ['obj' => $logData]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        $this->assertEquals(['log' => 'data'], $result->context['obj']);
    }

    public function testFormatsLogDataInterfaceWithException(): void
    {
        $logData = $this->createMock(LogDataInterface::class);
        $logData->expects($this->once())
            ->method('generateLogData')
            ->willThrowException(new \RuntimeException('Test exception'))
        ;

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test',
            context: ['obj' => $logData]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        // Should return null when exception occurs
        $this->assertNull($result->context['obj']);
    }

    public function testFormatsAsyncMessageInterface(): void
    {
        $asyncMessage = $this->createMock(AsyncMessageInterface::class);

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test',
            context: ['obj' => $asyncMessage]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        // The async message should be processed somehow - exact format depends on normalizer implementation
        $this->assertIsArray($result->context['obj']);
        $this->assertArrayHasKey('_class', $result->context['obj']);
        $this->assertEquals(get_class($asyncMessage), $result->context['obj']['_class']);
    }

    public function testFormatsPlainArrayInterface(): void
    {
        $plainArray = $this->createMock(PlainArrayInterface::class);
        $plainArray->expects($this->once())
            ->method('retrievePlainArray')
            ->willReturn(['plain' => 'array'])
        ;

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test',
            context: ['obj' => $plainArray]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        $this->assertEquals(['plain' => 'array'], $result->context['obj']);
    }

    public function testFormatsArrayable(): void
    {
        $arrayable = $this->createMock(Arrayable::class);
        $arrayable->expects($this->once())
            ->method('toArray')
            ->willReturn(['array' => 'data'])
        ;

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'test',
            context: ['obj' => $arrayable]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        $expected = [
            'array' => 'data',
            '_class' => get_class($arrayable),
        ];
        $this->assertEquals($expected, $result->context['obj']);
    }

    public function testFormatsThrowableReturnsFormattedException(): void
    {
        $exception = new \RuntimeException('Test exception');

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'test',
            context: ['exception' => $exception]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        $this->assertIsString($result->context['exception']);
        $this->assertStringContainsString('RuntimeException', $result->context['exception']);
    }

    public function testFormatsNotNormalizableValueException(): void
    {
        $exception = new NotNormalizableValueException('Test message');

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'test',
            context: ['exception' => $exception]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        $this->assertIsArray($result->context['exception']);
        $this->assertArrayHasKey('exception', $result->context['exception']);
        $this->assertArrayHasKey('currentType', $result->context['exception']);
        $this->assertArrayHasKey('expectedTypes', $result->context['exception']);
        $this->assertArrayHasKey('path', $result->context['exception']);
        $this->assertArrayHasKey('useMessageForUser', $result->context['exception']);
    }

    public function testFormatsContextAwareException(): void
    {
        // Create an anonymous class that implements both interfaces
        $exception = new class('Test message') extends \RuntimeException implements ContextAwareInterface {
            public function getContext(): array
            {
                return ['context_key' => 'context_value'];
            }

            public function setContext(array $context): void
            {
                // Not needed for test
            }
        };

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'test',
            context: ['exception' => $exception]
        );

        $processor = self::getService(ContextFormatProcessor::class);
        $result = ($processor)($record);

        $this->assertIsArray($result->context['exception']);
        $this->assertArrayHasKey('exception', $result->context['exception']);
        $this->assertArrayHasKey('context', $result->context['exception']);
        $this->assertEquals(['context_key' => 'context_value'], $result->context['exception']['context']);
    }
}
