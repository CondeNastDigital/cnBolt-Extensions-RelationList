<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bridge\Twig\Tests\Extension;

use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Bridge\Twig\Tests\Extension\Fixtures\StubFilesystemLoader;
use Symfony\Bridge\Twig\Tests\Extension\Fixtures\StubTranslator;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Tests\AbstractDivLayoutTest;
use Twig\Environment;

class FormExtensionDivLayoutTest extends AbstractDivLayoutTest
{
    /**
     * @var FormExtension
     */
    protected $extension;

    protected $testableFeatures = array(
        'choice_attr',
    );

    protected function setUp()
    {
        parent::setUp();

        $rendererEngine = new TwigRendererEngine(array(
            'form_div_layout.html.twig',
            'custom_widgets.html.twig',
        ));
        $renderer = new TwigRenderer($rendererEngine, $this->getMockBuilder('Symfony\Component\Security\Csrf\CsrfTokenManagerInterface')->getMock());

        $this->extension = new FormExtension($renderer);

        $loader = new StubFilesystemLoader(array(
            __DIR__.'/../../Resources/views/Form',
            __DIR__.'/Fixtures/templates/form',
        ));

        $environment = new Environment($loader, array('strict_variables' => true));
        $environment->addExtension(new TranslationExtension(new StubTranslator()));
        $environment->addGlobal('global', '');
        // the value can be any template that exists
        $environment->addGlobal('dynamic_template_name', 'child_label');
        $environment->addExtension($this->extension);

        $this->extension->initRuntime($environment);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->extension = null;
    }

    public function testThemeBlockInheritanceUsingUse()
    {
        $view = $this->factory
            ->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\EmailType')
            ->createView()
        ;

        $this->setTheme($view, array('theme_use.html.twig'));

        $this->assertMatchesXpath(
            $this->renderWidget($view),
            '/input[@type="email"][@rel="theme"]'
        );
    }

    public function testThemeBlockInheritanceUsingExtend()
    {
        $view = $this->factory
            ->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\EmailType')
            ->createView()
        ;

        $this->setTheme($view, array('theme_extends.html.twig'));

        $this->assertMatchesXpath(
            $this->renderWidget($view),
            '/input[@type="email"][@rel="theme"]'
        );
    }

    public function testThemeBlockInheritanceUsingDynamicExtend()
    {
        $view = $this->factory
            ->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\EmailType')
            ->createView()
        ;

        $renderer = $this->extension->renderer;
        $renderer->setTheme($view, array('page_dynamic_extends.html.twig'));

        $this->assertMatchesXpath(
            $renderer->searchAndRenderBlock($view, 'row'),
            '/div/label[text()="child"]'
        );
    }

    public function isSelectedChoiceProvider()
    {
        // The commented cases should not be necessary anymore, because the
        // choice lists should assure that both values passed here are always
        // strings
        return array(
//             array(true, 0, 0),
            array(true, '0', '0'),
            array(true, '1', '1'),
//             array(true, false, 0),
//             array(true, true, 1),
            array(true, '', ''),
//             array(true, null, ''),
            array(true, '1.23', '1.23'),
            array(true, 'foo', 'foo'),
            array(true, 'foo10', 'foo10'),
            array(true, 'foo', array(1, 'foo', 'foo10')),

            array(false, 10, array(1, 'foo', 'foo10')),
            array(false, 0, array(1, 'foo', 'foo10')),
        );
    }

    /**
     * @dataProvider isSelectedChoiceProvider
     */
    public function testIsChoiceSelected($expected, $choice, $value)
    {
        $choice = new ChoiceView($choice, $choice, $choice.' label');

        $this->assertSame($expected, $this->extension->isSelectedChoice($choice, $value));
    }

    public function testStartTagHasNoActionAttributeWhenActionIsEmpty()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\FormType', null, array(
            'method' => 'get',
            'action' => '',
        ));

        $html = $this->renderStart($form->createView());

        $this->assertSame('<form name="form" method="get">', $html);
    }

    public function testStartTagHasActionAttributeWhenActionIsZero()
    {
        $form = $this->factory->create('Symfony\Component\Form\Extension\Core\Type\FormType', null, array(
            'method' => 'get',
            'action' => '0',
        ));

        $html = $this->renderStart($form->createView());

        $this->assertSame('<form name="form" method="get" action="0">', $html);
    }

    public function isRootFormProvider()
    {
        return array(
            array(true, new FormView()),
            array(false, new FormView(new FormView())),
        );
    }

    /**
     * @dataProvider isRootFormProvider
     */
    public function testIsRootForm($expected, FormView $formView)
    {
        $this->assertSame($expected, $this->extension->isRootForm($formView));
    }

    public function testMoneyWidgetInIso()
    {
        $environment = new Environment(new StubFilesystemLoader(array(
            __DIR__.'/../../Resources/views/Form',
            __DIR__.'/Fixtures/templates/form',
        )), array('strict_variables' => true));
        $environment->addExtension(new TranslationExtension(new StubTranslator()));
        $environment->addExtension($this->extension);
        $environment->setCharset('ISO-8859-1');

        $this->extension->initRuntime($environment);

        $view = $this->factory
            ->createNamed('name', 'Symfony\Component\Form\Extension\Core\Type\MoneyType')
            ->createView()
        ;

        $this->assertSame('&euro; <input type="text" id="name" name="name" required="required" />', $this->renderWidget($view));
    }

    protected function renderForm(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->renderBlock($view, 'form', $vars);
    }

    protected function renderEnctype(FormView $view)
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'enctype');
    }

    protected function renderLabel(FormView $view, $label = null, array $vars = array())
    {
        if (null !== $label) {
            $vars += array('label' => $label);
        }

        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'label', $vars);
    }

    protected function renderErrors(FormView $view)
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'errors');
    }

    protected function renderWidget(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'widget', $vars);
    }

    protected function renderRow(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'row', $vars);
    }

    protected function renderRest(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'rest', $vars);
    }

    protected function renderStart(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->renderBlock($view, 'form_start', $vars);
    }

    protected function renderEnd(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->renderBlock($view, 'form_end', $vars);
    }

    protected function setTheme(FormView $view, array $themes)
    {
        $this->extension->renderer->setTheme($view, $themes);
    }

    public static function themeBlockInheritanceProvider()
    {
        return array(
            array(array('theme.html.twig')),
        );
    }

    public static function themeInheritanceProvider()
    {
        return array(
            array(array('parent_label.html.twig'), array('child_label.html.twig')),
        );
    }
}
