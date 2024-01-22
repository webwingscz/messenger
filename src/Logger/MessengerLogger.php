<?php declare(strict_types = 1);

namespace Contributte\Messenger\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;

class MessengerLogger extends AbstractLogger
{

	private LoggerInterface $httpLogger;

	private LoggerInterface $consoleLogger;

	public function __construct(LoggerInterface $logger, LoggerInterface $consoleLogger)
	{
		$this->httpLogger = $logger;
		$this->consoleLogger = $consoleLogger;
	}

	/**
	 * @param mixed $level
	 * @param Stringable|string $message
	 * @param mixed[] $context
	 */
	public function log($level, $message, array $context = []): void
	{
		$logger = PHP_SAPI === 'cli' ? $this->consoleLogger : $this->httpLogger;
		$logger->log($level, (string) $message, $context);
	}

}
