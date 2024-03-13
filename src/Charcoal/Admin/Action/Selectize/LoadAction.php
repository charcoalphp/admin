<?php

namespace Charcoal\Admin\Action\Selectize;

use Exception;
// From Pimple
use Pimple\Container;
// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
// From 'charcoal-admin'
use Charcoal\Admin\Action\Object\LoadAction as BaseLoadAction;
use Charcoal\Admin\Action\Selectize\SelectizeRendererAwareTrait;

/**
 * Selectize Load Action
 */
class LoadAction extends BaseLoadAction
{
    use SelectizeRendererAwareTrait;

    /**
     * The collection to return.
     *
     * @var array|mixed
     */
    private $selectizeCollection;

    /**
     * @var string $query
     */
    private $query;

    /**
     * Retrieve the list of parameters to extract from the HTTP request.
     *
     * @return string[]
     */
    protected function validDataFromRequest()
    {
        return array_merge([
            'selectize_prop_ident', 'selectize_property'
        ], parent::validDataFromRequest());
    }

    /**
     * @param  RequestInterface  $request  The request options.
     * @param  ResponseInterface $response The response to return.
     * @return ResponseInterface
     * @throws UnexpectedValueException If "obj_id" is passed as $request option.
     * @todo   Implement obj_id support for load object action
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        unset($request);

        $failMessage = $this->translator()->trans('Failed to load object(s)');
        $errorThrown = strtr($this->translator()->trans('{{ errorMessage }}: {{ errorThrown }}'), [
            '{{ errorMessage }}' => $failMessage
        ]);

        try {
            /** @var Charcoal\Admin\Property\Input\SelectizeInput */
            $input = $this->selectizeInput();

            /** @var Charcoal\Property\ObjectProperty */
            $property = $input->property();

            if ($this->query()) {
                /** @var array<string, mixed> */
                $options   = $input->selectizeOptions();
                $choiceMap = $input->choiceObjMap();

                if (!empty($options['searchProperties'])) {
                    $searchProperties = (array)$options['searchProperties'];
                } elseif (
                    !empty($choiceMap['label']) &&
                    strpos($choiceMap['label'], '{{') === false
                ) {
                    $searchProperties = [ $choiceMap['label'] ];
                } else {
                    $searchProperties = [];
                }

                if ($searchProperties) {
                    $search = [
                        'conjunction' => 'OR',
                        'conditions'  => [],
                    ];
                    foreach ($searchProperties as $searchProperties) {
                        $search['conditions'][] = [
                            'property' => $searchProperties,
                            'operator' => 'LIKE',
                            'value'    => '%' . $this->query() . '%',
                        ];
                    }

                    $filters = $property->filters();
                    if (is_array($filters)) {
                        array_push($filters, $search);
                    } else {
                        $filters = [ $search ];
                    }

                    $property->setFilters($filters);
                }
            }

            $choices = $property->choices();

            $this->setSelectizeCollection($this->selectizeVal($choices));

            $count = count($choices);
            switch ($count) {
                case 0:
                    $doneMessage = $this->translator()->translation('No objects found.');
                    break;

                case 1:
                    $doneMessage = $this->translator()->translation('One object found.');
                    break;

                default:
                    $doneMessage = strtr($this->translator()->translation('{{ count }} objects found.'), [
                        '{{ count }}' => $count
                    ]);
                    break;
            }
            $this->addFeedback('success', $doneMessage);
            $this->setSuccess(true);

            return $response;
        } catch (Exception $e) {
            $this->addFeedback('error', strtr($errorThrown, [
                '{{ errorThrown }}' => $e->getMessage()
            ]));
            $this->setSuccess(false);

            return $response->withStatus(500);
        }
    }

    /**
     * @return string
     */
    public function query()
    {
        return $this->query;
    }

    /**
     * @param string $query Query for LoadAction.
     * @return self
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function selectizeCollection()
    {
        return $this->selectizeCollection;
    }

    /**
     * @param array|mixed $selectizeCollection The collection to return.
     * @return self
     */
    public function setSelectizeCollection($selectizeCollection)
    {
        $this->selectizeCollection = $selectizeCollection;

        return $this;
    }

    /**
     * @return array
     */
    public function results()
    {
        return [
            'success'    => $this->success(),
            'feedbacks'  => $this->feedbacks(),
            'selectize'  => $this->selectizeCollection()
        ];
    }

    /**
     * Dependencies
     * @param Container $container DI Container.
     * @return void
     */
    protected function setDependencies(Container $container)
    {
        parent::setDependencies($container);

        $this->setSelectizeRenderer($container['selectize/renderer']);
        $this->setPropertyInputFactory($container['property/input/factory']);
        $this->setPropertyFactory($container['property/factory']);
    }
}
