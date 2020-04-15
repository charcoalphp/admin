<?php

namespace Charcoal\Admin\Property\Input;

// From 'charcoal-admin'
use Charcoal\Admin\Property\Input\FileInput;

/**
 * Audio Property Input
 */
class AudioInput extends FileInput
{
    /**
     * @return string|null
     */
    public function filePreview()
    {
        $value = $this->inputVal();
        if ($value) {
            return $this->view()->render('charcoal/admin/property/input/audio/preview', $this);
        }

        return '';
    }
}
