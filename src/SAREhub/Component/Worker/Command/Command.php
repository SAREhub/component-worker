<?php

namespace SAREhub\Component\Worker\Command;

/**
 * Base interface for all commands
 */
interface Command {
	
	/**
	 * @return string
	 */
	public function getName();
}