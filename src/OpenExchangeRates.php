<?php

namespace Sahiljb\OpenExchangeRates;

use App\Http\Controllers\Controller;

/**
 * A Laravel package for currency conversation from openexchangerates.org
 *
 * @link        https://openexchangerates.org/
 * @author      Sahil Buddhadev <hello@sahilbuddhadev.me>
 * @version     0.0.1
 */

class OpenExchangeRates extends Controller
{
    private string $api_url;
    private array $end_points;
    private string $appID;

    public bool $returnJSON;
    public string $baseCurrency;
    public array $symbols;
    public array $parameters;

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
        if ($json):
            return file_get_contents( $this->api_url . $this->end_points[$endpoint] . '?' . http_build_query($parameters) );
        else:
            return json_decode( file_get_contents( $this->api_url . $this->end_points[$endpoint] . '?' . http_build_query($parameters) ) );
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
     * @param string $currency
     * @return mixed
     */
    public function getRate(string $currency): mixed
    {
        $rates = $this->getRates();
        $rates = $rates->rates;

        return $rates->{$currency};
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
        $usd_to_from    = $this->getRate(strtoupper($from));
        $usd_to_to      = $this->getRate(strtoupper($to));

        $convert_amount = $amount * ($usd_to_to / $usd_to_from);

        return array(
            'price' => round($convert_amount, 2)
        );
    }
}

