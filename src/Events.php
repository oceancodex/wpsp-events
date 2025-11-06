<?php

namespace WPSPCORE\Events;

use WPSPCORE\Base\BaseInstances;
use WPSPCORE\Events\Event\EventServiceProvider;

class Events extends BaseInstances {

	private $dispatcher;

	/*
	 *
	 */

	public function boot() {
		$map        = $this->funcs->_config('events');
		$dispatcher = $this->dispatcher();
		if (is_array($map) && class_exists('WPSPCORE\Events\Event\EventServiceProvider')) {
			EventServiceProvider::boot($map, $dispatcher);
		}
		return $this;
	}

	/**
	 * @return \WPSPCORE\Events\Event\Dispatcher|null
	 */
	public function dispatcher() {
		if (!$this->dispatcher && class_exists('WPSPCORE\Events\Event\Dispatcher')) {
			$this->dispatcher = new \WPSPCORE\Events\Event\Dispatcher();
		}
		return $this->dispatcher;
	}

}