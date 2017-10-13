<?php
namespace Email;

use Mailchimp\Mailchimp;

class User
{
  private $emailInput;
  private $email;
  private $exists = false;

  private $exclusions = array();
  private $exclusionsRemoved = array();
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

  public function updateMailchimp($credentials)
  {
    $error = false;
    $MailChimp = $this->getMailchimp($credentials['api_key']);
    $subscriber_hash = $MailChimp->subscriberHash($this->email);
    $mailchimpMerge = $this->getPrefs(',', 'merge', '^');
    $mailchimpMerge['OPTOUT'] = ($this->isOptedOut()) ? 'Yes' : 'None';
    $mailchimpMerge['EXCLUSION'] = $this->getExclusions(',', '^');


    $malichimpPayload = [
      'email_address' => $this->emailInput,
      'merge_fields' => $mailchimpMerge,
      'status' => $this->getStatus()
    ];
    if (!$this->isOptedOut()) {
      $malichimpPayload['interests'] = [
        $credentials['opt_out_id'] => false
      ];
    }

    $mcresult = $MailChimp->patch("lists/{$credentials['list_id']}/members/$subscriber_hash", $malichimpPayload);

    if (!$MailChimp->success()) {
      error_log( $_SERVER['REQUEST_URI'] );
      error_log(json_encode($MailChimp->getLastRequest()));
      error_log(json_encode($MailChimp->getLastResponse()));
      $error = true;
    }

    return ['payload' => $malichimpPayload, 'response' => $mcresult, 'isError' => $error];
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
  public function setOptOut($optOut) {
    $this->optOut = !!$optOut;

    if (!$optOut) {
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