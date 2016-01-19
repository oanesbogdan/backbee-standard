<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee Standard Edition.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee Standard Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee Standard Edition. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Standard\Command;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Article\Article;
use BackBee\ClassContent\Article\ArticleContainer;
use BackBee\ClassContent\Media\Image;
use BackBee\ClassContent\Text\Paragraph;
use BackBee\Console\AbstractCommand;
use BackBee\NestedNode\Builder\PageBuilder;

use Badcow\LoremIpsum\Generator;

use Jarvis\Math\Bijective;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Eric Chau <eric.chau@lp-digital.fr>
 */
class FakeDataCommand extends AbstractCommand
{
    const ARTICLE_LIMIT = 500;
    const CATEGORY_LIMIT = 50;

    const CATEGORY_FLUSH_EVERY = 2;
    const ARTICLE_FLUSH_EVERY = 10;

    const RANDOM_IMAGE_URL = 'http://lorempixel.com/600/300/';

    private $app;
    private $entyMgr;
    private $generator;
    private $pageBuilder;
    private $articleCount;
    private $categoryCount;
    private $hydrateImage;
    private $input;
    private $output;
    private $bijective;
    private $extraContents = [
        'BackBee\ClassContent\Article\Quote',
        'BackBee\ClassContent\Media\Iframe',
        'BackBee\ClassContent\Media\Image',
        'BackBee\ClassContent\Social\Facebook',
        'BackBee\ClassContent\Social\Twitter',
    ];
    private $units = ['b', 'kb', 'mb', 'gb', 'tb', 'pb'];

    protected function configure()
    {
        $this
            ->setName('fake:data:generate')
            ->setDescription('Generate fake categories and articles')
            ->addOption('article-limit', null, InputOption::VALUE_OPTIONAL, '', self::ARTICLE_LIMIT)
            ->addOption('category-limit', null, InputOption::VALUE_OPTIONAL, '', self::CATEGORY_LIMIT)
            ->addOption('no-image', null, InputOption::VALUE_NONE)
            ->addOption('tmp-dir', null, InputOption::VALUE_OPTIONAL)
        ;
    }

    protected function init(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->app = $this->getContainer()->get('bbapp');
        $this->entyMgr = $this->app->getEntityManager();
        $this->pageBuilder = $this->app->getContainer()->get('pagebuilder');
        $this->generator = new Generator();
        $this->bijective = new Bijective();
        $this->hydrateImage = $input->getOption('no-image') ? false : true;
        $this->entyMgr->getConfiguration()->setSQLLogger(null);

        $this->categoryCount = (int) $this->entyMgr->getRepository('BackBee\NestedNode\Page')
            ->createQueryBuilder('p')
            ->select('count(p._uid)')
            ->join('p._section', 's')
            ->where('s._site = :site')
            ->setParameter('site', $this->getSite())
            ->andWhere('p._layout = :layout')
            ->setParameter('layout', $this->getLayout())
            ->getQuery()
            ->getSingleScalarResult()
        ;

        $this->articleCount = (int) $this->entyMgr->getRepository('BackBee\NestedNode\Page')
            ->createQueryBuilder('p')
            ->select('count(p._uid)')
            ->join('p._section', 's')
            ->where('s._site = :site')
            ->setParameter('site', $this->getSite())
            ->andWhere('p._layout = :layout')
            ->setParameter('layout', $this->getLayout('Article'))
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $this;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this
            ->init($input, $output)
            ->runCategoryProcess()
            ->runArticleProcess()
        ;
    }

    protected function runCategoryProcess()
    {
        $itemCount = 0;
        $createdCount = 0;
        $starttime = microtime(true);
        for ($i = $this->categoryCount; $i < $this->input->getOption('category-limit'); $i++) {
            $this->entyMgr->persist($this->generateRandomCategoryPage());
            $itemCount++;
            $createdCount++;

            if (self::CATEGORY_FLUSH_EVERY === $itemCount) {
                $this->entyMgr->flush();
                $this->categoryCount += $itemCount;
                $itemCount = 0;
            }
        }

        if (0 < $itemCount) {
            $this->entyMgr->flush();
            $this->categoryCount += $itemCount;
            $itemCount = 0;
        }

        $duration = number_format(microtime(true) - $starttime, 3);
        $this->output->writeln("<info>$createdCount categories created in $duration s.</info>");

        return $this;
    }

    protected function runArticleProcess()
    {
        $itemCount = 0;
        $createdCount = 0;
        $starttime = microtime(true);
        $limit = $this->input->getOption('article-limit');
        for ($i = 0; $i < $limit; $i++) {
            $this->entyMgr->persist($this->generateRandomArticlePage());
            $itemCount++;
            $createdCount++;

            if (self::ARTICLE_FLUSH_EVERY === $itemCount) {
                $this->entyMgr->flush();
                $this->entyMgr->clear();
                gc_collect_cycles();
                gc_disable();
                gc_enable();

                $memoryUsage = $this->formatBytes(memory_get_usage(true));
                $this->articleCount += $itemCount;
                $this->output->writeln("  flush of $itemCount articles ($createdCount/$limit - $memoryUsage)");
                $itemCount = 0;
            }
        }

        if (0 < $itemCount) {
            $this->entyMgr->flush();
            $this->articleCount += $itemCount;
            $itemCount = 0;
        }

        $duration = number_format(microtime(true) - $starttime, 3);
        $this->output->writeln("<info>$createdCount articles created in $duration s.</info>");
    }

    protected function getRandomParent()
    {
        $max = $this->categoryCount - 1;
        $max = $max > 0 ? $max : 0;
        $layout = $this->getLayout($max ? null : 'Home');

        $qb = $this->entyMgr->getRepository('BackBee\NestedNode\Page')->createQueryBuilder('p')
            ->join('p._section', 's')
            ->where('s._site = :site')
            ->setParameter('site', $this->getSite())
            ->andWhere('p._layout = :layout')
            ->setParameter('layout', $layout)
        ;

        if (0 < $max) {
            $qb->setFirstResult(mt_rand(0, $max))->setMaxResults(1);
        }

        return $qb->getQuery()->getSingleResult();
    }

    protected function generateRandomCategoryPage()
    {
        $title = ucfirst(implode(' ', $this->generator->getRandomWords(mt_rand(1, 3))));

        $page = $this->pageBuilder
            ->setTitle($title)
            ->setSite($this->getSite())
            ->setLayout($this->getLayout())
            ->setParent($this->getRandomParent())
            ->putOnlineAndVisible()
            ->setPersistMode(PageBuilder::PERSIST_AS_FIRST_CHILD)
            ->getPage()
        ;

        $this->enableContent($page->getContentSet()->first()->first());

        return $page;
    }

    protected function generateRandomArticlePage()
    {
        $title = ucfirst(implode(' ', $this->generator->getRandomWords(mt_rand(3, 8))));

        $page = $this->pageBuilder
            ->setTitle($title)
            ->setSite($this->getSite())
            ->setLayout($this->getLayout('Article'))
            ->setParent($this->getRandomParent())
            ->putOnlineAndHidden()
            ->setPersistMode(PageBuilder::PERSIST_AS_FIRST_CHILD)
            ->getPage()
        ;

        $this->randomPopulateArticle($page->getContentSet()->first()->first(), $title);

        return $page;
    }

    protected function getLayout($label = null)
    {
        return $this->entyMgr->getRepository('BackBee\Site\Layout')->findOneBy([
            '_label' => $label ?: 'Category',
            '_site'  => $this->getSite(),
        ]);
    }

    protected function getSite()
    {
        return $this->entyMgr->getRepository('BackBee\Site\Site')->findOneBy([]);
    }

    protected function randomPopulateArticle(Article $article, $title)
    {
        $this->enableContent($article);

        $article->title->value = $title;
        $article->abstract->value = implode(' ', $this->generator->getSentences(mt_rand(1, 3)));

        $this->populateRandomImage($article->image);
        $article->setParam('rendermode_autoblock', ['block-right']);

        for ($i = 0; $i < mt_rand(2, 8); $i++) {
            $article->body->push($this->generateRandomParagraph());

            if (0 === mt_rand() % 2) {
                $content = new $this->extraContents[mt_rand(0, 4)];
                $this->enableContent($content);
                if ($content instanceof Image) {
                    $this->populateRandomImage($content);
                }

                $article->body->push($content);
            }
        }

        return $this;
    }

    /**
     * Generates and returns Paragraph with random body value.
     *
     * @return Paragraph
     */
    protected function generateRandomParagraph()
    {
        $content = new Paragraph();

        $this->enableContent($content);
        $content->body->value = '<p>' . implode(' ', $this->generator->getParagraphs(1)) . '</p>';

        return $content;
    }

    /**
     * Populates the provided Media\Image with random image, title, copyright
     * and description.
     *
     * @param  Image  $image
     * @return self
     */
    protected function populateRandomImage(Image $image)
    {
        if (!$this->hydrateImage) {
            return $this;
        }

        $content = null;
        if (function_exists('curl_version')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, self::RANDOM_IMAGE_URL);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $content = curl_exec($curl);
            curl_close($curl);
        } else {
            $content = @file_get_contents(self::RANDOM_IMAGE_URL);
        }

        if (false == $content) {
            $this->output->writeln(
                '<error>Cannot hydrate images, file_get_contents() and curl extension are not availables.</error>'
            );

            return $this;
        }

        $tmpDir = $this->input->getOption('tmp-dir') ?: $this->app->getTemporaryDir();
        $destFilename = $tmpDir . '/' . $this->bijective->encode(mt_rand(1000, 10000000)) . '.jpg';

        file_put_contents($destFilename, $content);

        $image->title->value = ucfirst(implode(' ', $this->generator->getRandomWords(mt_rand(2, 4))));
        $image->copyrights->value = '@ ' . ucwords(implode(' ', $this->generator->getRandomWords(mt_rand(1, 2))));
        $image->description->value = implode(' ', $this->generator->getSentences(1));
        $image->image->path = $destFilename;
        $image->image->originalname = basename($destFilename);

        $this
            ->entyMgr
            ->getRepository('BackBee\ClassContent\Element\Image')
            ->setDirectories($this->app)
            ->setTemporaryDir($tmpDir)
            ->updateFile($image->image, basename($destFilename))
        ;

        return $image;
    }

    /**
     * Formats bytes to convert it to readable string with unit.
     *
     * @param  int $number
     * @return string
     */
    protected function formatBytes($number)
    {
        return @round($number / pow(1024, ($i = floor(log($number, 1024)))), 2) . ' ' . $this->units[$i];
    }

    /**
     * Enables provided content by set its revision to 1 and
     * its state to 1001 (STATE_NORMAL) so it can be displayed in front.
     *
     * @param  AbstractClassContent $content
     * @return self
     */
    protected function enableContent(AbstractClassContent $content)
    {
        $content
            ->setRevision(1)
            ->setState(AbstractClassContent::STATE_NORMAL)
        ;

        foreach ($content->getData() as $element) {
            if ($element instanceof AbstractClassContent) {
                $this->enableContent($element);
            }
        }

        return $this;
    }
}
