# OCM-JotForm
Example Connector between JotForm and OCM

JotForm's generic "webhook" integration does not have any ability to send standard http authentication headers so this script relies on a GET parameter that should match a hardcoded value within the connector. Potentially this could be redesigned to store the key in the settings table and set it through the system settings screen instead, but this example is intended to be free-standing for ease of understanding.

Instructions:

1. Drop the php file into the cms/services directory
2. Edit the block at the top of the file so that instead of saying this:
```php
if ($_GET['key'] != 'authenticationKeyGoesHere'){
  die('could not authenticate');
}
```
'authenticationKeyGoesHere' is replaced with a long random string.
3. Try accessing the page in a web browser, first with no authentication string and make sure you see "could not authenticate" without any serious errors.
4. Next try appending ?key='yourLongRandomAuthenticationStringThatYouPickedInStepTwo'. Be forewarned that doing this is probably going to create an empty transfer case that you should delete or reject. You should see a number (the transfer id) in the browser window. It will probably be 1 if you've never transferred a case in before.
5. Within jotform, create a new integration for a webhook. Don't use your OCM endpoint yet. Use a service like http://ptsv2.com/ or https://postb.in/ first to take a close look at the structure of the json that comes through, including the key names and subkey names. With some JotForm fields, the value may be one level down, but in others it may be two levels. Compare the address fields and the email field in the code in this repository for example.
6. Note that even if you do not plan to use the holding tank, you may want to follow the in-code comments to use it temporarily for testing purposes. 
7. Next, you will need to go through the section of this code that pulls values out of the array $d and restructures within array $p and adjust according to the structure of your data that will be coming through. 
8. Use the url with the ?key= parameter from step 4 as your endpoint within JotForm. Do a test submission and compare what was sent vs what came through.


Please feel free to contact Alex Clark of Metatheria, LLC if you have any problems.  https://metatheria.solutions.
