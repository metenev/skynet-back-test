<?php

namespace SkyNetBack\Controller;

use SkyNetBack\Core\Controller;
use SkyNetBack\Model\Tariff;
use SkyNetBack\Model\Service;

class Services extends Controller {

    public function prepare()
    {
        //
    }

    /**
     * Get available plans for an existing service of a user
     */
    public function action_tarifs(array $params)
    {
        if (!$this->requestMethod('get'))
        {
            $this->respondError('Request method is not allowed');
            return;
        }

        $tariffModels = Tariff::findPlansByServiceId($params['user_id'], $params['service_id']);
        $tariffs = [];

        foreach ($tariffModels as $model)
        {
            $tariffs[] = $model->forJSONOutput();
        }

        $this->respondSuccess([ 'tarifs' => $tariffs ]);
    }

    /**
     * Set tariff to an existing service of a user
     */
    public function action_tarif(array $params)
    {
        if (!$this->requestMethod('put'))
        {
            $this->respondError('Request method is not allowed');
            return;
        }

        // Prepare the body
        $body = file_get_contents("php://input");
        if ($body)
        {
            $body = @json_decode(trim($body), true);
        }

        if (!$body || empty($body))
        {
            $this->respondError('Request body is required');
            return;
        }

        // Find the service
        $service = Service::findById($params['user_id'], $params['service_id']);

        if (!$service)
        {
            $this->respondError('Service not found', 404);
            return;
        }

        // Find tariff

        $tariff = Tariff::findById(intval($body['tarif_id']));

        if (!$tariff)
        {
            $this->respondError('Tariff not found', 404);
            return;
        }

        // Now update the service

        try
        {
            $service->set('tarif_id', $tariff->field('ID'));
            $service->setExpr('payday', 'DATE_ADD(CURRENT_DATE, INTERVAL ' . $tariff->field('pay_period') . ' MONTH)');

            $service->save();
        }
        catch (\Exception $e)
        {
            // TODO: Log this exception
            $this->respondError('Could not set the tariff', 500);
            return;
        }

        $this->respondSuccess();
    }

}
