<?php declare(strict_types = 1);

namespace Contributte\Messenger\Logger;

use Psr\Log\AbstractLogger;
use Stringable;

class BufferLogger extends AbstractLogger
{

	/** @var array<array{message: string|Stringable, context?: array<mixed>}> */
	private array $logs = [];

	/**
	 * @param mixed $level
	 * @param string|Stringable $message
	 * @param mixed[] $context
	 */
	public function log($level, $message, array $context = []): void
	{
		$this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
	}

	/**
	 * @return array<array{message: string|Stringable, context?: array<mixed>}>
	 */
	public function obtain(): array
	{
		return $this->logs;
	}

}
