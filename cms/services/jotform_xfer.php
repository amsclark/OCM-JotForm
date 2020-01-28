<?php

/***************************/
/* Pika CMS (C) 2015       */
/* Pika Software, LLC.     */
/* http://pikasoftware.com */
/***************************/

if ($_GET['key'] != 'authenticationKeyGoesHere'){
  die('could not authenticate');
}

chdir('../');

define('PL_DISABLE_SECURITY', true);

require_once ('pika-danio.php');
//pika_init();
require_once('app/lib/pikaTransfer.php');

function json_clean_sub($j)
{
  foreach ($j as $key => $value) 
  {
    if (is_array($value))
    {   
      $j[$key] = json_clean_sub($value);
    }   
    
    else 
    {   
      if ($key == 'ssn' && strlen($value) == 9 && is_numeric($value))
      {   
        $j[$key] = substr($value, 0, 3) . '-' . substr($value, 3, 2) . '-' 
          . substr($value, 5, 4); 
        $j[$key] = $value;
      }   
    
      else 
      {   
        $j[$key] = pl_clean_form_input($value);
      }   
    }   
  }
  
  return($j);
}

$transfer = new pikaTransfer;
$j = $_REQUEST['rawRequest'];
$d = json_decode($j, true);
$d = json_clean_sub($d);
// $d has been cleaned, but it's structured all wrong for OCM to process it. 
// so let's create an array $p with the correct structure and pull in values one at a time from $d
$p = array();
$p['client']['first_name'] = $d['q3_fullName3']['first'];
$p['client']['last_name'] = $d['q3_fullName3']['last'];
$p['client']['address'] = $d['q5_currentAddress']['addr_line1'];
$p['client']['address2'] = $d['q5_currentAddress']['addr_line2'];
$p['client']['city'] = $d['q5_currentAddress']['city'];
$p['client']['state'] = $d['q5_currentAddress']['state'];
$p['client']['zip'] = $d['q5_currentAddress']['postal'];
$p['client']['area_code'] = $d['q6_phoneNumber6']['area'];
$p['client']['phone'] = $d['q6_phoneNumber6']['phone'];
$p['client']['email'] = $d['q7_email7'];
$d = json_encode($p);
$transfer->json_data = $d;
// If you do not want to auto-accept and instead would prefer to use the transfers.php holding tank, 
// change this to 2 for pending, and remove the 
$transfer->accepted = 1;  // auto-accepted. comment out this line for no auto-accept
//$transfer->accepted = 2; // pending. uncomment this line for no auto-accept
$transfer->save();
echo $transfer->getValue('transfer_id');


// the lines below actually create the OCM case 
// begin cutting here for no auto-accept

$safe_transfer_id = $transfer->getValue('transfer_id');
$tx = new pikaTransfer($safe_transfer_id);
$x = json_decode($tx->getValue('json_data'), 1);

require_once('app/lib/pikaContact.php');
$client = new pikaContact();
$client->setValues($x['client']);
$client->save();

require_once('app/lib/pikaCase.php');
$case0 = new pikaCase();
$case0->setValues($x['case']);
$case0->addContact($client->getValue('contact_id'), 1);
$case0->save();

// Opposing Party
if (isset($x['op']))
{
  $op = new pikaContact();
  $op->setValues($x['op']);
  $op->save();
  $case0->addContact($op->getValue('contact_id'), 2);
}

// Opposing Party Attorney
if (isset($x['opa']))
{
  $opa = new pikaContact();
  $opa->setValues($x['opa']);
  $opa->save();
  $case0->addContact($opa->getValue('contact_id'), 3);
}

// Note, here is where you could add additional code to process additional types of 
// contacts other than opposing party and opposing party attorney from incoming json
// just decide on another key for the json structure (maybe 'nah' for non-adverse-household, for example) and
// then copy one of the code blocks above replacing 'opa' with 'nah' and the second parameter of the call to addContact
// with the appropriate relation code from the menu_relation_codes table

        // Case notes
        if (isset($x['notes']))
        {
                require_once('app/lib/pikaActivity.php');

                for ($i = 0; $i < 10; $i++)
                {
                        if (isset($x['notes']['notes' . $i]))
                        {
                                $note = new pikaActivity();
                                $note->setValue('summary', 'Online Intake Notes');
                                $note->setValue('notes', $x['notes']['notes' . $i]);
                                $note->setValue('case_id', $case0->getValue('case_id'));
                                $note->save();
                        }
                }
        }

// end cutting here for no auto-accept

exit();
