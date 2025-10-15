<?php

namespace WPSPCORE\Events\Event;

final class EventServiceProvider {

	public static function boot($map, $dispatcher) {
		foreach ($map as $event => $listeners) {
			foreach ((array)$listeners as $listener) {
				$dispatcher->listen($event, $listener);
			}
		}
	}

}