<?php

namespace Charcoal\Admin\Action\Object;

use Exception;
// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
// From 'charcoal-core'
use Charcoal\Model\ModelValidator;
// From 'charcoal-object'
use Charcoal\Object\AuthorableInterface;

/**
 * Action: Save an object and update copy in storage.
 *
 * ## Required Parameters
 *
 * - `obj_type` (_string_) — The object type, as an identifier for a {@see \Charcoal\Model\ModelInterface}.
 * - `obj_id` (_mixed_) — The object ID to load and update
 *
 * ## Response
 *
 * - `success` (_boolean_) — TRUE if the object was properly saved, FALSE in case of any error.
 *
 * ## HTTP Status Codes
 *
 * - `200` — Successful; Object has been updated
 * - `400` — Client error; Invalid request data
 * - `500` — Server error; Object could not be updated
 */
class UpdateAction extends AbstractSaveAction
{
    /**
     * Data for the target model.
     *
     * @var array
     */
    protected $updateData = [];

    /**
     * Sets the action data from a PSR Request object.
     *
     * Extract relevant model data from $data, excluding _object type_ and _object ID_.
     * This {@see self::$updateData subset} is merged onto the target model.
     *
     * @param  RequestInterface $request A PSR-7 compatible Request instance.
     * @return self
     */
    protected function setDataFromRequest(RequestInterface $request)
    {
        parent::setDataFromRequest($request);

        $data = $this->filterUpdateData($request->getParams());

        $this->setUpdateData($data);

        return $this;
    }

    /**
     * Retrieve the list of parameters to extract from the HTTP request.
     *
     * @return string[]
     */
    protected function validDataFromRequest()
    {
        return array_merge([
            'obj_type', 'obj_id'
        ], parent::validDataFromRequest());
    }

    /**
     * Filter the dataset used to update the target model.
     *
     * @param  array $data The update data to filter.
     * @return array
     */
    public function filterUpdateData(array $data)
    {
        unset(
            $data['widget_id'],
            $data['widgetId'],
            $data['next_url'],
            $data['nextUrl'],
            $data['obj_type'],
            $data['objType'],
            $data['obj_id'],
            $data['objId']
        );

        return $data;
    }

    /**
     * Set the dataset used to update the target model.
     *
     * @param  array $data The update data.
     * @return UpdateAction Chainable
     */
    public function setUpdateData(array $data)
    {
        $this->updateData = $data;

        return $this;
    }

    /**
     * Retrieve the dataset used to update the target model.
     *
     * @return array
     */
    public function getUpdateData()
    {
        return $this->updateData;
    }

    /**
     * @param  RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param  ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        try {
            $failMessage = $this->translator()->translation('Failed to update object');
            $errorThrown = strtr($this->translator()->translation('{{ errorMessage }}: {{ errorThrown }}'), [
                '{{ errorMessage }}' => $failMessage
            ]);
            $reqMessage  = $this->translator()->translation(
                '{{ parameter }} required, must be a {{ expectedType }}, received {{ actualType }}'
            );
            $typeMessage = $this->translator()->translation(
                '{{ parameter }} must be a {{ expectedType }}, received {{ actualType }}'
            );

            $objType = $request->getParam('obj_type');
            $objId   = $request->getParam('obj_id');

            if (!$objType) {
                $actualType = is_object($objType) ? get_class($objType) : gettype($objType);
                $this->addFeedback('error', strtr($reqMessage, [
                    '{{ parameter }}'    => '"obj_type"',
                    '{{ expectedType }}' => 'string',
                    '{{ actualType }}'   => $actualType,
                ]));
                $this->setSuccess(false);

                return $response->withStatus(400);
            }

            if (!$objId) {
                $actualType = is_object($objId) ? get_class($objId) : gettype($objId);
                $this->addFeedback('error', strtr($reqMessage, [
                    '{{ parameter }}'    => '"obj_id"',
                    '{{ expectedType }}' => 'ID',
                    '{{ actualType }}'   => $actualType,
                ]));
                $this->setSuccess(false);

                return $response->withStatus(400);
            }

            $obj = $this->obj();
            $obj->mergeData($this->getUpdateData());

            $valid = $this->validate($obj);
            if (!$valid) {
                if (!$this->hasFeedbacks()) {
                    $this->addFeedback('error', strtr($errorThrown, [
                        '{{ errorThrown }}' => $this->translator()->translate('Invalid Data')
                    ]));
                }

                $this->addFeedbackFromModel($obj);
                $this->setSuccess(false);

                return $response->withStatus(400);
            }

            if ($obj instanceof AuthorableInterface) {
                $obj->setLastModifiedBy($this->getAuthorIdent());
            }

            $result = $obj->update();

            if ($result) {
                $this->addFeedback('success', $this->translator()->translate('Object has been successfully updated.'));
                $this->addFeedback('success', strtr($this->translator()->translate('Updated Object: {{ objId }}'), [
                    '{{ objId }}' => $obj->id()
                ]));
                $this->addFeedbackFromModel($obj, [ ModelValidator::NOTICE, ModelValidator::WARNING ]);
                $this->setSuccess(true);

                return $response;
            } else {
                $this->addFeedback('error', $failMessage);
                $this->addFeedbackFromModel($obj);
                $this->setSuccess(false);

                return $response->withStatus(500);
            }
        } catch (Exception $e) {
            $this->addFeedback('error', strtr($errorThrown, [
                '{{ errorThrown }}' => $e->getMessage()
            ]));
            $this->setSuccess(false);

            return $response->withStatus(500);
        }
    }
}
