<?php

namespace Charcoal\Admin\Property\Input;

use \Charcoal\Admin\Property\AbstractPropertyInput;

/**
 * DateTimePicker Input Property
 */
class DateTimePickerInput extends AbstractPropertyInput
{
    /**
     * The Bootstrap datetimepicker settings.
     *
     * @var  array
     * - {@link https://eonasdan.github.io/bootstrap-datetimepicker/}
     */
    private $datetimepickerOptions;

    /**
     * Set the picker's options.
     *
     * This method overwrites existing helpers and merges (replacing or adding) picker options.
     *
     * @param array $opts The picker options.
     * @return self Chainable
     */
    public function setDatetimepickerOptions(array $opts)
    {
        $this->datetimepickerOptions = array_merge($this->defaultDatetimepickerOptions(), $opts);

        return $this;
    }

    /**
     * Add (or replace) an option.
     *
     * @param string $optIdent The setting to add/replace.
     * @param mixed  $optVal   The setting's value to apply.
     * @throws InvalidArgumentException If the identifier is not a string.
     * @return self Chainable
     */
    public function addOption($optIdent, $optVal)
    {
        if (!is_string($optIdent)) {
            throw new InvalidArgumentException(
                'Option identifier must be a string.'
            );
        }

        // Make sure default options are loaded.
        if ($this->datetimepickerOptions === null) {
            $this->datetimepickerOptions();
        }

        $this->datetimepickerOptions[$optIdent] = $optVal;

        return $this;
    }

    /**
     * Retrieve the picker's options.
     *
     * @return array
     */
    public function datetimepickerOptions()
    {
        if ($this->datetimepickerOptions === null) {
            $this->setDatetimepickerOptions($this->defaultDatetimepickerOptions());
        }

        return $this->datetimepickerOptions;
    }

    /**
     * Retrieve the default picker options.
     *
     * @return array
     */
    public function defaultDatetimepickerOptions()
    {
        return [
            'format' => 'YYYY-MM-DD HH:mm:ss',
            'defaultDate' => $this->inputVal()
        ];
    }

    /**
     * Retrieve the picker's options as a JSON string.
     *
     * @return string Returns data serialized with {@see json_encode()}.
     */
    public function optionsAsJson()
    {
        return json_encode($this->datetimepickerOptions());
    }
}
