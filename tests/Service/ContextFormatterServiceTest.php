<?php

namespace Tourze\SymfonyLoggerBundle\Tests\Service;

use Doctrine\ORM\OptimisticLockException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Tourze\Arrayable\Arrayable;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\AsyncContracts\AsyncMessageInterface;
use Tourze\BacktraceHelper\ContextAwareInterface;
use Tourze\BacktraceHelper\LogDataInterface;
use Tourze\SymfonyLoggerBundle\Service\ContextFormatterService;

/**
 * @internal
 */
#[CoversClass(ContextFormatterService::class)]
class ContextFormatterServiceTest extends TestCase
{
    public function testFormatLineWithJsonSerializable(): void
    {
        $jsonSerializable = $this->createMock(\JsonSerializable::class);
        $jsonSerializable->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn(['serialized' => 'data'])
        ;

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($jsonSerializable);

        $this->assertEquals(['serialized' => 'data'], $result);
    }

    public function testFormatLineWithLogDataInterface(): void
    {
        $logData = $this->createMock(LogDataInterface::class);
        $logData->expects($this->once())
            ->method('generateLogData')
            ->willReturn(['log' => 'data'])
        ;

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($logData);

        $this->assertEquals(['log' => 'data'], $result);
    }

    public function testFormatLineWithLogDataInterfaceException(): void
    {
        $logData = $this->createMock(LogDataInterface::class);
        $logData->expects($this->once())
            ->method('generateLogData')
            ->willThrowException(new \RuntimeException('Test exception'))
        ;

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($logData);

        $this->assertNull($result);
    }

    public function testFormatLineWithAsyncMessageInterface(): void
    {
        $asyncMessage = $this->createMock(AsyncMessageInterface::class);
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('normalize')
            ->with($asyncMessage, 'array')
            ->willReturn(['normalized' => 'data'])
        ;

        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($asyncMessage);

        $expected = [
            'normalized' => 'data',
            '_class' => get_class($asyncMessage),
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFormatLineWithAsyncMessageInterfaceException(): void
    {
        $asyncMessage = $this->createMock(AsyncMessageInterface::class);
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('normalize')
            ->with($asyncMessage, 'array')
            ->willThrowException(new \RuntimeException('Normalization failed'))
        ;

        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($asyncMessage);

        $expected = [
            '_class' => get_class($asyncMessage),
            '_formatException' => 'Normalization failed',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFormatLineWithAsyncMessageInterfaceNonArrayResult(): void
    {
        $asyncMessage = $this->createMock(AsyncMessageInterface::class);
        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->expects($this->once())
            ->method('normalize')
            ->with($asyncMessage, 'array')
            ->willReturn('not an array')
        ;

        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($asyncMessage);

        $expected = [
            '_class' => get_class($asyncMessage),
            '_formatException' => 'Normalizer did not return array',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFormatLineWithPlainArrayInterface(): void
    {
        $plainArray = $this->createMock(PlainArrayInterface::class);
        $plainArray->expects($this->once())
            ->method('retrievePlainArray')
            ->willReturn(['plain' => 'array'])
        ;

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($plainArray);

        $this->assertEquals(['plain' => 'array'], $result);
    }

    public function testFormatLineWithPlainArrayInterfaceException(): void
    {
        $plainArray = $this->createMock(PlainArrayInterface::class);
        $plainArray->expects($this->once())
            ->method('retrievePlainArray')
            ->willThrowException(new \RuntimeException('Array retrieval failed'))
        ;

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($plainArray);

        $expected = [
            '_class' => get_class($plainArray),
            '_formatException' => 'Array retrieval failed',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFormatLineWithArrayable(): void
    {
        $arrayable = $this->createMock(Arrayable::class);
        $arrayable->expects($this->once())
            ->method('toArray')
            ->willReturn(['array' => 'data'])
        ;

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($arrayable);

        $expected = [
            'array' => 'data',
            '_class' => get_class($arrayable),
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFormatLineWithArrayableException(): void
    {
        $arrayable = $this->createMock(Arrayable::class);
        $arrayable->expects($this->once())
            ->method('toArray')
            ->willThrowException(new \RuntimeException('toArray failed'))
        ;

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($arrayable);

        $expected = [
            '_class' => get_class($arrayable),
            '_formatException' => 'toArray failed',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testFormatLineWithOptimisticLockExceptionWithStringableEntity(): void
    {
        $entity = $this->createMock(\Stringable::class);
        $entity->expects($this->once())
            ->method('__toString')
            ->willReturn('TestEntity')
        ;

        $exception = $this->createMock(OptimisticLockException::class);
        $exception->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity)
        ;

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($exception);

        $this->assertIsString($result);
        $this->assertStringContainsString('处理实体[TestEntity]时发生乐观锁异常', $result);
    }

    public function testFormatLineWithOptimisticLockExceptionWithNonStringableEntity(): void
    {
        $entity = new \stdClass();

        $exception = $this->createMock(OptimisticLockException::class);
        $exception->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity)
        ;

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($exception);

        $this->assertIsString($result);
        // Should contain exception information
        $this->assertStringContainsString('OptimisticLockException', $result);
    }

    public function testFormatLineWithNotNormalizableValueException(): void
    {
        $exception = new NotNormalizableValueException('Test message');

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($exception);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('exception', $result);
        $this->assertArrayHasKey('currentType', $result);
        $this->assertArrayHasKey('expectedTypes', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('useMessageForUser', $result);
    }

    public function testFormatLineWithContextAwareException(): void
    {
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

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($exception);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('exception', $result);
        $this->assertArrayHasKey('context', $result);
        $this->assertEquals(['context_key' => 'context_value'], $result['context']);
    }

    public function testFormatLineWithRegularThrowable(): void
    {
        $exception = new \RuntimeException('Test exception');

        $normalizer = $this->createMock(NormalizerInterface::class);
        $service = new ContextFormatterService($normalizer);

        $result = $service->formatLine($exception);

        $this->assertIsString($result);
        $this->assertStringContainsString('RuntimeException', $result);
        $this->assertStringContainsString('Test exception', $result);
    }

    public function testFormatLineWithRegularValue(): void
    {
        $values = [
            'string' => 'test',
            'integer' => 123,
            'float' => 12.34,
            'boolean' => true,
            'null' => null,
            'array' => ['test' => 'value'],
            'object' => new \stdClass(),
        ];

        foreach ($values as $key => $value) {
            $normalizer = $this->createMock(NormalizerInterface::class);
            $service = new ContextFormatterService($normalizer);

            $result = $service->formatLine($value);
            $this->assertEquals($value, $result, "Failed for type: {$key}");
        }
    }
}
