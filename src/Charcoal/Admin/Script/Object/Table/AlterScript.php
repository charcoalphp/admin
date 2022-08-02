<?php

namespace Charcoal\Admin\Script\Object\Table;

// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
// From 'charcoal-admin'
use Charcoal\Admin\AdminScript;

/**
 * Alter an object's table (sql source) according to its metadata's properties.
 */
class AlterScript extends AdminScript
{
    /**
     * @return array
     */
    public function defaultArguments()
    {
        $arguments = [
            'obj-type' => [
                'longPrefix'   => 'obj-type',
                'description'  => 'Object type',
                'defaultValue' => ''
            ]
        ];

        $arguments = array_merge(parent::defaultArguments(), $arguments);
        return $arguments;
    }

    /**
     * @param RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        unset($request);

        $climate = $this->climate();

        $climate->underline()->out(
            'Alter object table from metadata'
        );

        $objType = $this->argOrInput('obj-type');

        $obj = $this->modelFactory()->create($objType);

        $source = $obj->source();

        $table = $source->table();

        if ($this->verbose()) {
            $climate->bold()->out(
                sprintf('The table "%s" will be altered, if necessary...', $table)
            );
            $metadata = $obj->metadata();
            $properties = $metadata->properties();
            $prop_names = array_keys($properties);
            $climate->out(
                sprintf(
                    'The %d following properties will be used: "%s"',
                    count($prop_names),
                    implode(', ', $prop_names)
                )
            );
        }

        $input = $climate->confirm(
            'Continue?'
        );
        if (!$input->confirmed()) {
            return $response;
        }

        if (!$source->tableExists()) {
            $climate->error(
                sprintf('The table "%s" does not exist. This script can only alter existing tables.', $table)
            );
            $climate->darkGray()->out(
                'If you want to create the table, run the `admin/object/table/create` script.'
            );
            return $response;
        }

        $ret = $source->alterTable();

        $climate->green()->out(
            "\n" . 'Success!'
        );

        return $response;
    }
}
