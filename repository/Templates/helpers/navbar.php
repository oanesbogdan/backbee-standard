<?php

namespace BackBuilder\Renderer\Helper;

class navbar extends AHelper
{

    public function __invoke($mode = null)
    {
        $application = $this->_renderer->getApplication();
        $repository = $application->getEntityManager()->getRepository('BackBuilder\NestedNode\Page');

        $sections = [];
        $articles = [];
        $selected = null;

        if (null !== $current = $this->_renderer->getCurrentPage()) {
            $selected = $repository->getAncestor($current, 1);
            $sections = $repository->getVisibleDescendants($current->getRoot(), 2);

            foreach ($sections as $page) {
                if (1 === $page->getLevel()) {
                    $selector = [
                        'parentnode' => [$page->getUid()],
                        'orderby'    => ['modified', 'desc'],
                        'limit'      => 6,
                    ];

                    $articles[$page->getUid()] = $application->getEntityManager()
                        ->getRepository('BackBuilder\ClassContent\AClassContent')
                        ->getSelection($selector, false, true, 0, 3, true, false, ['BackBuilder\ClassContent\article'])
                    ;
                }
            }
        }

        $render = $this->_renderer->partial('partials/navbar.' . (null !== $mode ? $mode . '.' : '') . 'twig', [
            'sections' => $sections,
            'articles' => $articles,
            'selected' => $selected,
        ]);

        return $render;
    }

    private function generateCacheUid($selected = null)
    {
        return md5('navbar-' . (null === $selected ? 'none' : $selected->getUid()));
    }

}
