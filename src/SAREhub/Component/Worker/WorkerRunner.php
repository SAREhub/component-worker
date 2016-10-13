<?php

namespace SAREhub\Component\Worker;

use SAREhub\Component\Worker\Command\Command;
use SAREhub\Component\Worker\Command\CommandInput;
use SAREhub\Component\Worker\Command\CommandReply;
use SAREhub\Component\Worker\Command\CommandReplyOutput;
use SAREhub\Component\Worker\Service\ServiceSupport;

/**
 * Helper class for run worker.
 */
class WorkerRunner extends ServiceSupport {
	
	/**
	 * @var Worker
	 * */
	private $worker;
	
	/**
	 * @var CommandInput
	 */
	private $commandInput;
	
	private $commandReplyOutput;
	
	protected function __construct() {
	}
	
	/**
	 * @return WorkerRunner
	 */
	public static function newInstance() {
		return new self();
	}
	
	/**
	 * @param Worker $worker
	 * @return $this
	 */
	public function withWorker(Worker $worker) {
		$this->worker = $worker;
		return $this;
	}
	
	/**
	 * @param CommandInput $input
	 * @return $this
	 */
	public function withCommandInput(CommandInput $input) {
		$this->commandInput = $input;
		return $this;
	}
	
	/**
	 * @param CommandReplyOutput $output
	 * @return $this
	 */
	public function withCommandReplyOutput(CommandReplyOutput $output) {
		$this->commandReplyOutput = $output;
		return $this;
	}
	
	
	/**
	 * Contains custom worker start logic
	 * @throws \Exception When something was wrong.
	 */
	protected function doStart() {
		try {
			$this->getWorker()->start();
		} catch (\Exception $e) {
			$this->getLogger()->error($e);
		}
	}
	
	/**
	 * Contains custom worker tick logic
	 * @throws \Exception When something was wrong.
	 */
	protected function doTick() {
		try {
			$this->checkCommand();
			$this->getWorker()->tick();
		} catch (\Exception $e) {
			$this->getLogger()->error($e);
		}
	}
	
	private function checkCommand() {
		if ($command = $this->getCommandInput()->getNext()) {
			$runner = $this;
			$replyCallback = function (Command $command, CommandReply $reply) use ($runner) {
				$runner->getCommandReplyOutput()->send($reply, true);
				$runner->getLogger()->info('sending reply', ['reply' => $reply]);
			};
			$this->processCommand($command, $replyCallback);
		}
	}
	
	private function processCommand(Command $command, callable $replyCallback) {
		$this->getLogger()->info('process command', ['command' => (string)$command]);
		try {
			$this->onCommand($command, $replyCallback);
		} catch (\Exception $e) {
			$this->onProcessCommandException($command, $e, $replyCallback);
		}
	}
	
	private function onCommand(Command $command, callable $replyCallback) {
		switch ($command->getName()) {
			case WorkerCommands::STOP:
				$this->onStopCommand($command, $replyCallback);
				break;
			default:
				$this->worker->processCommand($command, $replyCallback);
		}
	}
	
	private function onStopCommand(Command $command, callable $replyCallback) {
		$this->getWorker()->stop();
		$replyCallback($command, CommandReply::success($command->getCorrelationId(), 'stopped'));
	}
	
	private function onProcessCommandException(Command $command, \Exception $e, callable $replyCallback) {
		$this->getLogger()->error($e);
		$replyCallback($command, CommandReply::error(
		  $command->getCorrelationId(),
		  'exception when execute command', [
		  'exceptionMessage' => $e->getMessage()
		]));
	}
	
	/**
	 * Contains custom worker stop logic
	 * @throws \Exception When something was wrong.
	 */
	protected function doStop() {
		try {
			$this->getWorker()->stop();
			$this->getCommandInput()->close();
			$this->getCommandReplyOutput()->close();
		} catch (\Exception $e) {
			$this->getLogger()->error($e);
		}
	}
	
	/**
	 * @return bool
	 */
	public function isRunning() {
		return !$this->getWorker()->isStopped();
	}
	
	/**
	 * @return Worker
	 */
	public function getWorker() {
		return $this->worker;
	}
	
	/**
	 * @return CommandInput
	 */
	public function getCommandInput() {
		return $this->commandInput;
	}
	
	/**
	 * @return CommandReplyOutput
	 */
	public function getCommandReplyOutput() {
		return $this->commandReplyOutput;
	}
}