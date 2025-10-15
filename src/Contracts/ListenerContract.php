<?php

namespace WPSPCORE\Events\Contracts;

interface ListenerContract {

	/**
	 * Handle the event.
	 *
	 * @param string|object $event
	 * @param array         $payload
	 *
	 * @return mixed
	 */
	public function handle($event, $payload = []);

	/**
	 * Indicates if this listener should be queued (simulate Laravel queueable).
	 * Implementations may ignore or handle synchronously depending on project setup.
	 */
	public function shouldQueue(): bool;

}