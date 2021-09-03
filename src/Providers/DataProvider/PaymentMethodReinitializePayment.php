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
    $content = 'continue';
    return $twig->render('Novalnet::PaymentMethodReinitializePayment', ["content" => $content]);
  }
}


