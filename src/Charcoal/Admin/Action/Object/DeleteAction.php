<?php

namespace Charcoal\Admin\Action\Object;

use Exception;
// From PSR-7
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
// From 'charcoal-admin'
use Charcoal\Admin\AdminAction;

/**
 * Admin Object Delete Action: Delete an object from storage.
 *
 * ## Parameters
 *
 * **Required parameters**
 *
 * - `obj_type` (_string_) — The object type, as an identifier for a {@see \Charcoal\Model\ModelInterface}.
 * - `obj_id` (_mixed_) — The object ID to delete
 *
 * ## Response
 *
 * - `success` (_boolean_) — TRUE if the object was properly deleted, FALSE in case of any error.
 * - `next_url` (_string_) — Redirect the client on success or failure.
 *
 * ## HTTP Codes
 *
 * - `200` — Successful; Object has been deleted
 * - `400` — Client error; Invalid request data
 * - `404` — Storage error; Object nonexistent ID
 * - `500` — Server error; Object could not be deleted
 */
class DeleteAction extends AdminAction
{
    /**
     * @todo   Add support for "next_url".
     * @todo   Implement Trash
     * @param  RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param  ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        try {
            $failMessage = $this->translator()->translation('Failed to delete object');
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
                    '{{ expectedType }}' => 'string or numeric',
                    '{{ actualType }}'   => $actualType,
                ]));
                $this->setSuccess(false);

                return $response->withStatus(400);
            }

            $this->logger->debug(sprintf(
                '[Admin] Deleting Object "%s" ID: %s',
                $objType,
                $objId
            ));

            $obj = $this->modelFactory()->create($objType);
            $obj->load($objId);

            if (!$obj->id()) {
                $this->addFeedback('error', strtr($errorThrown, [
                    '{{ errorThrown }}' => $this->translator()->translate('No object found.')
                ]));
                $this->setSuccess(false);

                return $response->withStatus(404);
            }

            $result = $obj->delete();
            if ($result) {
                $this->addFeedback('success', $this->translator()->translate('Object permanently deleted.'));
                $this->addFeedback('success', strtr($this->translator()->translate('Deleted Object: {{ objId }}'), [
                    '{{ objId }}' => $obj->id()
                ]));
                $this->setSuccess(true);

                return $response;
            } else {
                $this->addFeedback('error', $failMessage);
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

    /**
     * @return array
     */
    public function results()
    {
        return [
            'success'   => $this->success(),
            'feedbacks' => $this->feedbacks()
        ];
    }
}
