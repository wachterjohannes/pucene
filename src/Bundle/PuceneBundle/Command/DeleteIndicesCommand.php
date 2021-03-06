<?php

namespace Pucene\Bundle\PuceneBundle\Command;

use Pucene\Component\Client\ClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteIndicesCommand extends Command
{
    /**
     * @var array
     */
    private $indices;

    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(string $name, array $indices, ClientInterface $client)
    {
        parent::__construct($name);

        $this->indices = $indices;
        $this->client = $client;
    }

    protected function configure()
    {
        $this->addArgument('name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        if ($name) {
            return $this->dropIndex($name);
        }

        foreach ($this->indices as $name => $config) {
            $this->dropIndex($name);
        }
    }

    private function dropIndex(string $name): void
    {
        $this->client->delete($name);
    }
}
