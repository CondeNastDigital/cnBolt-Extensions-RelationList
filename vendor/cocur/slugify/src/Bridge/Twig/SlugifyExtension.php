<?php

/**
 * This file is part of cocur/slugify.
 *
 * (c) Florian Eckerstorfer <florian@eckerstorfer.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cocur\Slugify\Bridge\Twig;

use Cocur\Slugify\SlugifyInterface;

/**
 * SlugifyExtension
 *
 * @package    cocur/slugify
 * @subpackage bridge
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2014 Florian Eckerstorfer
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class SlugifyExtension extends \Twig_Extension
{
    /** @var SlugifyInterface */
    private $slugify;

    /**
     * Constructor.
     *
     * @param SlugifyInterface $slugify
     *
     * @codeCoverageIgnore
     */
    public function __construct(SlugifyInterface $slugify)
    {
        $this->slugify = $slugify;
    }

    /**
     * Returns the Twig functions of this extension.
     *
     * @return \Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('slugify', array($this, 'slugifyFilter')),
        );
    }

    /**
     * Slugify filter.
     *
     * @param string $string
     * @param string $separator
     *
     * @return string
     */
    public function slugifyFilter($string, $separator = '-')
    {
        return $this->slugify->slugify($string, $separator);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'slugify_extension';
    }
}
