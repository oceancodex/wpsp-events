<?php
namespace WPSPCORE\Events\Contracts;

interface DispatcherContract {
	public function listen(string $event, callable|string $listener): void;
	public function forget(string $event, callable|string|null $listener = null): void;
	public function dispatch(string $event, array $payload = []): void;
}