<?php

namespace Charcoal\Admin\Property;

use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Tickable input properties provide an array of choices to choose from.
 */
abstract class AbstractTickableInput extends AbstractSelectableInput
{
    public const BLOCK_INPUT_LAYOUT   = 'block';
    public const COLUMN_INPUT_LAYOUT  = 'column';
    public const GRID_INPUT_LAYOUT    = 'grid';
    public const INLINE_INPUT_LAYOUT  = 'inline';
    public const DEFAULT_INPUT_LAYOUT = self::INLINE_INPUT_LAYOUT;

    /**
     * How radio controls should be displayed.
     *
     * @var string|null
     */
    private $inputLayout;

    /**
     * Prepare a single tickable option for output.
     *
     * @param  string|integer $ident  The choice key.
     * @param  array|object   $choice The choice structure.
     * @return array|null
     */
    protected function parseChoice($ident, $choice)
    {
        $choice = parent::parseChoice($ident, $choice);

        // Prevent choices from breaking their input types
        if (isset($choice['type'])) {
            unset($choice['type']);
        }

        $choice['inputId'] = $this->generateInputId();

        return $choice;
    }

    /**
     * Set the property's input layout.
     *
     * @param  string $layout The layout for the tickable elements.
     * @throws InvalidArgumentException If the given layout is invalid.
     * @throws OutOfBoundsException If the given layout is unsupported.
     * @return AbstractTickableInput Chainable
     */
    public function setInputLayout($layout)
    {
        if ($layout === null) {
            $this->inputLayout = null;

            return $this;
        }

        if (!is_string($layout)) {
            throw new InvalidArgumentException(sprintf(
                'Layout must be a string, received %s',
                (is_object($layout) ? get_class($layout) : gettype($layout))
            ));
        }

        $supportedLayouts = $this->supportedInputLayouts();
        if (!in_array($layout, $supportedLayouts)) {
            throw new OutOfBoundsException(sprintf(
                'Unsupported layout [%s]; must be one of %s',
                $layout,
                implode(', ', $supportedLayouts)
            ));
        }

        $this->inputLayout = $layout;

        return $this;
    }

    /**
     * Retrieve the property's input layout.
     *
     * @return string|null
     */
    public function inputLayout()
    {
        if ($this->inputLayout === null) {
            return $this->defaultInputLayout();
        }

        return $this->inputLayout;
    }

    /**
     * Retrieve the input layouts; for templating.
     *
     * @return array
     */
    public function inputLayouts()
    {
        $supported = $this->supportedInputLayouts();
        $layouts   = [];
        foreach ($supported as $layout) {
            $layouts[$layout] = ($layout === $this->inputLayout());
        }

        return $layouts;
    }

    /**
     * Retrieve the supported input layouts.
     *
     * @return array
     */
    protected function supportedInputLayouts()
    {
        return [
            self::INLINE_INPUT_LAYOUT,
            self::BLOCK_INPUT_LAYOUT
        ];
    }

    /**
     * Retrieve the default input layout.
     *
     * @return array
     */
    protected function defaultInputLayout()
    {
        return static::DEFAULT_INPUT_LAYOUT;
    }
}
