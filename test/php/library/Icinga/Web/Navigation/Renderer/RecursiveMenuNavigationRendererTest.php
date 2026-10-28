<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Tests\Icinga\Web\Navigation\Renderer;

use Icinga\Application\ApplicationBootstrap;
use Icinga\Application\Icinga;
use Icinga\Test\BaseTestCase;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Navigation\Renderer\NavigationItemRenderer;
use Icinga\Web\Navigation\Renderer\RecursiveMenuNavigationRenderer;
use Icinga\Web\View;
use Mockery;
use RuntimeException;

class RecursiveMenuNavigationRendererTest extends BaseTestCase
{
    protected ApplicationBootstrap $originalApp;

    public function setUp(): void
    {
        parent::setUp();
        $this->originalApp = Icinga::app();

        $view = Mockery::mock(View::class)->makePartial();
        $view->shouldReceive('icon')->andReturn('');
        $view->shouldReceive('propertiesToString')->andReturn('');

        /** @var ApplicationBootstrap&\Mockery\MockInterface $app */
        $app = Mockery::mock(ApplicationBootstrap::class);
        $app->shouldReceive('getViewRenderer')->andReturn((object) ['view' => $view]);
        $app->shouldReceive('getRequest')->andReturn($this->getRequestMock());
        $app->shouldReceive('isCli')->andReturn(false);
        Icinga::setApp($app, true);
    }

    public function tearDown(): void
    {
        Icinga::setApp($this->originalApp, true);
        parent::tearDown();
    }

    public function testHeaderEscapesLabel(): void
    {
        $parent = $this->createItem('parent', '<img src=x onerror="alert(1)">');
        $parent->addChild($this->createItem('child', 'Child'));

        $html = $this->renderMenu($parent);

        $this->assertSame(2, substr_count($html, '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;'));
        $this->assertStringNotContainsString('<img', $html);
    }

    public function testHeaderKeepsMarkupOfCustomRenderer(): void
    {
        $parent = $this->createItem('parent', 'Pending & queued');
        $parent->setRenderer(new class () extends NavigationItemRenderer {
            public function render(?NavigationItem $item = null)
            {
                $item ??= $this->getItem();
                $item->setLabel($this->view()->escape($item->getLabel()) . '<span class="badge">3</span>');
                $this->setEscapeLabel(false);

                return parent::render($item);
            }
        });
        $parent->addChild($this->createItem('child', 'Child'));

        $html = $this->renderMenu($parent);

        $this->assertSame(2, substr_count($html, 'Pending &amp; queued<span class="badge">3</span>'));
        $this->assertStringNotContainsString('&amp;amp;', $html);
    }

    public function testHeaderEscapesLabelAfterCustomRendererFails(): void
    {
        $parent = $this->createItem('parent', '<b>Trusted</b>');
        $renderer = new class () extends NavigationItemRenderer {
            public bool $fail = false;

            public function render(?NavigationItem $item = null)
            {
                $this->setEscapeLabel(false);
                if ($this->fail) {
                    throw new RuntimeException('Rendering failed');
                }

                return parent::render($item);
            }
        };
        $parent->setRenderer($renderer);
        $parent->render();
        $renderer->fail = true;
        $parent->setLabel('<img src=x onerror="alert(1)">');
        $parent->addChild($this->createItem('child', 'Child'));

        $html = $this->renderMenu($parent);

        $this->assertSame(2, substr_count($html, '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;'));
        $this->assertStringNotContainsString('<img', $html);
    }

    public function testHeaderIgnoresCustomRendererWhenStandardRendererIsForced(): void
    {
        $parent = $this->createItem('parent', '<b>Custom</b>');
        $parent->getRenderer()->setEscapeLabel(false);
        $parent->render();
        $parent->addChild($this->createItem('child', 'Child'));

        $html = $this->renderMenu($parent, true);

        $this->assertSame(2, substr_count($html, '&lt;b&gt;Custom&lt;/b&gt;'));
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function testHeaderOfUnrenderedParentIgnoresPreviousRender(): void
    {
        $parent = $this->createItem('parent', '<b>Trusted</b>');
        $parent->getRenderer()->setEscapeLabel(false);
        $parent->render();
        $parent->setRender(false)->setLabel('<img src=x onerror="alert(1)">');
        $parent->addChild($this->createItem('child', 'Child'));

        $html = $this->renderMenu($parent);

        $this->assertSame(1, substr_count($html, '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;'));
        $this->assertStringNotContainsString('<img', $html);
    }

    protected function createItem(string $name, string $label): NavigationItem
    {
        $item = new NavigationItem($name);
        $item->setLabel($label)->setUrl('#')->setActive(false)->setSelected(false);
        $item->setRenderer(new NavigationItemRenderer());

        return $item;
    }

    protected function renderMenu(NavigationItem $parent, bool $useStandardRenderer = false): string
    {
        $navigation = new Navigation();
        $navigation->addItem($parent);
        $renderer = new RecursiveMenuNavigationRenderer($navigation);
        $renderer->setUseStandardItemRenderer($useStandardRenderer);

        return $renderer->render();
    }
}
