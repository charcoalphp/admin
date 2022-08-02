<?php

namespace Charcoal\Admin\Ui;

/**
 * Structure Container Interface
 *
 * Implementation, as trait, provided by {@see \Charcoal\Admin\Ui\StructureContainerTrait}.
 */
interface StructureContainerInterface
{
    public const SEAMLESS_STRUCT_DISPLAY = 'seamless';
    public const CARD_STRUCT_DISPLAY     = 'card';
    public const GROUP_STRUCT_DISPLAY    = 'group';
    public const DEFAULT_STRUCT_DISPLAY  = self::GROUP_STRUCT_DISPLAY;

    /**
     * Retrieve the property's display layout.
     *
     * @return string|null
     */
    public function display();

    /**
     * Determine if a notice should be displayed when the structure is empty.
     *
     * @return boolean
     */
    public function showEmpty();
}
