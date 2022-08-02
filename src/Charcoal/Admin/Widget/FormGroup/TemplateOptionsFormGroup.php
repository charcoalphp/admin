<?php

namespace Charcoal\Admin\Widget\FormGroup;

use RuntimeException;
use UnexpectedValueException;
use InvalidArgumentException;
// From Pimple
use Pimple\Container;
// From 'charcoal-core'
use Charcoal\Model\Service\MetadataLoader;
use Charcoal\Model\MetadataInterface;
// From 'charcoal-property'
use Charcoal\Property\PropertyInterface;
use Charcoal\Property\Structure\StructureMetadata;
use Charcoal\Property\TemplateProperty;
use Charcoal\Property\SelectablePropertyInterface;
// From 'charcoal-cms'
use Charcoal\Cms\TemplateableInterface;
// From 'charcoal-admin'
use Charcoal\Admin\Widget\FormGroup\StructureFormGroup;

/**
 * Template Options Form Group
 *
 * The form group widget displays a set of form controls based on properties
 * assigned to a template controller's metadata.
 *
 * This works best (minimal setup) if your model implements {@see \Charcoal\App\Template\TemplateInterface}.
 *
 * ## Examples
 *
 * **Example #1 — Template options widget**
 *
 * ```json
 * {
 *     "title": "Template Options",
 *     "type": "charcoal/admin/widget/form-group/template-options",
 *     "template": "charcoal/admin/widget/form-group/structure"
 * }
 * ```
 *
 * **Example #2 — With custom template controller**
 *
 * ```json
 * {
 *     "title": "Template Options",
 *     "type": "charcoal/admin/widget/form-group/template-options",
 *     "template": "charcoal/admin/widget/form-group/structure",
 *     "controller_ident": "foobar/template/front-page"
 * }
 * ```
 *
 * **Example #3 — With property for selecting a template controller**
 *
 * When changing the object's active template, the form must be saved and reloaded
 * to display the new template options. {@todo Eventually, the form will automatically
 * reload the template options widget when changing templates}.
 *
 * ```json
 * {
 *     "title": "Template Options",
 *     "type": "charcoal/admin/widget/form-group/template-options",
 *     "template": "charcoal/admin/widget/form-group/structure",
 *     "template_property": "template_ident"
 * }
 * ```
 */
class TemplateOptionsFormGroup extends StructureFormGroup
{
    /**
     * The form object's property for template controller choices.
     *
     * @var PropertyInterface|null
     */
    private $templateProperty;

    /**
     * The object's template controller identifier.
     *
     * @var TemplateableInterface|string|null
     */
    private $controllerIdent;

    /**
     * Store the metadata loader instance.
     *
     * @var MetadataLoader
     */
    private $metadataLoader;

    /**
     * Set the form object's template controller identifier.
     *
     * @param  mixed $ident The template controller identifier.
     * @return TemplateableInterface Chainable
     */
    public function setControllerIdent($ident)
    {
        if (class_exists($ident)) {
            $this->controllerIdent = $ident;

            return $this;
        }

        if (substr($ident, -9) !== '-template') {
            $ident .= '-template';
        }

        $this->controllerIdent = $ident;

        return $this;
    }

    /**
     * Retrieve the form object's template controller identifier.
     *
     * @return mixed
     */
    public function controllerIdent()
    {
        return $this->controllerIdent;
    }

    /**
     * Set the form object's property for template controller choices.
     *
     * Must be a property of the form's object model that will supply a list of properties.
     *
     * @param  string|PropertyInterface $propertyIdent The property identifier—or instance—of a storage property.
     * @throws InvalidArgumentException If the property identifier is not a string.
     * @throws UnexpectedValueException If a property data is invalid.
     * @return StructureFormGroup
     */
    public function setTemplateProperty($propertyIdent)
    {
        if ($propertyIdent === null) {
            $this->templateProperty = null;

            return $this;
        }

        $property = null;
        if ($propertyIdent instanceof PropertyInterface) {
            $property      = $propertyIdent;
            $propertyIdent = $property->ident();
        } elseif (!is_string($propertyIdent)) {
            throw new InvalidArgumentException(
                'Property identifier must be a string'
            );
        }

        $obj = $this->obj();
        if (!$obj->hasProperty($propertyIdent)) {
            throw new UnexpectedValueException(sprintf(
                'The "%1$s" property is not defined on [%2$s]',
                $propertyIdent,
                get_class($this->obj())
            ));
        }

        if ($property === null) {
            $property = $obj->property($propertyIdent);
        }

        $this->templateProperty = $property;

        return $this;
    }

    /**
     * Retrieve form object's property for template controller choices.
     *
     * @throws RuntimeException If the template property was not previously set.
     * @return PropertyInterface|null
     */
    public function templateProperty()
    {
        if ($this->templateProperty === null) {
            $obj = $this->obj();
            if ($obj instanceof TemplateableInterface) {
                $this->setTemplateProperty($obj->property('template_ident'));
            } else {
                throw new RuntimeException(sprintf(
                    'Storage property owner is not defined for "%s"',
                    get_class($this)
                ));
            }
        }

        return $this->templateProperty;
    }

    /**
     * Retrieve the form group's storage property master.
     *
     * @throws RuntimeException If the storage property was not previously set.
     * @return PropertyInterface
     */
    public function storageProperty()
    {
        if ($this->storageProperty === null) {
            $obj = $this->obj();
            if ($obj instanceof TemplateableInterface) {
                $this->setStorageProperty($obj->property('template_options'));
            } else {
                throw new RuntimeException(sprintf(
                    'Storage property owner is not defined for "%s"',
                    get_class($this)
                ));
            }
        }

        return $this->storageProperty;
    }

    /**
     * Inject dependencies from a DI Container.
     *
     * @param  Container $container A dependencies container instance.
     * @return void
     */
    protected function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        $this->setMetadataLoader($container['metadata/loader']);
    }

    /**
     * Set a metadata loader.
     *
     * @param  MetadataLoader $loader The loader instance, used to load metadata.
     * @return self
     */
    protected function setMetadataLoader(MetadataLoader $loader)
    {
        $this->metadataLoader = $loader;

        return $this;
    }

    /**
     * Retrieve the metadata loader.
     *
     * @throws RuntimeException If the metadata loader was not previously set.
     * @return MetadataLoader
     */
    protected function metadataLoader()
    {
        if ($this->metadataLoader === null) {
            throw new RuntimeException(sprintf(
                'Metadata Loader is not defined for "%s"',
                get_class($this)
            ));
        }

        return $this->metadataLoader;
    }

    /**
     * Load a metadata file.
     *
     * @param  string $metadataIdent A metadata file path or namespace.
     * @return MetadataInterface
     */
    protected function loadMetadata($metadataIdent)
    {
        $metadata = $this->metadataLoader()->load($metadataIdent, $this->metadataClass());
        return $metadata;
    }

    /**
     * Create a new metadata object.
     *
     * @return MetadataInterface
     */
    protected function createMetadata()
    {
        $class = $this->metadataClass();
        return new $class();
    }

    /**
     * Retrieve the class name of the metadata object.
     *
     * @return string
     */
    protected function metadataClass()
    {
        return StructureMetadata::class;
    }

    /**
     * Finalize the form group's properies, entries, and layout.
     *
     * @param  boolean $reload Rebuild the form group's structure.
     * @return void
     */
    protected function finalizeStructure($reload = false)
    {
        if ($reload || !$this->isStructureFinalized) {
            $template = null;
            $finalize = false;

            $obj = $this->obj();
            if ($obj instanceof TemplateableInterface) {
                $structureMetadata = $obj->templateOptionsMetadata();
            } else {
                $structureMetadata = $this->createMetadata();
            }

            $controllerInterfaces = (array)$this->controllerIdent();
            if (!empty($controllerInterfaces)) {
                $metadataLoader = $this->metadataLoader();

                $controllerStructKey = $controllerInterfaces;
                array_unshift($controllerStructKey, $obj->objType(), $obj->id());
                $controllerStructKey = 'template/structure=' . $metadataLoader->serializeMetaKey($controllerStructKey);

                $structureMetadata = $this->metadataLoader()->load(
                    $controllerStructKey,
                    $structureMetadata,
                    $controllerInterfaces
                );
            }

            $storageProperty = $this->storageProperty();
            $storageProperty->setStructureMetadata($structureMetadata);

            parent::finalizeStructure();
        }
    }
}
