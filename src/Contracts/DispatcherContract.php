<?php

namespace WPSPCORE\Events\Contracts;

interface DispatcherContract {

	public function listen($events, $listener);

	public function subscribe($subscriber);

	public function hasListeners($eventName);

	public function forget($event, $listener = null);

	public function flush($event);

	/**
	 * Dispatch the given event and call the listeners.
	 * Return last non-null listener result (like Laravel::until) if $halt = true,
	 * otherwise return array of listener results.
	 *
	 * @param string|object $event
	 * @param array         $payload
	 * @param bool          $halt
	 *
	 * @return mixed
	 */
	public function dispatch($event, $payload = [], $halt = false);

	/**
	 * Dispatch and return first non-null result from listeners, stop further propagation.
	 */
	public function until($event, $payload = []);

}