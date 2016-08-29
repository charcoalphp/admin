<?php

namespace Charcoal\Admin\Widget;

use \Charcoal\Admin\AdminWidget;
use \Charcoal\Translation\TranslationString;

/**
 *
 */
class TextWidget extends AdminWidget
{
    /**
     * @var boolean $showTitle
     */
    private $showTitle = true;

    /**
     * @var boolean $showSubtitle
     */
    private $showSubtitle = true;

    /**
     * @var boolean $showDescription
     */
    private $showDescription = true;

    /**
     * @var boolean $showNotes
     */
    private $showNotes = true;

    /**
     * @var string $title
     */
    private $title = '';

    /**
     * @var string $subtitle
     */
    private $subtitle = '';

    /**
     * @var string $description
     */
    private $description = '';

    /**
     * @var string $notes
     */
    private $notes = '';

    /**
     * @param boolean $show The show title flag.
     * @return Text Chainable
     */
    public function setShowTitle($show)
    {
        $this->showTitle = !!$show;
        return $this;
    }

    /**
     * @return boolean
     */
    public function showTitle()
    {
        if ($this->showTitle === false) {
            return false;
        } else {
            return !!$this->title();
        }
    }

    /**
     * @param boolean $show The show subtitle flag.
     * @return Text Chainable
     */
    public function setShowSubtitle($show)
    {
        $this->showSubtitle = !!$show;
        return $this;
    }

    /**
     * @return boolean
     */
    public function showSubtitle()
    {
        if ($this->showSubtitle === false) {
            return false;
        } else {
            return !!$this->subtitle();
        }
    }

    /**
     * @param boolean $show The show description flag.
     * @return Text Chainable
     */
    public function setShowDescription($show)
    {
        $this->showDescription = !!$show;
        return $this;
    }

    /**
     * @return boolean
     */
    public function showDescription()
    {
        if ($this->showDescription === false) {
            return false;
        } else {
            return !!$this->description();
        }
    }

    /**
     * @param boolean $show The "show notes" flag.
     * @return Text Chainable
     */
    public function setShowNotes($show)
    {
        $this->showNotes = !!$show;
        return $this;
    }

    /**
     * @return boolean
     */
    public function showNotes()
    {
        if ($this->showNotes === false) {
            return false;
        } else {
            return !!$this->notes();
        }
    }

    /**
     * @param mixed $title The text widget title.
     * @return TextWidget Chainable
     */
    public function setTitle($title)
    {
        if (TranslationString::isTranslatable($title)) {
            $this->title = new TranslationString($title);
        }

        return $this;
    }

    /**
     * @return TranslationString
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * @param mixed $subtitle The text widget subtitle.
     * @return TextWidget Chainable
     */
    public function setSubtitle($subtitle)
    {
        if (TranslationString::isTranslatable($subtitle)) {
            $this->subtitle = new TranslationString($subtitle);
        }

        return $this;
    }

    /**
     * @return TranslationString
     */
    public function subtitle()
    {
        return $this->subtitle;
    }

    /**
     * @param mixed $description The text widget description (main content).
     * @return TextWidget Chainable
     */
    public function setDescription($description)
    {
        if (TranslationString::isTranslatable($description)) {
            $this->description = new TranslationString($description);
        }

        return $this;
    }

    /**
     * @return TranslationString
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * @param mixed $notes The text widget notes.
     * @return TextWidget Chainable
     */
    public function setNotes($notes)
    {
        if (TranslationString::isTranslatable($notes)) {
            $this->notes = new TranslationString($notes);
        }

        return $this;
    }

    /**
     * @return TranslationString
     */
    public function notes()
    {
        return $this->notes;
    }
}