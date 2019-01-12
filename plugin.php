<?php
/**
 *  Contact Telegram
 *
 *  @package Bludit
 *  @subpackage Plugins
 *  @author Viktor Gordienko
 *  @version 1.0
 *  @release 2019-01-10
 *  @info Plugin based on Contact3 plugin by novafacile OÜ (https://www.novafacile.com)
 *
 */
class pluginContactTelegram extends Plugin {

  private $message = '';
  private $success = false;
  private $error = false;

	// install plugin
  public function init() {
    $this->dbFields = array(
	  'botToken'	=> '',		// <= Your bot token
	  'chatId'	=> '',			// <= Your chat ID
      'email'	=> '',			// <= Your contact email
      'page'	=> '',			// <= Slug url of contact page
	  //'check-phone'	=> 'N',		// <= Check if phone
	  //'check-email'	=> 'Y'		// <= Check if email
    );
  }

	// config form
	public function form() {
    global $L, $staticPages;
    // create pageOptions;
    $pageOptions = array();
    // get all content as page
    foreach ($staticPages as $page) {
      $pageOptions[$page->key()] = $page->title();
    }
    // sort by name
    ksort($pageOptions);
    $html = '';


	// TOKEN
    $html .= '<div>';
    $html .= '<label>'.$L->get('bot-token').'</label>';
    $html .= '<input name="botToken" type="text" class="form-control" value="'.$this->getValue('botToken').'">';
    $html .= '<span class="tip">'.$L->get('how-to-get-bot-token').'</span>';
	$html .= '</div>'.PHP_EOL;
	// Chat ID
    $html .= '<div>';
    $html .= '<label>'.$L->get('chat-id').'</label>';
    $html .= '<input name="chatId" type="text" class="form-control" value="'.$this->getValue('chatId').'">';
    $html .= '<span class="tip">'.$L->get('how-to-get-chat-id').'</span>';
	$html .= '</div>'.PHP_EOL;

	// Input fields - coming soon
    /*
	$html .= '<div>';
    $html .= '<label>'.$L->get('input-fields').'</label>';
	$html .= '<input type="text" name="check-phone" value="'.$this->getValue('check-phone').'">';
	$html .= '<span class="tip">'.$L->get('input-fields-info-phone').'</span>';
	$html .= '<input type="text" name="check-email" value="'.$this->getValue('check-email').'">';
    $html .= '<span class="tip">'.$L->get('input-fields-info-email').'</span>';
	$html .= '</div>'.PHP_EOL;
	*/
	
    // select static page
    $html .= '<div>';
    $html .= '<label>'.$L->get('Select a content').'</label>';
    $html .= '<select name="page">'.PHP_EOL;
    $html .= '<option value="">- '.$L->get('static-pages').' -</option>'.PHP_EOL;
    foreach ($pageOptions as $key => $value) {
    	$html .= '<option value="'.$key.'" '.($this->getValue('page')==$key?'selected':'').'>'.$value.'</option>'.PHP_EOL;
    }
    $html .= '</select>';
    $html .= '<span class="tip">'.$L->get('the-list-is-based-only-on-published-content').'</span>';
    $html .= '</div>'.PHP_EOL;

    $html .= '<br><br>';
    return $html;
	}

  // Load CSS for contact form
  public function siteHead() {
    $webhook = $this->getValue('page');
    if($this->webhook($webhook)) {
      $css = THEME_DIR_CSS . 'contact-telegram.css';
      if(file_exists($css)) {
        $html = Theme::css('css' . DS . 'contact.css');
      } else {
        $html = '<link rel="stylesheet" href="' .$this->htmlPath(). 'layout' . DS . 'contact-telegram.css">' .PHP_EOL;
      }
      return $html;
    }
	} 

  // Load contact form and send email
  public function pageEnd(){
    $webhook = $this->getValue('page');
    if($this->webhook($webhook)) {
      
      // send email if submit 
      if(isset($_POST['submit'])) {

        // get post paramaters
        $this->readPost();
        $this->error = $this->validatePost();

        // check if it's a bot
        if($this->isBot()) {
          $this->error = true;
          // fake success for bot
          $this->success = true;
        }

        // if no error until now, then create and send email
        if(!$this->error){
          if(empty($this->getValue('smtphost'))) {
            $this->success = $this->useSendmail();
          }
          if($this->success){
            $this->clearForm();
          }
        }
        // show frontend message
        //echo $this->frontendMessage(); 
      }

      // include contact form
      $this->includeContactForm();
    }
  }

/****
 * private functions
 *****/

  private function isBot(){
    if(isset($_POST['interested'])) {
      return true;
    } else {
      return false;
    }
  }

  private function isHtml(){
    if($this->getValue('type') === 'html') {
      return true;
    } else {
      return false;
    }
  }

  private function readPost(){
    // removes bad content - just a little protection - could be better
    if(isset($_POST['name'])) { 
      $this->senderName =  trim(strip_tags($_POST['name']));
    }
    if(isset($_POST['email'])) {
      $this->senderEmail =  trim(preg_replace("/[^0-9a-zA-ZäöüÄÖÜÈèÉéÂâáÁàÀíÍìÌâÂ@ \-\+\_\.]/", " ", $_POST['email']));
    }
    if(isset($_POST['message'])){
      $this->message = nl2br(trim(strip_tags($_POST['message'])));
    }
  }

  private function validatePost(){
    global $L;
    if(trim($this->senderName)==='')
      $error = $L->get('Please enter your name');                            
    elseif(trim($this->senderEmail)==='')
      $error = $L->get('Please enter a valid email address');
    elseif(trim($this->message)==='')
      $error = $L->get('Please enter the content of your message');
    else
      $error = false;
    return $error;
  }


  private function getTelegramBotToken(){
    global $site, $L;
    $botToken = $this->getValue('botToken');
    return $botToken;
  }
  private function getTelegramChatId(){
    global $site, $L;
    $chatId = $this->getValue('chatId');
    return $chatId;
  }

  private function getSubject(){
    global $site, $L;
    $subject = $this->getValue('subject');
    if(empty($subject)){
      $subject = $L->get('New contact from'). ' - ' .$site->title();
    }
    return $subject;
  }


  private function getEmailText(){
    global $L;
    if($this->isHtml()) {
      $emailText  = '<b>'.$L->get('Name').': </b>'.$this->senderName.'<br>';
      $emailText .= '<b>'.$L->get('Email').': </b>'.$this->senderEmail.'<br>';
      $emailText .= '<b>'.$L->get('Message').': </b><br>'.$this->message.'<br>';
    } else {
      $emailText  = $L->get('Name').': '.$this->senderName."\r\n\r";
      $emailText .= $L->get('Email').': '.$this->senderEmail."\r\n\r";
      $emailText .= $L->get('Message').': '."\r\n".$this->message."\r\n\r";
    }
    return $emailText;
  }


  private function frontendMessage(){
    global $L;
    if($this->success) {
      $html = '<div class="alert alert-success">' .$L->get('thank-you-for-contact'). '</div>' ."\r\n";
    } elseif(!is_bool($this->error)) {
      $html = '<div class="alert alert-danger">' .$this->error. '</div>' ."\r\n";
    } elseif($this->error) {
      $html = '<div class="alert alert-danger">' .$L->get('an-error-occurred-while-sending'). '</div>' ."\r\n";
    } else {
      $html = '';
    }
    return $html;
  }

  private function useSendmail(){
    $success = false;
    $success = mail($this->getValue('botToken'), $this->getSubject(), $this->getEmailText());            
    if(!$success){
      $this->error = true;
    }
    return $success;
  }


  private function clearForm(){
    $this->senderEmail = '';
    $this->senderName = '';
    $this->message = '';
  }

  private function includeContactForm(){
    global $page, $security, $L;
    $template = THEME_DIR_PHP . 'contact-telegram.php';
    if(file_exists($template)) {
      include($template);
    } else {
      include(__DIR__ . DS . 'layout' . DS . 'contact-telegram.php');
    }
  }
}