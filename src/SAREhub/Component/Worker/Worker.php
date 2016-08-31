<?php

namespace SAREhub\Component\Worker;

/**
 * Represents Worker instance.
 */
interface Worker {
	
	/**
	 * Executed once on worker start.
	 */
	public function onStart();
	
	/**
	 * Executed on every tick, must implements worker logic code.
	 */
	public function onTick();
	
	/**
	 * Executed when worker was stopped.
	 */
	public function onStop();
	
	/**
	 * @return string
	 */
	public function getUuid();
}