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
        parent::configure();

        $this
            ->setName('api2symfony:raml:generate')
            ->setDescription('Generate Symfony controllers from RAML')
            ->addArgument('raml_file', InputArgument::REQUIRED, 'RAML specification file')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command will convert a RAML specification to Symfony controllers.

  <info>php %command.full_name% "Acme\\DemoBundle\\Controller" path/to/file.raml [--destination=force/another/destination/path]</info>
EOT
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('raml_file');

        $destination = $this->getDestination($input, $output);

        $namespace = $this->getNamespace($input);

        $controllers = $this->getContainer()->get('api2symfony.converter.raml')->generate($file, $namespace);

        $this->store($input, $output, $controllers);
    }
}
