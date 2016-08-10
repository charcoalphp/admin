<?php

namespace Charcoal\Admin\Template;

// Local parent namespace dependencies
use \Charcoal\Admin\AdminTemplate as AdminTemplate;

/**
 * Admin Lost Password Template
 */
class LostPasswordTemplate extends AdminTemplate
{
    /**
     * Authentication is obviously never required for the lost-password page.
     *
     * @return boolean
     */
    protected function authRequired()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function showHeaderMenu()
    {
        return false;
    }

    /**
     * @return boolean
     */
    public function showFooterMenu()
    {
        return false;
    }
}