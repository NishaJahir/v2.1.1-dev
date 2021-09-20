<?php

namespace Novalnet\Providers\DataProvider;

use Plenty\Plugin\Templates\Twig;
use Novalnet\Helper\PaymentHelper;

class PaymentMethodReinitializePaymentScript
{
  public function call(Twig $twig, $arg):string
  {
    $paymentHelper = pluginApp(PaymentHelper::class);
    
    return $twig->render('Novalnet::PaymentMethodReinitializePaymentScript', ["order" => $arg[0], 'mopIds' => ['paymentMethodId' => 6008]]);
  }
}
