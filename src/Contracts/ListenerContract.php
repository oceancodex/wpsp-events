<?php
namespace WPSPCORE\Events\Contracts;

interface ListenerContract {
	public function handle(string $event, array $payload = []): void;
}