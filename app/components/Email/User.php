<?php
namespace Email;

use Mailchimp\Mailchimp;
use IMCConnector\ImcConnector;
use IMCConnector\ImcConnectorException;
use IMCConnector\ImcXmlConnector;


class User
{
  private $email;
  private $exists = false;

  private $exclusions = array();
  private $exclusionsRemoved = array();
  private $recipientId;
  private $optOut;
  private $mcStatus;


  /**
   * Create a new instance
   * @param array   $credentials    Api keys etc contained in the INI file
   * @param string  $emailInput     The email address supplied by URL parameter
   **/

  public function __construct($credentials, $emailInput = null)
  {
    $this->email = strtolower($emailInput);

    if ($this->email) {
      $mcresult = $this->getMailchimpData($credentials['mailchimp']);
      if ($mcresult['status'] !== 404) {
        $this->exists = true;
        $this->exclusions = $this->strToArr($mcresult['merge_fields']['EXCLUSION']);
        $this->mcStatus = $mcresult['status'];
        $this->recipientId = $mcresult['merge_fields']['IMCID'];
        $this->optOut = ( strtolower($mcresult['merge_fields']['OPTOUT']) === 'yes' ||
          $mcresult['status'] !== 'subscribed' ||
          $mcresult['interests'][$credentials['mailchimp']['opt_out_id']] ||
          in_array("NOC", $this->exclusions) ||
          in_array("EMC", $this->exclusions) );
      }
    }
  }

  /**
   * Get Mailchimp
   *
   * A method that returns the Mailchimp object
   *
   * @param array   $apikey         Mailchimp API keys
   * @return mixed
   **/

  private function getMailchimp($apikey)
  {
    return new MailChimp($apikey);
  }

  /**
   * Get Mailchimp Data
   *
   * A method that pulls the user's data from Mailchimp
   *
   * @param array   $credentials    Mailchimp API keys etc contained in the INI file
   * @return array
   **/

  private function getMailchimpData($credentials)
  {
    $MailChimp = $this->getMailchimp($credentials['api_key']);
    $subscriber_hash = $MailChimp->subscriberHash($this->email);
    return $MailChimp->get("lists/{$credentials['list_id']}/members/$subscriber_hash");
  }

  /**
   * Update Mailchimp Data
   *
   * @param string  $credentials  Mailchimp credentials
   * @return mixed
   **/

  public function updateMailchimp($credentials, $groups)
  {
    $error = false;
    $MailChimp = $this->getMailchimp($credentials['api_key']);
    $subscriber_hash = $MailChimp->subscriberHash($this->email);
    $mailchimpPayload['interests'] = array();

    if ($this->isOptedOut()) {
      $this->optInExclusions();
      $mailchimpPayload['status'] = 'pending';
      $mailchimpPayload['merge_fields'] = array (
        'OPTOUT' => 'None',
        'EXCLUSION' => $this->getExclusions(',', '^')
      );
      $mailchimpPayload['interests'][$credentials['opt_out_id']] = false;
    }

    foreach($groups as $group) {
      $mailchimpPayload['interests'][$group] = true;
    }
    if ($this->exists) {
      $mcresult = $MailChimp->patch("lists/{$credentials['list_id']}/members/$subscriber_hash", $mailchimpPayload);
    } else {
      $mailchimpPayload['email_address'] = $this->email;
      $mailchimpPayload['status'] = 'pending';
      $mailchimpPayload['timestamp_signup'] = date("Y-m-d G:i:s");

      $mcresult = $MailChimp->post("lists/{$credentials['list_id']}/members", $mailchimpPayload);
    }

    if (!$MailChimp->success()) {
      error_log( $_SERVER['REQUEST_URI'] );
      error_log(json_encode($MailChimp->getLastRequest()));
      error_log(json_encode($MailChimp->getLastResponse()));
      $error = true;
    }
    return ['payload' => $mailchimpPayload, 'response' => $mcresult, 'isError' => $error];

    /* return ['payload' => $mailchimpPayload]; */

  }

  /**
   * Setup IMC
   *
   * A method that instantiates IMC
   *
   * @param array   $credentials    IMC API keys etc contained in the INI file
   **/

  private function initIMC($credentials)
  {
    ImcConnector::getInstance($credentials['baseUrl']);
    ImcConnector::getInstance()->authenticateRest(
      $credentials['client_id'],
      $credentials['client_secret'],
      $credentials['refresh_token']
    );

  }

  /**
   * Update IMC
   *
   * @param  array  $credentials  IMC credentials
   * @return mixed
   **/

  public function updateIMC($credentials) {
    $imcPayload = array();
    $imcResult = array();
    $error = false;
    if ($this->recipientId && $this->isOptedOut()) {
      $this->initIMC($credentials);
      $imcPayload['Fordham Opt Out'] = 'None';
      try {
        $imcResult = ImcConnector::getInstance()->updateRecipient($credentials['database_id'],
          $this->recipientId,
          null,
          $imcPayload);
      } catch (ImcConnectorException $sce) {
        error_log($_SERVER['REQUEST_URI']);
        error_log(json_encode($sce));
        $imcResult = $sce;
        $error = true;
      }
    }
    return ['payload' => $imcPayload, 'response' => $imcResult, 'isError' => $error];
  }

  /**
   * String to Array
   *
   * Strips all characters except alphanumeric -_, and returns an array
   *
   * @param string  $str        String to convert
   * @param string  $delim      Delimiter (defaults to ,)
   * @return array
   **/

  private function strToArr($str, $delim = ',') {
    if (gettype($str) !== 'string') {
      return array();
    }
    $trm = function($value) {
      return trim($value, "\t\n\r ^\"[]");
    };
    return array_map($trm, explode($delim, $str));
  }

  /**
   * Get Array Value
   *
   * Returns the value of an array key if it exists. If it does not exists returns null. 
   *
   * @param string  $key        The name of the key
   * @param array   $array      The array to search
   * @param bool    $imc        Flag to indicate if the data is coming from IMC
   * @return mixed              The value of the key or null if it doesn't exist
   **/

  private function getArrayValue($key, $array, $imc = false) {
    if ($imc) {
      return array_values(array_filter($array['COLUMNS']['COLUMN'], function($item) use($key) {
        return $item['NAME'] == $key;
      }))[0]['VALUE'] ?: '';
    }

    if ( array_key_exists ($key,$array) ) {
      return $array[$key];
    }

    return null;
  }

  /**
   * User Opt Out Status
   *
   * @return bool
   **/
  public function isOptedOut() {
    return $this->optOut;
  }

  /**
   * Set OptOut and replace exclusion codes if resubscribed
   *
   * @param bool    $optOut     Opt Out
   **/
  private function optInExclusions() {
    if(($key = array_search("EMC", $this->exclusions)) !== false) {
      $this->exclusionsRemoved[] = "EMC";
      unset($this->exclusions[$key]);
    }

    if(($key = array_search("NOC", $this->exclusions)) !== false) {
      $this->exclusionsRemoved[] = "NOC";
      unset($this->exclusions[$key]);
      foreach(["APC","AMC"] as $ec) {
        if (!in_array($ec, $this->exclusions)) {
          $this->exclusions[] = $ec;
        }
      }
    }
  }

  /**
   * Get Exclusion Codes
   *
   * @param string  $delim      Delimiter (defaults to ,)
   * @param string  $wrap       Characters to be wrapped around each item
   * @return string
   **/
  private function getExclusions($delim = ',', $wrap = '') {
    $exclusions = array_map(function($value) use($wrap) {
      return $wrap . $value . $wrap;
    }, $this->exclusions);
    return implode($delim, $exclusions);
  }
}