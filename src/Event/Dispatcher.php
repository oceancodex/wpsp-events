<?php

namespace WPSPCORE\Events\Event;

use WPSPCORE\Events\Contracts\DispatcherContract;
use WPSPCORE\Events\Contracts\ListenerContract;

/**
 * Dispatcher mô phỏng gần giống Illuminate\Events\Dispatcher:
 * - Hỗ trợ listen nhiều event cùng lúc, wildcard: "user.*"
 * - Hỗ trợ subscribe (class subscriber với phương thức subscribe($events))
 * - Hỗ trợ until (halt on first non-null)
 * - Hỗ trợ class-based event & listener
 * - Hỗ trợ stop propagation khi listener trả về false (tùy theo ngữ nghĩa dùng until/halt)
 */
class Dispatcher implements DispatcherContract {

	private $listeners = [];
	private $wildcards = [];

	/*
	 *
	 */

	public function __construct() {}

	/*
	 *
	 */

	public function listen($events, $listener) {
		foreach ((array)$events as $event) {
			if ($this->isWildcard($event)) {
				$this->wildcards[$event]   ??= [];
				$this->wildcards[$event][] = $listener;
			}
			else {
				$this->listeners[$event]   ??= [];
				$this->listeners[$event][] = $listener;
			}
		}
	}

	public function subscribe($subscriber) {
		$instance = is_string($subscriber) ? new $subscriber() : $subscriber;
		if (method_exists($instance, 'subscribe')) {
			$instance->subscribe($this);
			return;
		}
		// Laravel compatibility style: array mapping
		if (property_exists($instance, 'listen') && is_array($instance->listen)) {
			foreach ($instance->listen as $event => $handlers) {
				foreach ((array)$handlers as $handler) {
					$this->listen($event, is_string($handler) ? [$instance, $handler] : $handler);
				}
			}
		}
	}

	public function hasListeners($eventName) {
		return !empty($this->listeners[$eventName]) || $this->hasWildcardListeners($eventName);
	}

	public function forget($event, $listener = null) {
		if ($this->isWildcard($event)) {
			if (!isset($this->wildcards[$event])) return;
			if ($listener === null) {
				unset($this->wildcards[$event]);
				return;
			}
			$this->wildcards[$event] = array_values(array_filter(
				$this->wildcards[$event],
				fn($l) => $l !== $listener
			));
			if (!$this->wildcards[$event]) unset($this->wildcards[$event]);
			return;
		}

		if (!isset($this->listeners[$event])) return;

		if ($listener === null) {
			unset($this->listeners[$event]);
			return;
		}

		$this->listeners[$event] = array_values(array_filter(
			$this->listeners[$event],
			fn($l) => $l !== $listener
		));
		if (!$this->listeners[$event]) unset($this->listeners[$event]);
	}

	public function flush($event) {
		unset($this->listeners[$event]);
	}

	public function dispatch($event, $payload = [], $halt = false) {
		[$name, $objectPayload] = $this->normalizeEvent($event, $payload);

		$results = [];
		foreach ($this->getListenersForEvent($name) as $listener) {
			$result = $this->callListener($listener, $name, $objectPayload);
			// Laravel behavior: if $halt => return first non-null result
			if ($halt && $result !== null) {
				return $result;
			}
			$results[] = $result;
			// Optional: stop propagation if explicit false
			if ($result === false) {
				break;
			}
		}
		return $halt ? null : $results;
	}

	public function until($event, $payload = []) {
		return $this->dispatch($event, $payload, true);
	}

	/*
	 * Internal helpers
	 */

	private function isWildcard($event) {
		return str_contains($event, '*');
	}

	private function hasWildcardListeners($eventName) {
		foreach ($this->wildcards as $pattern => $list) {
			if ($this->wildcardMatches($eventName, $pattern) && !empty($list)) return true;
		}
		return false;
	}

	private function getListenersForEvent($eventName) {
		$list = $this->listeners[$eventName] ?? [];

		// merge wildcard listeners
		foreach ($this->wildcards as $pattern => $wilds) {
			if ($this->wildcardMatches($eventName, $pattern)) {
				$list = array_merge($list, $wilds);
			}
		}
		return $list;
	}

	private function wildcardMatches($event, $pattern) {
		// convert "user.*" to regex
		$regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i';
		return (bool)preg_match($regex, $event);
	}

	private function normalizeEvent($event, $payload) {
		if (is_object($event)) {
			// Class-based event: use FQCN as name and inject object into payload under 'event'
			$name    = $event::class;
			$payload = array_merge(['event' => $event], $payload);
			return [$name, $payload];
		}
		return [$event, $payload];
	}

	private function callListener($listener, $eventName, $payload) {
		// Listener is reference: [object, method] or [FQCN, method] or closure or FQCN Listener
		if (is_array($listener)) {
			[$classOrObj, $method] = $listener + [null, 'handle'];
			$instance = is_object($classOrObj) ? $classOrObj : new $classOrObj();
			return $this->invokeHandle($instance, $method, $eventName, $payload);
		}

		if (is_string($listener) && class_exists($listener)) {
			$obj = new $listener();
			return $this->invokeHandle($obj, 'handle', $eventName, $payload);
		}

		if (is_callable($listener)) {
			return $listener($eventName, $payload);
		}

		return null;
	}

	private function invokeHandle($obj, $method, $eventName, $payload) {
		// Support ListenerContract
		if ($obj instanceof ListenerContract) {
			// simulate queueable: if shouldQueue() true => run sync here (project chưa có queue)
			return $obj->handle($payload['event'] ?? $eventName, $payload);
		}
		// General callable method
		if (method_exists($obj, $method)) {
			return $obj->{$method}($payload['event'] ?? $eventName, $payload);
		}
		// Fallback to handle()
		if (method_exists($obj, 'handle')) {
			return $obj->handle($payload['event'] ?? $eventName, $payload);
		}
		return null;
	}

}