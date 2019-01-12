<?php 
/**
 *  Contact layout
 *
 *  @package Bludit
 *  @subpackage Contact
 *  @author Viktor G.
 *  @info: Duplicate this layout in your themes/YOUR_THEME/php/ 
 *	for a custom template.
 */	
?>
<form method="post" action="<?php echo '.' . DS . $page->slug(); ?>" class="contact3">
	<?php //echo $this->frontendMessage(); ?>
	
	<div class="form-group">
	   <input name="name" type="text" class="form-control" tabindex="0" placeholder="<?php echo $L->get('contact-name'); ?>" required>
	</div>
	<div class="form-group">
	   <input name="phone" type="tel" class="form-control" tabindex="0" pattern="^[ 0-9]+$" placeholder="<?php echo $L->get('contact-phone'); ?>" required>
	</div>
	<div class="form-group">
	<input id="email" type="email" name="email" value="<?php echo sanitize::email($this->senderEmail); ?>" placeholder="<?php echo $L->get('contact-email'); ?>" class="form-control" required>
	</div>
	<div class="form-group">
		<input name="theme" type="hidden" class="form-control"  value="<?php echo $L->get('contact-reason'); ?> <?php echo DOMAIN_BASE; ?>">
    </div>
    <div class="form-group">
	   <textarea id="message" rows="6" name="message" placeholder="<?php echo $L->get('contact-message'); ?>" class="form-control" required><?php echo sanitize::html($this->message); ?></textarea>
	</div>     
           
	<input type="checkbox" name="interested">
	<button id="submit" name="submit" type="submit" class="btn btn-primary"><?php echo $L->get('Send'); ?></button>
</form>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
if (!empty($_POST['name']) && !empty($_POST['phone'])){
	
  if (isset($_POST['name'])) {
    if (!empty($_POST['name'])){
  $name = strip_tags($_POST['name']);
  $nameFieldset = $L->get('contact-name');
  }
}

if (isset($_POST['phone'])) {
  if (!empty($_POST['phone'])){
  $phone = strip_tags($_POST['phone']);
  $phoneFieldset = $L->get('contact-phone');
  }
}

if (isset($_POST['email'])) {
  if (!empty($_POST['email'])){
  $email = strip_tags($_POST['email']);
  $emailFieldset = $L->get('contact-email');
  }
}

if (isset($_POST['message'])) {
  if (!empty($_POST['message'])){
  $message = strip_tags($_POST['message']);
  $messageFieldset = $L->get('contact-message');
  }
}

if (isset($_POST['theme'])) {
  if (!empty($_POST['theme'])){
  $theme = strip_tags($_POST['theme']);
  $themeFieldset = "";
  }
}

include '../plugin.php';
$botToken = $this->getTelegramBotToken();
$chatId = $this->getTelegramChatId();


$arr = array(
  $themeFieldset => $theme,
  $nameFieldset => $name,
  $phoneFieldset => $phone,
  $emailFieldset => $email,
  $messageFieldset => $message
);
foreach($arr as $key => $value) {
  $txt .= "<b>".$key."</b> ".$value."%0A";
};
$sendToTelegram = fopen("https://api.telegram.org/bot{$botToken}/sendMessage?chat_id={$chatId}&parse_mode=html&text={$txt}","r");


}

if ($sendToTelegram) {
   echo '<div class="alert alert-success">' .$L->get('thank-you-for-contact'). '</div>' ."\r\n";
    return true;
} else {
  echo '<div class="alert alert-danger">' .$L->get('an-error-occurred-while-sending'). '</div>' ."\r\n";
}
} else {
header ("Location: /");
}

?>