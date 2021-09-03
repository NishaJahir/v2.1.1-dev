<?php

namespace Novalnet\Providers\DataProvider;

use Plenty\Plugin\Templates\Twig;
use Novalnet\Helpers\PaymentHelper;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Plugin\Log\Loggable;

class PaymentMethodReinitializePayment
{
  use Loggable;
  
  public function call(Twig $twig, $arg)
  {
    /** @var PaymentMethodRepositoryContract $paymentMethodRepository */
      $paymentMethodRepository = pluginApp(PaymentMethodRepositoryContract::class);
      $paymentMethods          = $paymentMethodRepository->allForPlugin('plenty_novalnet');
      $paymentIds              = [];
      foreach ($paymentMethods as $paymentMethod) {
          if ($paymentMethod instanceof PaymentMethod) {
              $paymentIds[] = $paymentMethod->id;
          }
      }
    $this->getLogger(__METHOD__)->error('methods', $paymentMethods);
    $this->getLogger(__METHOD__)->error('ids', $paymentIds);
    return $twig->render('Novalnet::PaymentMethodReinitializePayment', ["order" => $arg[0], 'paymentIds' => $paymentIds]);
  }
}


