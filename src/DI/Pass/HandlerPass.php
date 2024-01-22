<?php declare(strict_types = 1);

namespace Contributte\Messenger\DI\Pass;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Messenger\DI\Utils\Reflector;
use Contributte\Messenger\Exception\LogicalException;
use Nette\DI\Definitions\ServiceDefinition;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class HandlerPass extends AbstractPass
{

	/**
	 * Register services
	 */
	public function loadPassConfiguration(): void
	{
		// Nothing to register
	}

	/**
	 * Decorate services
	 */
	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();

		// Attach message handlers to bus
		foreach ($config->bus as $busName => $busConfig) {
			$handlers = [];

			// Collect all message handlers from DIC
			$serviceHandlers = $this->getMessageHandlers();

			// Iterate all found handlers
			foreach ($serviceHandlers as $serviceName) {
				$serviceDef = $builder->getDefinition($serviceName);
				/** @var class-string $serviceClass */
				$serviceClass = $serviceDef->getType();

				// Ensure handler class exists
				try {
					new ReflectionClass($serviceClass);
				} catch (ReflectionException $e) {
					throw new LogicalException(sprintf('Handler "%s" class not found', $serviceClass), 0, $e);
				}

				// Drain service tag
				$tag = (array) $serviceDef->getTag(MessengerExtension::HANDLER_TAG);
				/** @var array{bus: string|null, alias: string|null, method: string|null, handles: string|null, priority: numeric|null, from_transport: string|null} $tagOptions */
				$tagOptions = [
					'bus' => $tag['bus'] ?? null,
					'alias' => $tag['alias'] ?? null,
					'method' => $tag['method'] ?? null,
					'handles' => $tag['handles'] ?? null,
					'priority' => $tag['priority'] ?? null,
					'from_transport' => $tag['from_transport'] ?? null,
				];

				// Complete final options
				$options = [
					'service' => $serviceName,
					'bus' => $tagOptions['bus'] ?? $busName,
					'alias' => $tagOptions['alias'] ?? null,
					'method' => $tagOptions['method'] ?? '__invoke',
					'handles' => $tagOptions['handles'] ?? null,
					'priority' => $tagOptions['priority'] ?? 0,
					'from_transport' => $tagOptions['from_transport'] ?? null,
				];

				// Autodetect handled message
				if (!isset($options['handles'])) {
					$options['handles'] = Reflector::getMessageHandlerMessage($serviceClass, $options);
				}

				// If handler is not for current bus, then skip it
				if (($tagOptions['bus'] ?? $busName) !== $busName) {
					continue;
				}

				$handlers[$options['handles']][$options['priority']][] = $options;
			}

			// Sort handlers by priority
			foreach ($handlers as $message => $handlersByPriority) {
				krsort($handlersByPriority);
				$handlers[$message] = array_merge(...$handlersByPriority);
			}

			// Replace handlers in bus
			/** @var ServiceDefinition $busHandlerLocator */
			$busHandlerLocator = $builder->getDefinition($this->prefix(sprintf('bus.%s.locator', $busName)));
			$busHandlerLocator->setArgument(0, $handlers);
		}
	}

	/**
	 * @return array<int, string>
	 */
	private function getMessageHandlers(): array
	{
		$builder = $this->getContainerBuilder();

		// Find all handlers
		$serviceHandlers = [];
		$serviceHandlers = array_merge($serviceHandlers, array_keys($builder->findByTag(MessengerExtension::HANDLER_TAG)));
		$serviceHandlers = array_merge($serviceHandlers, array_keys($builder->findByType(MessageHandlerInterface::class)));

		// Clean duplicates
		return array_unique($serviceHandlers);
	}

}
