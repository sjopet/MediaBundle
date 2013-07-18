<?php

namespace Symfony\Cmf\Bundle\MediaBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Cmf\Bundle\MediaBundle\Doctrine\Phpcr\Directory;

/**
 * @author Sjoerd Peters <sjoerd.peters@gmail.com>
 */
class FixturesCommand extends ContainerAwareCommand
{
    /** @var ContainerInterface $container */
    protected $container;

    /** @var \Doctrine\ODM\PHPCR\DocumentManager $dm */
    protected $dm;

    /** @var \Jackalope\Session $phpcrSession */
    protected $phpcrSession;

    protected function configure()
    {
        $this
            ->setName('cmf:media:generate')
            ->addOption('force', null, InputOption::VALUE_NONE, 'If set, the commmand will overwrite any existing data')
            ->setDescription('This command will generate some test data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container = $this->getContainer();
        $this->dm = $this->container->get('doctrine_phpcr.odm.document_manager');
        $this->phpcrSession = $this->container->get('doctrine_phpcr.default_session');

        $mediaRoot = '/cms/media';

//        $force = $input->getOption('force');
//        if (true === $force) {
            if($this->phpcrSession->nodeExists($mediaRoot)){
                $this->phpcrSession->removeItem($mediaRoot);
            }
//        }

        $dir = new Directory();
        $dir->setId($mediaRoot);
        $this->dm->persist($dir);
        $this->dm->flush($dir);

        $output->writeln(sprintf('Created directory with path %s', $mediaRoot));
    }

}