<?php

namespace Novalnet\Providers\DataProvider;

use Plenty\Plugin\Templates\Twig;
use Novalnet\Helper\PaymentHelper;

class PaymentMethodReinitializePaymentScript
{
  public function call(Twig $twig):string
  {
    $paymentHelper = pluginApp(PaymentHelper::class);
    
    return $twig->render('PaymentMethod::PaymentMethodReinitializePaymentScript', ['mopIds' => ['paymentMethodId' => 6002]]);
  }
}
