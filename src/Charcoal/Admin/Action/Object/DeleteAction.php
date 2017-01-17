<?php

namespace Charcoal\Admin\Action\Object;

// Dependencies from `PHP`
use Exception;

// PSR-7 (http messaging) dependencies
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// Intra-module (`charcoal-admin`) dependencies
use Charcoal\Admin\AdminAction;

/**
 * Admin Object Delete Action: Delete an object from storage.
 *
 * ## Parameters
 *
 * **Required parameters**
 *
 * - `obj_type`
 * - `obj_id`
 *
 * ## Response
 *
 * - `success` true if login was successful, false otherwise.
 *   - Failure should also send a different HTTP code: see below.
 * - `feedbacks` (Optional) operation feedbacks, if any.
 * - `next_url` Redirect URL, in case of successfull login.
 *   - This is the `next_url` parameter if it was set, or the default admin URL if not
 *
 * ## HTTP Codes
 *
 * - `200` in case of a successful object deletion
 * - `404` if any error occurs
 *
 * Ident: `charcoal/admin/action/object/delete`
 *
 */
class DeleteAction extends AdminAction
{

    /**
     * @param RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        try {
            $objType = $request->getParam('obj_type');
            $objId = $request->getParam('obj_id');

            if (!$objType) {
                $this->setSuccess(false);
                return $response->withStatus(404);
            }

            if (!$objId) {
                $this->setSuccess(false);
                return $response->withStatus(404);
            }

            $this->logger->debug(sprintf('Admin Deleting object "%s" ID %s', $objType, $objId));

            $obj = $this->modelFactory()->create($objType);
            $obj->load($objId);
            if (!$obj->id()) {
                $this->setSuccess(false);
                return $response->withStatus(404);
            }
            $res = $obj->delete();
            if ($res) {
                $this->setSuccess(true);
                return $response;
            }
        } catch (Exception $e) {
            $this->setSuccess(false);
            return $response->withStatus(500);
        }
    }

    /**
     * @return array
     */
    public function results()
    {
        $results = [
            'success'   => $this->success(),
            'feedbacks' => $this->feedbacks()
        ];

        return $results;
    }
}
