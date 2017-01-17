<?php

namespace Charcoal\Admin\Action\Object;

// PSR-7 (http messaging) dependencies
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

// Intra-module (`charcoal-admin`) dependencies
use Charcoal\Admin\AdminAction;
use Charcoal\Admin\Ui\ObjectContainerInterface;
use Charcoal\Admin\Ui\ObjectContainerTrait;

/**
 *
 */
class RevertRevisionAction extends AdminAction implements ObjectContainerInterface
{
    use ObjectContainerTrait;

    /**
     * @var integer $revNum
     */
    protected $revNum;

    /**
     * @param RequestInterface  $request  A PSR-7 compatible Request instance.
     * @param ResponseInterface $response A PSR-7 compatible Response instance.
     * @return ResponseInterface
     */
    public function run(RequestInterface $request, ResponseInterface $response)
    {
        $params = $request->getParams();
        $this->setData($params);

        $obj = $this->obj();
        $revNum = $params['rev_num'];

        $ret = $obj->revertToRevision($revNum);

        if ($ret) {
            $this->setSuccess(true);
            $this->addFeedback('success', 'Object was succesfully reverted to revision.');
            return $response;
        } else {
            $this->setSuccess(false);
            $this->addFeedback('error', 'Could not revert to revision');
            return $response->withStatus(404);
        }
    }
}
