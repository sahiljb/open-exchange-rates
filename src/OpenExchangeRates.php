<?php

namespace Sahiljb\OpenExchangeRates;

use App\Http\Controllers\Controller;

/**
 * A Laravel package for currency conversation from openexchangerates.org
 *
 * @package     OpenExchangeRates
 * @link        https://openexchangerates.org/
 * @author      Sahil Buddhadev <hello@sahilbuddhadev.me>
 * @license     MIT
 * @access      public
 * @version     0.0.1
 */

class OpenExchangeRates extends Controller
{
    private string $api_url;
    private array $end_points;
    private string $appID;
    private string $baseCurrency;

    public bool $returnJSON;
    public array $symbols;
    public array $parameters;
    public int $rounding;

    public function __construct(string $appID)
    {
        $this->appID                = $appID;
        $this->api_url              = 'https://openexchangerates.org/api/';
        $this->end_points           = array(
                                        'latest'        => 'latest.json',
                                        'currencies'    => 'currencies.json',
                                        'usage'         => 'usage.json'
                                    );

        $this->returnJSON           = false;
        $this->baseCurrency         = 'USD';
        $this->symbols              = [];
        $this->parameters['app_id'] = $this->appID;
        $this->rounding             = 4;
        $this->setBaseCurrency();
    }

    /**
     * Set base currency price
     *
     * @param string $currency
     * @return void
     */
    public function setBaseCurrency( string $currency = '' ): void
    {
        $this->baseCurrency = empty($currency) ? 'USD' : strtoupper($currency);
    }

    /**
     * Make API calls
     *
     * @param string $endpoint
     * @param array $parameters
     * @param bool $json
     * @return false|mixed|string
     */
    private function _make_call(string $endpoint, array $parameters = array(), bool $json = false): mixed
    {
        $request_url = $this->api_url . $this->end_points[$endpoint] . '?' . http_build_query(array_filter($parameters));

        $ch = curl_init();
        $timeout = 30;

        curl_setopt($ch,CURLOPT_URL, $request_url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);

        $response = curl_exec($ch);

        curl_close($ch);

        if ($json):
            return $response;
        else:
            return json_decode( $response );
        endif;
    }

    private function _usages_checking()
    {
    }

    /**
     * Set Application ID
     *
     * @param string $appID
     * @return void
     */
    public function setAppID( string $appID ): void
    {
        $this->appID = $appID;
    }

    /**
     * Get plan details of User
     *
     * @return mixed
     */
    public function getPlan(): mixed
    {
        return $this->_make_call('usage', $this->parameters, $this->returnJSON);
    }

    /**
     * Get all currency rates
     *
     * @return mixed
     */
    public function getRates(): mixed
    {
        $this->parameters['symbols'] = implode(',', $this->symbols);
        return $this->_make_call('latest', $this->parameters, $this->returnJSON );
    }

    /**
     * Get specific currency rate
     *
     * @param array $currency
     * @return mixed
     */
    public function getRate(array $currency): mixed
    {
        $rates = $this->getRates();

        if ( isset($rates->error) ) {
            return $rates;
        } else {
            $rates = $rates->rates;

            implode(",", $currency);

            $currency_data = array(
                $rates->{strtoupper($currency[0])},
                $rates->{strtoupper($currency[1])}
            );

            return $currency_data;
        }
    }

    /**
     * Get currencies list with Symbol and Full Name
     *
     * @return mixed
     */
    public function getCurrencies(): mixed
    {
        return $this->_make_call('currencies', $this->parameters, $this->returnJSON);
    }

    /**
     * Currency conversation from base price USD
     *
     * @param float $amount
     * @param string $from
     * @param string $to
     * @return array
     */
    public function convert(float $amount, string $from, string $to): array
    {
        $currencies = array(
            $from,
            $to
        );
        $currencies_current_rate = $this->getRate($currencies);

        $usd_to_from = $currencies_current_rate[0];

        if ( isset($usd_to_from->error) ):
            return (array) $usd_to_from;
        else:
            $usd_to_to      = $currencies_current_rate[1];
            $convert_amount = $amount * ($usd_to_to / $usd_to_from);

            return array(
                'price' => round($convert_amount, $this->rounding)
            );
        endif;
    }

    /**
     * Get exchange rates based on new base currency
     *
     * @return array
     */
    public function getRatesFromBase(): array
    {
        $base_currency = $this->baseCurrency;
        $converted_rates = array(
            'base_currency' => $base_currency
        );
        $currencies = $this->getCurrencies();

        $usd_based_rates = $this->getRates();

        foreach ( $currencies as $currency => $currency_name ):
            if( !empty($usd_based_rates->rates->$currency) )
                $converted_rates['rates'][$currency] = round(1 * ( $usd_based_rates->rates->$currency / $usd_based_rates->rates->$base_currency ), $this->rounding);
        endforeach;

        return $converted_rates;
    }
}
