<?php
namespace WPSP\Event;

use WPSPCORE\Events\Contracts\DispatcherContract;
use WPSPCORE\Events\Contracts\ListenerContract;

final class Dispatcher implements DispatcherContract {
	private static ?self $instance = null;

	/** @var array<string, array<int, callable|string>> */
	private array $listeners = [];

	private function __construct() {}

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function listen(string $event, callable|string $listener): void {
		$this->listeners[$event] ??= [];
		$this->listeners[$event][] = $listener;
	}

	public function forget(string $event, callable|string|null $listener = null): void {
		if (!isset($this->listeners[$event])) return;

		if ($listener === null) {
			unset($this->listeners[$event]);
			return;
		}

		$this->listeners[$event] = array_values(array_filter(
			$this->listeners[$event],
			fn($l) => $l !== $listener
		));

		if (!$this->listeners[$event]) {
			unset($this->listeners[$event]);
		}
	}

	public function dispatch(string $event, array $payload = []): void {
		$bin = $this->listeners[$event] ?? [];
		foreach ($bin as $listener) {
			if (is_string($listener) && class_exists($listener)) {
				$obj = new $listener();
				if ($obj instanceof ListenerContract) {
					$obj->handle($event, $payload);
					continue;
				}
			}
			if (is_callable($listener)) {
				$listener($event, $payload);
			}
		}
	}
}