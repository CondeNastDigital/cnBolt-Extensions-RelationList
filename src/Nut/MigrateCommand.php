<?php
namespace Bolt\Extension\CND\RelationList\Nut;

use Bolt\Application;
use Bolt\Extension\CND\RelationList\Entity\Item;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{

    protected $app;

    public function __construct(Application $app = null)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure()
    {
        $this
            ->setName('relationlist:migrate')
            ->setDescription('Migrate an old relationlist field to new format')
            ->addArgument(
                'contenttype',
                InputArgument::REQUIRED,
                'contenttype to convert'
            )
            ->addArgument(
                'field',
                InputArgument::REQUIRED,
                'field to convert'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $contenttype = $input->getArgument('contenttype');
        $field = $input->getArgument('field');

        /* @var Repository $storage */
        $storage = $this->app['storage']->getRepository($contenttype);
        $items = $storage->findAll();

        $count = 0;
        /* @var \Bolt\Storage\Entity\Content $item */
        foreach ($items as $item){

            $value = json_decode($item->get($field), true);

            if(!$value || isset($value['items']))
                continue;

            $value = $this->app['cnd.relationlist.legacy']->convertValue($value);
            $value = json_encode($value);

            $item->set($field, $value);
            $storage->save($item);

            $count ++;
        }

        $output->writeln('Migration finished - '.($count+0).' records updated');
    }
}