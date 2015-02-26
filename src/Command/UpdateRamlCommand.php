<?php

namespace Creads\Api2SymfonyBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\Question;

class UpdateRamlCommand extends RamlCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('api2symfony:raml:update')
            ->setDescription('Update Symfony controllers from RAML specification')
            ->addArgument('raml_file', InputArgument::REQUIRED, 'New RAML specification file')
            ->addOption('backwards-compatible', 'bc', InputOption::VALUE_NONE, 'Will create new controller in a sub-namespace to maintain the old API working')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command will convert a RAML specification to Symfony controllers and merge it with

  <info>php %command.full_name% "Acme\\DemoBundle\\Controller" path/to/new_file.raml path/to/old_file.raml [--destination=force/another/destination/path]</info>
EOT
            );
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('raml_file');

        $destination = $this->getDestination($input, $output);

        $namespace = $this->getNamespace($input);

        $newControllers = $this->getContainer()->get('api2symfony.converter.raml')->generate($file, $namespace);

        $oldControllers = $this->getContainer()->get('api2symfony.loader.symfony_controller')->load($file);

        $loader = $this->getContainer()->get('api2symfony.loader.symfony_controller');

        $diffEngine = $this->getContainer()->get('api2symfony.diff_engine');

        $done = array();
        foreach ($controllers as $className => $newController) {

            $oldController = isset($oldControllers[$className])?$oldControllers[$className]:null;

            $diffs = $diffEngine->compare($oldController, $newController);
            if ($diffs->isCreated()) {
                $input->writeln(sprintf('<info>%s</info>: controller created', $className));
            } else if ($diffs->isModified()) {
                $input->writeln(sprintf('<info>%s</info>: controller modified', $className));
                foreach ($diffs as $diff) {
                    //detect methods additions
                    //detect methods modification
                    //detect methods deletion
                }
            } else {
                $input->writeln('<info>%s</info>: no changes in controler');
            }

            $done[] = $className;
        }

        foreach ($controllers as $className => $oldControllers) {
            if (!in_array($className, $done)) {
                $input->writeln('<info>%s</info>: controller deleted');
                $done[] = $className;
            }
        }

        //$this->store($input, $output, $controllers);
    }
}
