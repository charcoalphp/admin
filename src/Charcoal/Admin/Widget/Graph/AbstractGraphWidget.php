<?php

namespace Charcoal\Admin\Widget\Graph;

// From `charcoal-admin`
use \Charcoal\Admin\AdminWidget;

use \Charcoal\Admin\Widget\Graph\GraphWidgetInterface;

/**
 * Base Graph widget
 */
abstract class AbstractGraphWidget extends AdminWidget implements GraphWidgetInterface
{
    /**
     * @var mixed $height
     */
    protected $height = 400;

    /**
     * @var array $colors
     */
    protected $colors;

    /**
     * @param mixed $height The graph height (for CSS).
     * @return GraphWidgetInterface Chainable
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * @return mixed
     */
    public function height()
    {
        return $this->height;
    }

    /**
     * @param string[] $colors The graph colors.
     * @return GraphWidgetInterface Chainable
     */
    public function setColors(array $colors)
    {
        $this->colors = $colors;
        return $this;
    }

    /**
     * @return string[]
     */
    public function colors()
    {
        if ($this->colors === null || empty($this->colors)) {
            $this->colors = $this->defaultColors();
        }
        return $this->colors;
    }

    /**
     * @todo Read from widget metadata
     * @return string[]
     */
    public function defaultColors()
    {
        return [
            '#ed5564',
            '#337ab7',
            '#da70d6',
            '#32cd32',
            '#6495ed',
            '#ff69b4',
            '#ba55d3',
            '#cd5c5c',
            '#ffa500',
            '#40e0d0',
            '#1e90ff',
            '#ff6347',
            '#7b68ee',
            '#00fa9a',
            '#ffd700',
            '#6b8e23',
            '#ff00ff',
            '#3cb371',
            '#b8860b',
            '#30e0e0'
        ];
    }

    /**
     * @return array Categories structure.
     */
    abstract public function categories();

    /**
     * @return string JSONified categories structure.
     */
    public function seriesJson()
    {
        return json_encode($this->series());
    }

    /**
     * @return array Series structure.
     */
    abstract public function series();

    /**
     * @return string JSONified categories structure.
     */
    public function categoriesJson()
    {
        return json_encode($this->categories());
    }
}