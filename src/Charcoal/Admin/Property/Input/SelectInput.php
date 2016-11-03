<?php

namespace Charcoal\Admin\Property\Input;

use \InvalidArgumentException;

use \Charcoal\Admin\Property\AbstractSelectableInput;

/**
 * Select Options Input Property
 *
 * > The HTML _select_ (`<select>`) element represents a control that presents a menu of options.
 * — {@link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/select/}
 */
class SelectInput extends AbstractSelectableInput
{
    /**
     * The Bootstrap select picker settigns.
     *
     * @var  array
     * - {@link http://silviomoreto.github.io/bootstrap-select/@link http://silviomoreto.github.io/bootstrap-select/}
     */
    private $selectOptions;

    /**
     * Retrieve the selectable options.
     *
     * @todo [^1]: With PHP7 we can simply do `yield from $choices;`.
     * @return Generator|array
     */
    public function choices()
    {
        if ($this->p()->allowNull() && !$this->p()->multiple()) {
            $prepend = $this->emptyChoice();

            yield $prepend;
        }

        $choices = parent::choices();

        /* Pass along the Generator from the parent method [^1] */
        foreach ($choices as $choice) {
            yield $choice;
        }
    }

    /**
     * Retrieve a blank choice.
     *
     * @return array
     */
    protected function emptyChoice()
    {
        $label = $this->placeholder();

        return [
            'value'   => '',
            'label'   => $label,
            'title'   => $label,
            'subtext' => ''
        ];
    }

    /**
     * Set the select picker's options.
     *
     * This method overwrites existing helpers.
     *
     * @param array $opts The select picker options.
     * @return Tinymce Chainable
     */
    public function setSelectOptions(array $opts)
    {
        $this->selectOptions = $opts;

        return $this;
    }

    /**
     * Merge (replacing or adding) select picker options.
     *
     * @param array $opts The select picker options.
     * @return Tinymce Chainable
     */
    public function mergeSelectOptions(array $opts)
    {
        $this->selectOptions = array_merge($this->selectOptions, $opts);

        return $this;
    }

    /**
     * Add (or replace) an select picker option.
     *
     * @param string $optIdent The setting to add/replace.
     * @param mixed  $optVal   The setting's value to apply.
     * @throws InvalidArgumentException If the identifier is not a string.
     * @return Tinymce Chainable
     */
    public function addSelectOption($optIdent, $optVal)
    {
        if (!is_string($optIdent)) {
            throw new InvalidArgumentException(
                'Option identifier must be a string.'
            );
        }

        // Make sure default options are loaded.
        if ($this->selectOptions === null) {
            $this->selectOptions();
        }

        $this->selectOptions[$optIdent] = $optVal;

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
            $this->setSelectOptions($this->defaultSelectOptions());
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
        $metadata = $this->metadata();

        if (isset($metadata['data']['select_options'])) {
            return $metadata['data']['select_options'];
        }

        return [];
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