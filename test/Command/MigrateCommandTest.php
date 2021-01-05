<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Migration\Command;

use Laminas\Migration\Command\MigrateCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommandTest extends TestCase
{
    public function testExecutionEndsInErrorWhenNonInteractiveAndUserOmitsFlagAcknowledgingDeletion(): void
    {
        /**
         * @var InputInterface|MockObject $input
         * @psalm-var InputInterface&MockObject $input
         */
        $input = $this->createMock(InputInterface::class);
        $input
            ->expects($this->once())
            ->method('getOption')
            ->with($this->equalTo('yes'))
            ->willReturn(null);
        $input
            ->expects($this->atLeastOnce())
            ->method('isInteractive')
            ->willReturn(false);

        /**
         * @var OutputInterface|MockObject $output
         * @psalm-var OutputInterface&MockObject $output
         */
        $output = $this->createMock(OutputInterface::class);
        $output
            ->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('acknowledge this command will remove'));

        $command = new MigrateCommand();

        $this->assertSame(1, $command->run($input, $output));
    }

    public function testExecutionEndsInErrorWhenUserDoesNotConfirmDeletion(): void
    {
        $tempStream = fopen('php://temp', 'wb+');
        fwrite($tempStream, 'no');
        rewind($tempStream);

        /**
         * @var InputInterface|MockObject $input
         * @psalm-var InputInterface&MockObject $input
         */
        $input = $this->createMock(StreamableInputInterface::class);
        $input
            ->expects($this->once())
            ->method('getOption')
            ->with($this->equalTo('yes'))
            ->willReturn(null);
        $input
            ->expects($this->atLeastOnce())
            ->method('isInteractive')
            ->willReturn(true);
        $input
            ->expects($this->atLeastOnce())
            ->method('getStream')
            ->willReturn($tempStream);

        /**
         * @var OutputInterface|MockObject $output
         * @psalm-var OutputInterface&MockObject $output
         */
        $output = $this->createMock(OutputInterface::class);
        $output
            ->expects($this->once())
            ->method('write')
            ->with($this->stringContains('This command REMOVES your composer.lock'));

        $command = new MigrateCommand();
        $command->setHelperSet(new HelperSet([
            new QuestionHelper(),
        ]));

        $this->assertSame(1, $command->run($input, $output));
    }
}
