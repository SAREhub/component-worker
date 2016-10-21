<?php

use PHPUnit\Framework\TestCase;
use SAREhub\Component\Worker\Cli\Cli;
use SAREhub\Component\Worker\Cli\StartWorkerCommand;
use SAREhub\Component\Worker\Command\CommandReply;
use SAREhub\Component\Worker\Command\CommandRequest;
use SAREhub\Component\Worker\Command\CommandService;
use SAREhub\Component\Worker\Manager\ManagerCommands;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class StartWorkerCommandTest extends TestCase {
	
	private $cli;
	
	/**
	 * @var CommandTester
	 */
	private $commandTester;
	private $commandService;
	
	protected function setUp() {
		parent::setUp();
		
		$this->cli = $this->createMock(Cli::class);
		$this->commandService = $this->createMock(CommandService::class);
		
		$this->cli->method('getCommandService')->willReturn($this->commandService);
		$application = new Application();
		$application->add(StartWorkerCommand::newInstance()
		  ->withCli($this->cli));
		
		$this->commandTester = new CommandTester($application->find('start-worker'));
	}
	
	public function testExecuteThenCommandServiceProcess() {
		$this->commandService->expects($this->once())->method('process');
		$this->commandTester->execute(['manager' => 'm', 'worker' => 'w']);
	}
	
	public function testExecuteThenCommandRequestTopicIsManagerId() {
		$this->assertCommandRequest(function (CommandRequest $request) {
			return $request->getTopic() === 'm';
		});
		$this->commandTester->execute(['manager' => 'm', 'worker' => 'w']);
	}
	
	public function testExecuteThenCommandRequestNotAsync() {
		$this->assertCommandRequest(function (CommandRequest $request) {
			return !$request->isAsync();
		});
		$this->commandTester->execute(['manager' => 'm', 'worker' => 'w']);
	}
	
	public function testExecuteThenCommandRequestCommandManagerStartWorker() {
		$this->assertCommandRequest(function (CommandRequest $request) {
			return $request->getCommand()->getName() === ManagerCommands::START;
		});
		$this->commandTester->execute(['manager' => 'm', 'worker' => 'w']);
	}
	
	public function testExecuteWhenReplyThenCommandRequestReplyCallbackOutput() {
		$reply = CommandReply::success('1', 'm');
		$this->assertCommandRequest(function (CommandRequest $request) use ($reply) {
			($request->getReplyCallback())($request, $reply);
			return true;
		});
		
		$this->commandTester->execute(['manager' => 'm', 'worker' => 'w']);
		$this->assertContains('manager reply: '.$reply->toJson(), $this->commandTester->getDisplay());
		
	}
	
	private function assertCommandRequest(callable $callback) {
		$this->commandService->expects($this->once())->method('process')
		  ->with($this->callback($callback));
	}
}
