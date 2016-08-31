<?php

namespace SAREhub\Component\Worker\Command\Standard;


use SAREhub\Component\Worker\Command\WorkerCommand;

class KillWorkerCommand implements WorkerCommand {
	
	const NAME = 'command.worker.kill';
	
	protected $uuid;
	
	public function __construct($uuid) {
		$this->uuid = $uuid;
	}
	
	public function getUuid() {
		return $this->uuid;
	}
	
	public function getName() {
		return self::NAME;
	}
}