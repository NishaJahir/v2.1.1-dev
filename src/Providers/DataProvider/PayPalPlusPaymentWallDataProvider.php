<?php

namespace Novalnet\Providers\DataProvider;

use Novalnet\Services\PaymentService;
use Plenty\Modules\Frontend\Contracts\Checkout;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Plugin\Templates\Twig;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;

class ReinitializePayment
{
    /**
     * @param Twig $twig
     * @param BasketRepositoryContract  $basketRepositoryContract
     * @param PayPalPlusService         $paypalPlusService
     * @param PaymentService            $paymentService
     * @param Checkout                  $checkout
     * @param CountryRepositoryContract $countryRepositoryContract
     * @return string
     */
    public function call(   Twig                        $twig,
                            BasketRepositoryContract    $basketRepositoryContract,
                            PaymentService              $paymentService,
                            Checkout                    $checkout,
                            CountryRepositoryContract   $countryRepositoryContract)
    {
        $content = '';
        
            $content = $basketRepositoryContract->load();
        

        return $twig->render('Novalnet::Reinitialize.Payment', ['content'=>$content]);
    }
}
