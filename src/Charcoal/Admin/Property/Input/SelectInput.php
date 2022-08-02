<?php

namespace Charcoal\Admin\Property\Input;

use InvalidArgumentException;
use Charcoal\Admin\Property\AbstractSelectableInput;
use Charcoal\Admin\Property\HierarchicalObjectProperty;

/**
 * Select Options Input Property
 *
 * > The HTML _select_ (`<select>`) element represents a control that presents a menu of options.
 * — {@link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/select/}
 */
class SelectInput extends AbstractSelectableInput
{
    /**
     * Settings for {@link http://silviomoreto.github.io/bootstrap-select/ Bootstrap Select}.
     *
     * @var array
     */
    private $selectOptions;

    /**
     * Retrieve the selectable options.
     *
     * Note: This method is also featured in {@see \Charcoal\Admin\Property\Input\TagsInput}.
     *
     * @todo [^1]: With PHP7 we can simply do `yield from $choices;`.
     * @return Generator|array
     */
    public function choices()
    {
        if ($this->p()['allowNull'] && !$this->p()['multiple']) {
            $prepend = $this->parseChoice('', $this->emptyChoice());

            yield $prepend;
        }

        $choices = parent::choices();

        /* Pass along the Generator from the parent method [^1] */
        foreach ($choices as $choice) {
            yield $choice;
        }
    }

    /**
     * Prepare a single selectable option for output.
     *
     * @param  string|integer $ident  The choice key.
     * @param  array|object   $choice The choice structure.
     * @return array|null
     */
    protected function parseChoice($ident, $choice)
    {
        $choice = parent::parseChoice($ident, $choice);

        if (isset($choice['title'])) {
            $choice['title'] = (string)$this->translator()->translation($choice['title']);
        } else {
            $choice['title'] = (string)$this->translator()->translation($choice['label']);
        }

        if (!isset($choice['subtext'])) {
            $choice['subtext'] = '';
        }

        if (!isset($choice['icon'])) {
            $choice['icon'] = '';
        }

        if ($this->p() instanceof HierarchicalObjectProperty) {
            $choice['disabled'] = $choice['value'] === $this->p()->objId();
        }

        return $choice;
    }

    /**
     * Set the select picker's options.
     *
     * This method always merges default settings.
     *
     * @param  array $settings The select picker options.
     * @return Selectinput Chainable
     */
    public function setSelectOptions(array $settings)
    {
        $this->selectOptions = array_merge($this->defaultSelectOptions(), $settings);

        return $this;
    }

    /**
     * Merge (replacing or adding) select picker options.
     *
     * @param  array $settings The select picker options.
     * @return Selectinput Chainable
     */
    public function mergeSelectOptions(array $settings)
    {
        $this->selectOptions = array_merge($this->selectOptions, $settings);

        return $this;
    }

    /**
     * Add (or replace) an select picker option.
     *
     * @param  string $key The setting to add/replace.
     * @param  mixed  $val The setting's value to apply.
     * @throws InvalidArgumentException If the identifier is not a string.
     * @return Selectinput Chainable
     */
    public function addSelectOption($key, $val)
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException(
                'Setting key must be a string.'
            );
        }

        // Make sure default options are loaded.
        if ($this->selectOptions === null) {
            $this->selectOptions();
        }

        $this->selectOptions[$key] = $val;

        return $this;
    }

    /**
     * Retrieve the select picker's options.
     *
     * @return array
     */
    public function selectOptions()
    {
        if ($this->selectOptions === null) {
            $this->selectOptions = $this->defaultSelectOptions();
        }

        return $this->selectOptions;
    }

    /**
     * Retrieve the default select picker options.
     *
     * @return array
     */
    public function defaultSelectOptions()
    {
        return [
            'style'    => '',
            // Neutralize any Bootstrap .btn styling
            'template' => [
                // No need for .caret markup since Bootstrap 4
                'caret' => ''
            ]
        ];
    }

    /**
     * Retrieve the select picker's options as a JSON string.
     *
     * @return string Returns data serialized with {@see json_encode()}.
     */
    public function selectOptionsAsJson()
    {
        return json_encode($this->selectOptions());
    }
}
