<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Utils;

use Contributte\Messenger\Exception\LogicalException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

final class Reflector
{

	/**
	 * @param class-string $class
	 * @param array{method: string} $options
	 */
	public static function getMessageHandlerMessage(string $class, array $options): string
	{
		try {
			$rc = new ReflectionClass($class);
		} catch (ReflectionException $e) {
			throw new LogicalException(sprintf('Handler "%s" class not found', $class), 0, $e);
		}

		try {
			$rcMethod = $rc->getMethod($options['method']);
		} catch (ReflectionException $e) {
			throw new LogicalException(sprintf('Handler must have "%s::%s()" method.', $class, $options['method']));
		}

		if ($rcMethod->getNumberOfParameters() !== 1) {
			throw new LogicalException(sprintf('Only one parameter is allowed in "%s::%s()."', $class, $options['method']));
		}

		/** @var ReflectionNamedType|null $type */
		$type = $rcMethod->getParameters()[0]->getType();

		if ($type === null) {
			throw new LogicalException(sprintf('Cannot detect parameter type for "%s::%s()."', $class, $options['method']));
		}

		return $type->getName();
	}

}
