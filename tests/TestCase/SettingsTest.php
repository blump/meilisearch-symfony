<?php

declare(strict_types=1);

namespace MeiliSearch\Bundle\Test\TestCase;

use MeiliSearch\Bundle\SearchService;
use MeiliSearch\Bundle\Test\BaseTest;
use MeiliSearch\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class SettingsTest.
 */
class SettingsTest extends BaseTest
{
    public const DEFAULT_RANKING_RULES
        = [
            'typo',
            'words',
            'proximity',
            'attribute',
            'wordsPosition',
            'exactness',
        ];

    protected Client $client;
    protected string $indexName;
    protected Application $application;
    protected SearchService $searchService;

    /**
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->searchService = $this->get('search.service');
        $this->client = $this->get('search.client');
        $this->indexName = 'posts';

        $this->application = new Application(self::$kernel);
        $this->refreshDb($this->application);
    }

    public function testGetDefaultSettings()
    {
        $primaryKey = 'ObjectID';
        $settingA = $this->client->getOrCreateIndex('indexA')->getSettings();
        $settingB = $this->client->getOrCreateIndex('indexB', ['primaryKey' => $primaryKey])->getSettings();

        $this->assertEquals(self::DEFAULT_RANKING_RULES, $settingA['rankingRules']);
        $this->assertNull($settingA['distinctAttribute']);
        $this->assertIsArray($settingA['searchableAttributes']);
        $this->assertEquals(['*'], $settingA['searchableAttributes']);
        $this->assertIsArray($settingA['displayedAttributes']);
        $this->assertEquals(['*'], $settingA['displayedAttributes']);
        $this->assertIsArray($settingA['stopWords']);
        $this->assertEmpty($settingA['stopWords']);
        $this->assertIsArray($settingA['synonyms']);
        $this->assertEmpty($settingA['synonyms']);

        $this->assertEquals(self::DEFAULT_RANKING_RULES, $settingB['rankingRules']);
        $this->assertNull($settingB['distinctAttribute']);
        $this->assertEquals(['*'], $settingB['searchableAttributes']);
        $this->assertEquals(['*'], $settingB['displayedAttributes']);
        $this->assertIsArray($settingB['stopWords']);
        $this->assertEmpty($settingB['stopWords']);
        $this->assertIsArray($settingB['synonyms']);
        $this->assertEmpty($settingB['synonyms']);
    }

    public function testUpdateSettings()
    {
        $command = $this->application->find('meili:import');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'command' => $command->getName(),
                '--indices' => 'posts',
                '--update-settings' => true,
            ]
        );

        $settings = $this->client->index('sf_phpunit__posts')->getSettings();

        $this->assertNotEmpty($settings['stopWords']);
        $this->assertEquals(['a', 'an', 'the'], $settings['stopWords']);

        $this->assertNotEmpty($settings['attributesForFaceting']);
        $this->assertEquals(['title', 'publishedAt'], $settings['attributesForFaceting']);
    }
}
