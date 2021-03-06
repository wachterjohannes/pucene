<?php

namespace Pucene\Tests\TestBundle\Command;

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\DoctrineCacheStorage;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadWikidataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('test:download:wikidata')->addArgument(
                'file',
                InputArgument::OPTIONAL,
                '',
                __DIR__ . '/../../../app/data.json'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $content = json_decode(file_get_contents($input->getArgument('file')), true);

        $stack = HandlerStack::create();
        $stack->push(
            new CacheMiddleware(
                new GreedyCacheStrategy(
                    new DoctrineCacheStorage(
                        new FilesystemCache(__DIR__ . '/../../../var/wikidata')
                    ),
                    31104000
                )
            ),
            'cache'
        );
        $client = new Client(['base_uri' => 'https://www.wikidata.org', 'handler' => $stack]);

        $progressBar = new ProgressBar($output, count($content));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $enabledCounter = 0;

        $newData = [];
        foreach ($content as $id => $item) {
            try {
                $response = $client->get('/wiki/Special:EntityData/' . $id . '.json');
            } catch (\Exception $e) {
                continue;
            }

            $response = json_decode($response->getBody()->getContents(), true);
            $response = array_values($response['entities']);
            $response = reset($response);

            $title = $item['title'];
            if (array_key_exists('en', $response['labels'])) {
                $title = $response['labels']['en']['value'];
            }

            $description = $item['description'];
            if (array_key_exists('en', $response['descriptions'])) {
                $description = $response['descriptions']['en']['value'];
            }

            $aliases = [];
            if (array_key_exists('en', $response['aliases'])) {
                $aliases = array_values(
                    array_filter(
                        array_map(
                            function (array $item) {
                                return trim(preg_replace('/[[:^print:]]/', '', $item['value']));
                            },
                            $response['aliases']['en']
                        )
                    )
                );
            }

            $claims = [];
            foreach ($response['claims'] as $claim) {
                $claim = array_values($claim)[0];
                $claims[] = [
                    'id' => $claim['id'],
                    'mainsnak' => [
                        'property' => $claim['mainsnak']['property'],
                        'datatype' => $claim['mainsnak']['datatype'],
                    ],
                ];
            }

            $newData[$response['id']] = [
                'title' => $title,
                'description' => $description,
                'aliases' => $aliases,
                'claims' => $claims,
                'modified' => $response['modified'],
                'pageId' => (int) $response['pageid'],
                'seed' => rand(0, 100) / 100.0,
                'enabled' => $enabledCounter < 100 && 1 === rand(0, 1) ? true : false,
            ];

            if ($newData[$response['id']]['enabled']) {
                ++$enabledCounter;
            }

            $progressBar->advance();
        }

        file_put_contents($input->getArgument('file'), json_encode($newData, JSON_PRETTY_PRINT));

        $progressBar->finish();
    }

    public function hasEmojis($string)
    {
        preg_match('/[\x{1F600}-\x{1F64F}]/u', $string, $matches_emo);

        return !empty($matches_emo[0]) ? true : false;
    }
}
