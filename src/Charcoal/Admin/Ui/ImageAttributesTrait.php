<?php

namespace Charcoal\Admin\Ui;

/**
 * Common Image Attributes for HTML
 */
trait ImageAttributesTrait
{
    /**
     * The CSS classes for the HTML `class` attribute.
     *
     * @var string|null
     */
    protected $classAttr;

    /**
     * The CSS styling declarations for the HTML `style` attribute.
     *
     * @var string|null
     */
    protected $styleAttr;

    /**
     * The intrinsic width when displaying the image.
     *
     * @var string
     */
    protected $width = 'auto';

    /**
     * The intrinsic height when displaying the image.
     *
     * @var string
     */
    protected $height = 'auto';

    /**
     * The maximum width to display the image.
     *
     * @var string
     */
    protected $maxWidth = '100%';

    /**
     * The maximum height to display the image.
     *
     * @var string
     */
    protected $maxHeight = 'none';

    /**
     * Set the CSS styling declarations to apply on the image.
     *
     * @param  string|string[] $styles An associative array of CSS styles.
     * @return ImageDisplay Chainable
     */
    public function setStyleAttr($styles)
    {
        if (is_array($styles)) {
            $styles = $this->parseStyleAttr($styles);
        }

        $defaults = $this->parseStyleAttr($this->defaultStyleAttr());
        if ($defaults) {
            $styles .= ' ' . $defaults;
        }

        $this->styleAttr = $styles;

        return $this;
    }

    /**
     * Retrieve the CSS styling declarations for the HTML `style` attribute.
     *
     * @return string
     */
    public function styleAttr()
    {
        if ($this->styleAttr === null) {
            $this->styleAttr = $this->parseStyleAttr($this->defaultStyleAttr());
        }

        return $this->styleAttr;
    }

    /**
     * Parse the CSS styling declarations from the property's display features.
     *
     * @return string
     */
    protected function defaultStyleAttr()
    {
        return [
            'height'     => $this->height(),
            'width'      => $this->width(),
            'max-height' => $this->maxHeight(),
            'max-width'  => $this->maxWidth(),
        ];
    }

    /**
     * Parse the CSS styling declarations from the property's display features.
     *
     * @param  string[] $styles An associative array of CSS styles.
     * @return string
     */
    protected function parseStyleAttr(array $styles)
    {
        $inline = array_map(
            function ($val, $key) {
                if (is_bool($val)) {
                    return ($val) ? $key : '';
                } elseif (isset($val)) {
                    if (is_array($val)) {
                        $val = implode(' ', $val);
                    }

                    $val = htmlspecialchars($val, ENT_QUOTES);

                    if (is_string($val)) {
                        return sprintf('%1$s: %2$s;', $key, $val);
                    }
                }
            },
            $styles,
            array_keys($styles)
        );

        return implode(' ', $inline);
    }

    /**
     * Set the CSS classes to apply on the image.
     *
     * @param  string|string[] $classes A space-separated list of CSS classes.
     * @return ImageDisplay Chainable
     */
    public function setClassAttr($classes)
    {
        if (is_array($classes)) {
            $classes = implode(' ', $classes);
        }

        $this->classAttr = $classes;

        return $this;
    }

    /**
     * Retrieve the CSS classes for the HTML `class` attribute.
     *
     * @return string
     */
    public function classAttr()
    {
        return $this->classAttr;
    }

    /**
     * Set the display width of the image.
     *
     * If integer specified, 'px' will be append to it.
     *
     * @param  mixed $width A CSS value; a length, percentage or `calc()`.
     * @return ImageDisplay Chainable
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width .= 'px';
        }

        $this->width = $width;

        return $this;
    }

    /**
     * Retrieve the display width of the image.
     *
     * @return string
     */
    public function width()
    {
        return $this->width;
    }

    /**
     * Set the display height of the image.
     *
     * If integer specified, 'px' will be append to it.
     *
     * @param  mixed $height A CSS value; a length, percentage or `calc()`.
     * @return ImageDisplay Chainable
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height .= 'px';
        }

        $this->height = $height;

        return $this;
    }

    /**
     * Retrieve the display width of the image.
     *
     * @return string
     */
    public function height()
    {
        return $this->height;
    }

    /**
     * Set the maximum width to display the image.
     *
     * If integer specified, 'px' will be append to it.
     *
     * @param  mixed $width A CSS value; a length, percentage or `calc()`.
     * @return ImageDisplay Chainable
     */
    public function setMaxWidth($width)
    {
        if (is_numeric($width)) {
            $width .= 'px';
        }

        $this->maxWidth = $width;

        return $this;
    }

    /**
     * Retrieve the maximum width to display the image.
     *
     * @return string
     */
    public function maxWidth()
    {
        return $this->maxWidth;
    }

    /**
     * Set the maximum height to display the image.
     *
     * If integer specified, 'px' will be append to it.
     *
     * @param  mixed $height A CSS value; a length, percentage or `calc()`.
     * @return ImageDisplay Chainable
     */
    public function setMaxHeight($height)
    {
        if (is_numeric($height)) {
            $height .= 'px';
        }

        $this->maxHeight = $height;

        return $this;
    }

    /**
     * Retrieve the maximum height to display the image.
     *
     * @return string
     */
    public function maxHeight()
    {
        return $this->maxHeight;
    }

    /**
     * Determine if the value is a {@see @see http://en.wikipedia.org/wiki/Data_URI_scheme Data URI}.
     *
     * @param  string $val A path or URI to analyze.
     * @return boolean
     */
    protected function isDataUri($val)
    {
        return (0 === strpos($val, 'data:'));
    }
}
