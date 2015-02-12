<?php

namespace Creads\Api2SymfonyBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\Question;

class GenerateRamlCommand extends RamlCommand
{
    protected function configure()
    {
        $this
            ->setName('api2symfony:raml:generate')
            ->setDescription('Generate Symfony controllers from RAML')
            ->addArgument('raml_file', InputArgument::REQUIRED, 'RAML specification file')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command will convert a RAML specification to Symfony controllers.

  <info>php %command.full_name% path/to/file.raml Base/Namespace/Of/YourBundle [--destination=force/another/destination/path]</info>
EOT
            );
        ;

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('raml_file');

        $destination = $this->getDestination($input, $output);

        $namespace = $this->getNamespace($input) . '\Controller';

        $dialog = $this->getHelperSet()->get('question');

        $controllers = $this->getContainer()->get('api2symfony.converter.raml')->convert($file, $namespace);

        foreach ($controllers as $controller) {
            if ($this->getContainer()->get('api2symfony.dumper')->exists($controller, $destination)) {
                if ($input->isInteractive()) {
                    $output->writeln(sprintf('* <comment>%s</comment>: <error>EXISTS</error>', $controller->getClassName()));
                }

                $answer = $dialog->ask(
                    $input,
                    $output,
                    new Question(sprintf('<question>Overwrite this file (previous file will be renamed with extension .old) ?</question> [Y]/n ', $controller->getClassName()), false)
                );
                if ($answer === 'n' || $answer === 'N') {
                    continue;
                }
                $output->writeln(sprintf('* <comment>%s</comment>: <info>OVERWRITTEN</info>', $controller->getClassName()));
            } else {
                $output->writeln(sprintf('* <comment>%s</comment>: <info>CREATED</info>', $controller->getClassName()));
            }

            $file = $this->getContainer()->get('api2symfony.dumper')->dump($controller, $destination);
        }
    }
}
