<?php

namespace Novalnet\Providers\DataProvider;

use Plenty\Plugin\Templates\Twig;
use Novalnet\Helpers\PaymentHelper;

class PaymentMethodReinitializePayment
{
  public function call(Twig $twig, $arg):string
  {
    /** @var PaymentHelper $paymentHelper */
    $paymentHelper = pluginApp(PaymntHelper::class);
    
    return $twig->render('PaymentMethod::PaymentMethodReinitializePayment', ["order" => $arg[0], "paymentMethodId" => 6002]);
  }
}
