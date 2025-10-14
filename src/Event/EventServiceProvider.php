<?php
namespace WPSPCORE\Event;

final class EventServiceProvider {
	/**
	 * Boot listeners map: ['event.name' => [ListenerClass::class, callable, ...], ...]
	 */
	public static function boot(array $map): void {
		$dispatcher = Dispatcher::instance();
		foreach ($map as $event => $listeners) {
			foreach ((array) $listeners as $listener) {
				$dispatcher->listen($event, $listener);
			}
		}
	}
}