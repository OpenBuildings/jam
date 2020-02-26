<?php

use Symfony\Component\DomCrawler\Crawler;
use PHPUnit\Framework\Exception;

trait Trait_DomSearch
{
	protected function assertSelectEquals(
		string $selector,
		string $content,
		$count,
		string $searchString,
		string $message = ''
	)
	{
		$crawler = new Crawler($searchString);
		$crawler = $crawler->filter($selector);

		if (!empty($content)) {
			$crawler = $crawler->reduce(
				function (Crawler $node) use ($content) {
					if ($content === '') {
						return $node->text() === '';
					}
					return (bool) preg_match('/' . $content . '/i', $node->text());
				}
			);
		}

		$found = count($crawler);

		if (is_bool($count)) {
			$this->assertEquals($found > 0, $count, $message);
		} else if (is_numeric($count)) {
			$this->assertEquals($found, $count, $message);
		} else {
			throw new Exception('Invalid count format');
		}
	}

	protected function assertSelectCount(
		string $selector,
		int $count,
		string $html
	)
	{
		$crawler = new Crawler($html);

		$crawler = $crawler->filter($selector);
		$this->assertEquals($crawler->count(), $count);
	}
}
