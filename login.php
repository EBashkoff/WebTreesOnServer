<?php

// Register as a new User or request new password if it is lost
//
// webtrees: Web based Family History software
// Copyright (C) 2013 webtrees development team.
//
// Derived from PhpGedView
// Copyright (C) 2002 to 2009  PGV Development Team.  All rights reserved.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// $Id: login.php 14845 2013-03-01 15:20:26Z lukasz $

define('WT_SCRIPT_NAME', 'login.php');
require './includes/session.php';
require WT_ROOT . 'includes/functions/functions_edit.php';

//  *************************************************************************************         
//  **********        
//  **********MY SITE EDIT:  ADD THE FOLLOWING LINE FOR GOOGLE ANALYTICS         
//
//
include_once("myanalyticstracking.php");

// If we are already logged in, then go to the home page

if (WT_USER_ID) {
    header('Location: ' . WT_SERVER_NAME . WT_SCRIPT_PATH);
    exit;
}

$controller = new WT_Controller_Page();

$REQUIRE_ADMIN_AUTH_REGISTRATION = WT_Site::preference('REQUIRE_ADMIN_AUTH_REGISTRATION');

$action = safe_POST('action');
$user_realname = safe_POST('user_realname');
$user_name = safe_POST('user_name', WT_REGEX_USERNAME);
$user_email = safe_POST('user_email', WT_REGEX_EMAIL);
$user_password01 = safe_POST('user_password01', WT_REGEX_PASSWORD);
$user_password02 = safe_POST('user_password02', WT_REGEX_PASSWORD);
$user_comments = safe_POST('user_comments');
$user_password = safe_POST('user_password', WT_REGEX_UNSAFE); // Can use any password that was previously stored
$user_hashcode = safe_POST('user_hashcode');
$url = safe_POST('url', WT_REGEX_URL);
$username = safe_POST('username', WT_REGEX_USERNAME);
$password = safe_POST('password', WT_REGEX_UNSAFE); // Can use any password that was previously stored
$usertime = safe_POST('usertime');

// These parameters may come from the URL which is emailed to users.
if (empty($action))
    $action = safe_GET('action');
if (empty($user_name))
    $user_name = safe_GET('user_name', WT_REGEX_USERNAME);
if (empty($user_hashcode))
    $user_hashcode = safe_GET('user_hashcode');

// This parameter may come from generated login links
if (!$url) {
    $url = safe_GET('url', WT_REGEX_URL);
}

$message = '';

switch ($action) {
    case 'login':
    default:
        if ($action == 'login') {
            $user_id = authenticateUser($username, $password);
            switch ($user_id) {
                case -1: // not validated
                    $message = WT_I18N::translate('This account has not been verified.  Please check your email for a verification message.');
                    break;

                case -2: // not approved
                    $message = WT_I18N::translate('This account has not been approved.  Please wait for an administrator to approve it.');
                    break;

                case -3: // bad password
                case -4: // bad username
                    $message = WT_I18N::translate('The username or password is incorrect.');
                    break;

                case -5: // no cookies
                    $message = WT_I18N::translate('You cannot login because your browser does not accept cookies.');
                    break;

                default: // Success
                    if ($usertime) {
                        $WT_SESSION->timediff = WT_TIMESTAMP - strtotime($usertime);
                    } else {
                        $WT_SESSION->timediff = 0;
                    }
                    $WT_SESSION->locale = get_user_setting($user_id, 'language');
                    $WT_SESSION->theme_dir = get_user_setting($user_id, 'theme');

                    // If we’ve clicked login from the login page, we don’t want to go back there.
                    if (strpos($url, WT_SCRIPT_NAME) === 0) {
                        $url = 'index.php';
                    }
                    // Redirect to the target URL
                    header('Location: ' . WT_SERVER_NAME . WT_SCRIPT_PATH . $url);

                    // Explicitly write the session data before we exit,
                    // as it doesn't always happen when using APC.
                    Zend_Session::writeClose();
                    exit;
            }
        }

        $controller
                ->setPageTitle(WT_I18N::translate('Login'))
                ->pageHeader()
                ->addInlineJavascript('
			jQuery("#new_passwd_form").hide();
			jQuery("#passwd_click").click(function() {
				jQuery("#new_passwd_form").slideToggle(100, function() {
					jQuery("#new_passwd_username").focus()
				});
			});
		');

        echo '<div id="login-page">';
        echo '<div id="login-text">';

        if (WT_GED_ID) {
            switch ($WELCOME_TEXT_AUTH_MODE) {
                case 1:
                    echo WT_I18N::translate('<center><b>Welcome to this Genealogy website</b></center><br />Access to this site is permitted to every visitor who has a user account.<br /><br />If you have a user account, you can login on this page.  If you don\'t have a user account, you can apply for one by clicking on the appropriate link below.<br /><br />After verifying your application, the site administrator will activate your account.  You will receive an email when your application has been approved.');
                    break;
                case 2:
                    echo WT_I18N::translate('<center><b>Welcome to this Genealogy website</b></center><br />Access to this site is permitted to <u>authorized</u> users only.<br /><br />If you have a user account you can login on this page.  If you don\'t have a user account, you can apply for one by clicking on the appropriate link below.<br /><br />After verifying your information, the administrator will either approve or decline your account application.  You will receive an email message when your application has been approved.');
                    break;
                case 3:
                    echo WT_I18N::translate('<center><b>Welcome to this Genealogy website</b></center><br />Access to this site is permitted to <u>family members only</u>.<br /><br />If you have a user account you can login on this page.  If you don\'t have a user account, you can apply for one by clicking on the appropriate link below.<br /><br />After verifying the information you provide, the administrator will either approve or decline your request for an account.  You will receive an email when your request is approved.');
                    break;
                case 4:
                    echo WT_I18N::translate('<center><b>Welcome to this Genealogy website</b></center><br />Access is permitted to users who have an account and a password for this website.');
                    if (get_gedcom_setting(WT_GED_ID, 'WELCOME_TEXT_CUST_HEAD')) {
                        echo '<p>', get_gedcom_setting(WT_GED_ID, 'WELCOME_TEXT_AUTH_MODE_' . WT_LOCALE), '</p>';
                    }
                    break;
            }
        }


//  *************************************************************************************         
//  **********        
//  **********MY SITE EDIT:  COMMENT OUT THE FOLLOWING IF BLOCK AND ADD THE 5 LINES INCLUDING the PHP delimiters       
//        if (!isset($_COOKIE[WT_SESSION_NAME])) {
//            echo
//            '<p class="ui-state-error">' .
//            WT_I18N::translate('This site uses cookies to keep track of your login status.<br /><br />Cookies do not appear to be enabled in your browser. You must enable cookies for this site before you can login.  You can consult your browser\'s help documentation for information on enabling cookies.') .
//            '</p>';
//        }
?> 
<script type="text/javascript">
document.getElementById("body").style.display="none";
</script>
<?php 
        echo '</div>'; //close "login-text"
        echo '<div id="login-box">
		<form id="login-form" name="login-form" method="post" action="', WT_LOGIN_URL, '" onsubmit="t = new Date(); this.usertime.value=t.getFullYear()+\'-\'+(t.getMonth()+1)+\'-\'+t.getDate()+\' \'+t.getHours()+\':\'+t.getMinutes()+\':\'+t.getSeconds();return true;">
		<input type="hidden" name="action" value="login">
		<input type="hidden" name="url" value="', htmlspecialchars($url), '">
		<input type="hidden" name="usertime" value="">';
        if (!empty($message))
            echo '<span class="error"><br><b>', $message, '</b><br><br></span>';
        echo '<div>
			<label for="username">', WT_I18N::translate('Username'),
        '<input type="text" id="username" name="username" value="', htmlspecialchars($username), '" class="formField" autofocus>
			</label>
		</div>
		<div>
			<label for="password">', WT_I18N::translate('Password'),
        '<input type="password" id="password" name="password" class="formField">
			</label>
		</div>
		<div>
			<input type="submit" value="', WT_I18N::translate('Login'), '">
		</div>
		<div>
			<a href="#" id="passwd_click">', WT_I18N::translate('Request new password'), '</a>
		</div>';
        if (WT_Site::preference('USE_REGISTRATION_MODULE')) {
            echo '<div><a href="' . WT_LOGIN_URL . '?action=register">', WT_I18N::translate('Request new user account'), '</a></div>';
        }
        echo '</form>';

        // hidden New Password block
        echo '<div id="new_passwd">
		<form id="new_passwd_form" name="new_passwd_form" action="' . WT_LOGIN_URL . '" method="post">
		<input type="hidden" name="action" value="requestpw">
		<h4>', WT_I18N::translate('Lost password request'), '</h4>
		<div>
			<label for="new_passwd_username">', WT_I18N::translate('Username or email address'),
        '<input type="text" id="new_passwd_username" name="new_passwd_username" value="">
			</label>
		</div>
		<div><input type="submit" value="', /* I18N: button label */ WT_I18N::translate('continue'), '"></div>
		</form>
	</div>';
        echo '</div>';

        echo '</div>';
        break;

    case 'requestpw':
        $controller
                ->setPageTitle(WT_I18N::translate('Lost password request'))
                ->pageHeader();
        echo '<div id="login-page">';
        $user_name = safe_POST('new_passwd_username', WT_REGEX_USERNAME);

        $user_id = WT_DB::prepare(
                        "SELECT user_id FROM `##user` WHERE ? IN (user_name, email)"
                )->execute(array($user_name))->fetchOne();
        if ($user_id) {
            $passchars = 'abcdefghijklmnopqrstuvqxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $user_new_pw = '';
            $max = strlen($passchars) - 1;
            for ($i = 0; $i < 8; $i++) {
                $index = rand(0, $max);
                $user_new_pw .= $passchars{$index};
            }

            set_user_password($user_id, $user_new_pw);
            set_user_setting($user_id, 'pwrequested', 1);
            $user_name = get_user_name($user_id);

            $mail_body = '';
            $mail_body .= WT_I18N::translate('Hello %s ...', getUserFullName($user_id)) . "\r\n\r\n";
            $mail_body .= WT_I18N::translate('A new password was requested for your user name.') . "\r\n\r\n";
            $mail_body .= WT_I18N::translate('Username') . ": " . $user_name . "\r\n";

            $mail_body .= WT_I18N::translate('Password') . ": " . $user_new_pw . "\r\n\r\n";
            $mail_body .= WT_I18N::translate('Recommendation:') . "\r\n";
            $mail_body .= WT_I18N::translate('Please click on the link below or paste it into your browser, login with the new password, and change it immediately to keep the integrity of your data secure.') . "\r\n\r\n";
            $mail_body .= WT_I18N::translate('After you have logged in, select the «My Account» link under the «My Page» menu and fill in the password fields to change your password.') . "\r\n\r\n";

            if ($TEXT_DIRECTION == 'rtl') {
                $mail_body .= "<a href=\"" . WT_SERVER_NAME . WT_SCRIPT_PATH . "\">" . WT_SERVER_NAME . WT_SCRIPT_PATH . "</a>";
            } else {
                $mail_body .= WT_SERVER_NAME . WT_SCRIPT_PATH;
            }

            require_once WT_ROOT . 'includes/functions/functions_mail.php';
            webtreesMail(getUserEmail($user_id), $WEBTREES_EMAIL, WT_I18N::translate('Lost password request'), $mail_body);
        }
        // Show a success message, even if the user account does not exist.
        // Otherwise this page can be used to guess/test usernames.
        // A genuine user will hopefully always know their own email address.
        echo
        '<div class="confirm"><p>',
        /* I18N: %s is a username */
        WT_I18N::translate('A new password has been created and emailed to %s.  You can change this password after you login.', $user_name),
        '</p></div>';
        AddToLog('Password request was sent to user: ' . $user_name, 'auth');
        echo '</div>';
        break;

    case 'register':
        if (!WT_Site::preference('USE_REGISTRATION_MODULE')) {
            header('Location: ' . WT_SERVER_NAME . WT_SCRIPT_PATH);
            exit;
        }

        $controller->setPageTitle(WT_I18N::translate('Request new user account'));

        // The form parameters are mandatory, and the validation errors are shown in the client.
        if ($WT_SESSION->good_to_send && $user_name && $user_password01 && $user_password01 == $user_password02 && $user_realname && $user_email && $user_comments) {

            // These validation errors cannot be shown in the client.
            if (get_user_id($user_name)) {
                WT_FlashMessages::addMessage(WT_I18N::translate('Duplicate user name.  A user with that user name already exists.  Please choose another user name.'));
            } elseif (get_user_by_email($user_email)) {
                WT_FlashMessages::addMessage(WT_I18N::translate('Duplicate email address.  A user with that email already exists.'));
            } elseif (preg_match('/(?!' . preg_quote(WT_SERVER_NAME, '/') . ')(((?:ftp|http|https):\/\/)[a-zA-Z0-9.-]+)/', $user_comments, $match)) {
                WT_FlashMessages::addMessage(
                        WT_I18N::translate('You are not allowed to send messages that contain external links.') . ' ' .
                        WT_I18N::translate('You should delete the “%1$s” from “%2$s” and try again.', $match[2], $match[1])
                );
                AddToLog('Possible spam registration from "' . $user_name . '"/"' . $user_email . '", IP="' . $WT_REQUEST->getClientIp() . '", comments="' . $user_comments . '"', 'auth');
            } else {
                // Everything looks good - create the user
                $controller->pageHeader();
                AddToLog('User registration requested for: ' . $user_name, 'auth');

                $user_id = create_user($user_name, $user_realname, $user_email, $user_password01);

                set_user_setting($user_id, 'language', WT_LOCALE);
                set_user_setting($user_id, 'verified', 0);
                set_user_setting($user_id, 'verified_by_admin', !$REQUIRE_ADMIN_AUTH_REGISTRATION);
                set_user_setting($user_id, 'reg_timestamp', date('U'));
                set_user_setting($user_id, 'reg_hashcode', md5(uniqid(rand(), true)));
                set_user_setting($user_id, 'contactmethod', 'messaging2');
                set_user_setting($user_id, 'visibleonline', 1);
                set_user_setting($user_id, 'editaccount', 1);
                set_user_setting($user_id, 'auto_accept', 0);
                set_user_setting($user_id, 'canadmin', 0);
                set_user_setting($user_id, 'sessiontime', 0);

                // Generate an email in the admin’s language
                $webmaster_user_id = get_gedcom_setting(WT_GED_ID, 'WEBMASTER_USER_ID');
                WT_I18N::init(get_user_setting($webmaster_user_id, 'language'));

                $mail1_body =
                        WT_I18N::translate('Hello Administrator ...') . "\r\n\r\n" .
                        /* I18N: %s is a server name/URL */
                        WT_I18N::translate('A prospective user has registered with webtrees at %s.', WT_SERVER_NAME . WT_SCRIPT_PATH . ' ' . strip_tags(WT_TREE_TITLE)) . "\r\n\r\n" .
                        WT_I18N::translate('Username') . ' ' . $user_name . "\r\n" .
                        WT_I18N::translate('Real name') . ' ' . $user_realname . "\r\n" .
                        WT_I18N::translate('Email Address:') . ' ' . $user_email . "\r\n\r\n" .
                        WT_I18N::translate('Comments') . ' ' . $user_comments . "\r\n\r\n" .
                        WT_I18N::translate('The user has been sent an e-mail with the information necessary to confirm the access request') . "\r\n\r\n";
                if ($REQUIRE_ADMIN_AUTH_REGISTRATION) {
                    $mail1_body.=WT_I18N::translate('You will be informed by e-mail when this prospective user has confirmed the request.  You can then complete the process by activating the user name.  The new user will not be able to login until you activate the account.') . "\r\n";
                } else {
                    $mail1_body.=WT_I18N::translate('You will be informed by e-mail when this prospective user has confirmed the request.  After this, the user will be able to login without any action on your part.') . "\r\n";
                }
                $mail1_body.=
                        "\r\n" .
                        "=--------------------------------------=\r\nIP ADDRESS: " . $WT_REQUEST->getClientIp() . "\r\n" .
                        "DNS LOOKUP: " . gethostbyaddr($WT_REQUEST->getClientIp()) . "\r\n" .
                        "LANGUAGE: " . WT_LOCALE . "\r\n";

                $mail1_subject = /* I18N: %s is a server name/URL */ WT_I18N::translate('New registration at %s', WT_SERVER_NAME . WT_SCRIPT_PATH . ' ' . strip_tags(WT_TREE_TITLE));
                $mail1_to = $WEBTREES_EMAIL;
                $mail1_from = $user_email;
                $mail1_method = get_user_setting($webmaster_user_id, 'contact_method');
                WT_I18N::init(WT_LOCALE);

                echo '<div id="login-register-page">';

                require_once WT_ROOT . 'includes/functions/functions_mail.php';

                // Generate an email in the user’s language
                $mail2_body =
                        WT_I18N::translate('Hello %s ...', $user_realname) . "\r\n\r\n" .
                        /* I18N: %1$s is the site URL and %2$s is an email address */
                        WT_I18N::translate('You (or someone claiming to be you) has requested an account at %1$s using the email address %2$s.', WT_SERVER_NAME . WT_SCRIPT_PATH . ' ' . strip_tags(WT_TREE_TITLE), $user_email) . '  ' .
                        WT_I18N::translate('Information about the request is shown under the link below.') . "\r\n\r\n" .
                        WT_I18N::translate('Please click on the following link and fill in the requested data to confirm your request and email address.') . "\r\n\r\n";
                if ($TEXT_DIRECTION == 'rtl') {
                    $mail2_body .= "<a href=\"";
                    $mail2_body .= WT_LOGIN_URL . "?user_name=" . urlencode($user_name) . "&user_hashcode=" . urlencode(get_user_setting($user_id, 'reg_hashcode')) . "&action=userverify\">";
                }
                $mail2_body .= WT_LOGIN_URL . "?user_name=" . urlencode($user_name) . "&user_hashcode=" . urlencode(get_user_setting($user_id, 'reg_hashcode')) . "&action=userverify";
                if ($TEXT_DIRECTION == 'rtl') {
                    $mail2_body .= "</a>";
                }
                $mail2_body.=
                        "\r\n" .
                        WT_I18N::translate('Username') . " " . $user_name . "\r\n" .
                        WT_I18N::translate('Verification code:') . " " . get_user_setting($user_id, 'reg_hashcode') . "\r\n\r\n" .
                        WT_I18N::translate('Comments') . ": " . $user_comments . "\r\n\r\n" .
                        WT_I18N::translate('If you didn\'t request an account, you can just delete this message.') . "  " .
                        WT_I18N::translate('You won\'t get any more email from this site, because the account request will be deleted automatically after seven days.') . "\r\n";
                $mail2_subject = /* I18N: %s is a server name/URL */ WT_I18N::translate('Your registration at %s', WT_SERVER_NAME . WT_SCRIPT_PATH);
                $mail2_to = $user_email;
                $mail2_from = $WEBTREES_EMAIL;

                // Send user message by email only
                webtreesMail($mail2_to, $mail2_from, $mail2_subject, $mail2_body);

                // Send admin message by email and/or internal messaging
                webtreesMail($mail1_to, $mail1_from, $mail1_subject, $mail1_body);
                if (WT_Site::preference('STORE_MESSAGES') && $mail1_method != 'messaging3' && $mail1_method != 'mailto' && $mail1_method != 'none') {
                    WT_DB::prepare("INSERT INTO `##message` (sender, ip_address, user_id, subject, body) VALUES (? ,? ,? ,? ,?)")
                            ->execute(array($user_email, $WT_REQUEST->getClientIp(), $webmaster_user_id, $mail1_subject, $mail1_body));
                }

                echo '<div class="confirm"><p>', WT_I18N::translate('Hello %s ...<br />Thank you for your registration.', $user_realname), '</p><p>';
                if ($REQUIRE_ADMIN_AUTH_REGISTRATION) {
                    echo WT_I18N::translate('We will now send a confirmation email to the address <b>%s</b>. You must verify your account request by following instructions in the confirmation email. If you do not confirm your account request within seven days, your application will be rejected automatically.  You will have to apply again.<br /><br />After you have followed the instructions in the confirmation email, the administrator still has to approve your request before your account can be used.<br /><br />To login to this site, you will need to know your user name and password.', $user_email);
                } else {
                    echo WT_I18N::translate('We will now send a confirmation email to the address <b>%s</b>. You must verify your account request by following instructions in the confirmation email. If you do not confirm your account request within seven days, your application will be rejected automatically.  You will have to apply again.<br /><br />After you have followed the instructions in the confirmation email, you can login.  To login to this site, you will need to know your user name and password.', $user_email);
                }
                echo '</p>
			</div>';
                echo '</div>';

//  *************************************************************************************
//  **********
//  **********MY SITE EDIT:  ADD THE FOLLOWING SINGLE LINE FOR REGISTRATION PAGE CUSTOMIZATION
//
//     
                require "myaltregisterpage.php";

                exit;
            }
        }

        $WT_SESSION->good_to_send = true;
        $controller
                ->pageHeader()
                ->addInlineJavascript('function regex_quote(str) {return str.replace(/[\\\\.?+*()[\](){}|]/g, "\\\\$&");}');

        echo '<div id="login-register-page">
		<h2>', $controller->getPageTitle(), '</h2>';
        if ($SHOW_REGISTER_CAUTION) {
            echo '<div id="register-text">';
            echo WT_I18N::translate('<div class="largeError">Notice:</div><div class="error">By completing and submitting this form, you agree:<ul><li>to protect the privacy of living people listed on our site;</li><li>and in the text box below, to explain to whom you are related, or to provide us with information on someone who should be listed on our site.</li></ul></div>');
            echo '</div>';
        }
        echo '<div id="register-box">
			<form id="register-form" name="register-form" method="post" action="' . WT_LOGIN_URL . '" onsubmit="return checkform(this);" autocomplete="off">
			<input type="hidden" name="action" value="register">
			<h4>', WT_I18N::translate('All fields must be completed.'), '</h4><hr>
			<div>
				<label for="user_realname">', WT_I18N::translate('Real name'), help_link('real_name'),
        '<input type="text" id="user_realname" name="user_realname" required maxlength="64" value="', htmlspecialchars($user_realname), '" autofocus>
				</label>		
			</div>
			<div>
				<label for="user_email">', WT_I18N::translate('Email address'), help_link('email'),
        '<input type="email" id="user_email" name="user_email" required maxlength="64" value="', htmlspecialchars($user_email), '">
				</label>
			</div>
			<div>
				<label for="username">', WT_I18N::translate('Desired user name'), help_link('username'),
        '<input type="text" id="username" name="user_name" required maxlength="32" value="', htmlspecialchars($user_name), '">
				</label>
			</div>
			<div>
				<label for="user_password01">', WT_I18N::translate('Desired password'), help_link('password'),
        '<input type="password" id="user_password01" name="user_password01" value="', htmlspecialchars($user_password01), '" required placeholder="', /* I18N: placeholder text for new-password field */ WT_I18N::plural('Use at least %s character.', 'Use at least %s characters.', WT_MINIMUM_PASSWORD_LENGTH, WT_I18N::number(WT_MINIMUM_PASSWORD_LENGTH)), '" pattern="' . WT_REGEX_PASSWORD . '" onchange="form.user_password02.pattern = regex_quote(this.value);">
				</label>
			</div>
			<div>
				<label for="user_password02">', WT_I18N::translate('Confirm password'), help_link('password_confirm'),
        '<input type="password" id="user_password02" name="user_password02" value="', htmlspecialchars($user_password02), '" required placeholder="', /* I18N: placeholder text for repeat-password field */ WT_I18N::translate('Type the password again.'), '" pattern="' . WT_REGEX_PASSWORD . '">
				</label>
			</div>
			<div>
				<label for="user_comments">', WT_I18N::translate('Comments'), help_link('register_comments'),
        '<textarea cols="50" rows="5" id="user_comments" name="user_comments" required placeholder="', /* I18N: placeholder text for registration-comments field */ WT_I18N::translate('Explain why you are requesting an account.'), '">',
        htmlspecialchars($user_comments),
        '</textarea>
				</label>
			</div>
			<hr>
			<div id="registration-submit">
				<input type="submit" value="', WT_I18N::translate('continue'), '">
			</div>
		</form>
	</div>
	</div>';

//  *************************************************************************************
//  **********
//  **********MY SITE EDIT:  ADD THE FOLLOWING SINGLE LINE FOR REGISTRATION PAGE CUSTOMIZATION
//
//
        require "myaltregisterpage.php";

        break;
        
    case 'userverify':
        if (!WT_Site::preference('USE_REGISTRATION_MODULE')) {
            header('Location: ' . WT_SERVER_NAME . WT_SCRIPT_PATH);
            exit;
        }

        // Change to the new user’s language
        $user_id = get_user_id($user_name);
        WT_I18N::init(get_user_setting($user_id, 'language'));

        $controller->setPageTitle(WT_I18N::translate('User verification'));
        $controller->pageHeader();

        echo '<div id="login-register-page">
		<form id="verify-form" name="verify-form" method="post" action="', WT_LOGIN_URL, '">
			<input type="hidden" name="action" value="verify_hash">
			<h4>', WT_I18N::translate('User verification'), '</h4>
			<div>
				<label for="username">', WT_I18N::translate('Username'), '</label>
				<input type="text" id="username" name="user_name" value="', $user_name, '">
			</div>
			<div>
			<label for="user_password">', WT_I18N::translate('Password'), '</label>
			<input type="password" id="user_password" name="user_password" value="" autofocus>
			</div>
			<div>
			<label for="user_hashcode">', WT_I18N::translate('Verification code:'), '</label>
			<input type="text" id="user_hashcode" name="user_hashcode" value="', $user_hashcode, '">
			</div>
			<div>
				<input type="submit" value="', WT_I18N::translate('Send'), '">
			</div>
		</form>
	</div>';
        break;

    case 'verify_hash':
        if (!WT_Site::preference('USE_REGISTRATION_MODULE')) {
            header('Location: ' . WT_SERVER_NAME . WT_SCRIPT_PATH);
            exit;
        }
        AddToLog('User attempted to verify hashcode: ' . $user_name, 'auth');

        // switch language to webmaster settings
        $webmaster_user_id = get_gedcom_setting(WT_GED_ID, 'WEBMASTER_USER_ID');
        WT_I18N::init(get_user_setting($webmaster_user_id, 'language'));

        $user_id = get_user_id($user_name);
        $mail1_body =
                WT_I18N::translate('Hello Administrator ...') . "\r\n\r\n" .
                /* I18N: %1$s is a real-name, %2$s is a username, %3$s is an email address */
                WT_I18N::translate('A new user (%1$s) has requested an account (%2$s) and verified an email address (%3$s).', getUserFullName($user_id), $user_name, getUserEmail($user_id)) . "\r\n\r\n";
        if ($REQUIRE_ADMIN_AUTH_REGISTRATION && !get_user_setting($user_id, 'verified_by_admin')) {
            $mail1_body .= WT_I18N::translate('You now need to review the account details, and set the “approved” status to “yes”.') . "\r\n";
        } else {
            $mail1_body .= WT_I18N::translate('You do not have to take any action; the user can now login.') . "\r\n";
        }
        if ($TEXT_DIRECTION == 'rtl') {
            $mail1_body .= "<a href=\"";
            $mail1_body .= WT_SERVER_NAME . WT_SCRIPT_PATH . "admin_users.php?filter=" . rawurlencode($user_name) . "\">";
        }
        $mail1_body .= WT_SERVER_NAME . WT_SCRIPT_PATH . "admin_users.php?filter=" . rawurlencode($user_name);
        if ($TEXT_DIRECTION == "rtl") {
            $mail1_body .= "</a>";
        }
        $mail1_body.=
                "\r\n\r\n" .
                "=--------------------------------------=\r\n" .
                "IP ADDRESS: " . $WT_REQUEST->getClientIp() . "\r\n" .
                "DNS LOOKUP: " . gethostbyaddr($WT_REQUEST->getClientIp()) . "\r\n" .
                "LANGUAGE: " . WT_LOCALE . "\r\n";

        $mail1_to = $WEBTREES_EMAIL;
        $mail1_from = getUserEmail($user_id);
        $mail1_subject = /* I18N: %s is a server name/URL */ WT_I18N::translate('New user at %s', WT_SERVER_NAME . WT_SCRIPT_PATH . ' ' . strip_tags(WT_TREE_TITLE));
        $mail1_method = get_user_setting($webmaster_user_id, 'CONTACT_METHOD');

        // Change to the new user’s language
        WT_I18N::init(get_user_setting($user_id, 'language'));

        $controller->setPageTitle(WT_I18N::translate('User verification'));
        $controller->pageHeader();

        echo '<div id="login-register-page">';
        echo '<h2>' . WT_I18N::translate('User verification') . '</h2>';
        echo '<div id="user-verify">';
        echo WT_I18N::translate('The data for the user <b>%s</b> was checked.', $user_name);
        if ($user_id) {
            $pw_ok = check_user_password($user_id, $user_password);
            $hc_ok = get_user_setting($user_id, 'reg_hashcode') == $user_hashcode;
            if ($pw_ok && $hc_ok) {
                require_once WT_ROOT . 'includes/functions/functions_mail.php';
                webtreesMail($mail1_to, $mail1_from, $mail1_subject, $mail1_body);
                if (WT_Site::preference('STORE_MESSAGES') && $mail1_method != 'messaging3' && $mail1_method != 'mailto' && $mail1_method != 'none') {
                    WT_DB::prepare("INSERT INTO `##message` (sender, ip_address, user_id, subject, body) VALUES (? ,? ,? ,? ,?)")
                            ->execute(array($user_name, $WT_REQUEST->getClientIp(), $webmaster_user_id, $mail1_subject, $mail1_body));
                }

                set_user_setting($user_id, 'verified', 1);
                set_user_setting($user_id, 'pwrequested', null);
                set_user_setting($user_id, 'reg_timestamp', date("U"));
                set_user_setting($user_id, 'reg_hashcode', null);
                if (!$REQUIRE_ADMIN_AUTH_REGISTRATION) {
                    set_user_setting($user_id, 'verified_by_admin', 1);
                }
                AddToLog('User verified: ' . $user_name, 'auth');

                echo '<br><br>' . WT_I18N::translate('You have confirmed your request to become a registered user.') . '<br><br>';
                if ($REQUIRE_ADMIN_AUTH_REGISTRATION && !get_user_setting($user_id, 'verified_by_admin')) {
                    echo WT_I18N::translate('The Administrator has been informed.  As soon as he gives you permission to login, you can login with your user name and password.');
                } else {
                    echo WT_I18N::translate('You can now login with your user name and password.');
                }
                echo '<br><br>';
            } else {
                echo '<br><br>';
                echo '<span class="warning">';
                echo WT_I18N::translate('Data was not correct, please try again');
                echo '</span><br><br>';
            }
        } else {
            echo '<br><br>';
            echo '<span class="warning">';
            echo WT_I18N::translate('Could not verify the information you entered.  Please try again or contact the site administrator for more information.');
            echo '</span>';
        }
        echo '</div>';
        echo '</div>';
        break;
}

//  *************************************************************************************         
//  **********        
//  **********MY SITE EDIT:  ADD THE FOLLOWING LINE FOR LOGIN PAGE CUSTOMIZATION       
//
//
require "myaltloginpage.php";