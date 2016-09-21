<?php
$name = $email = $message = "";

//antispam
//if($_SERVER["REQUEST_METHOD"] == "POST" && empty($_POST['url']) && empty($_POST['website']) && empty($_POST['comment']))
//antispam

//Does nothing if antispam fields are filled out
if($_SERVER["REQUEST_METHOD"] == "POST")
{
	$name = trim($_POST['name']);
	$email = trim($_POST['email']);
	$message = trim($_POST['message']);
	
	//log obvious spam
	 if(!empty($_POST['url']) || !empty($_POST['website']) || !empty($_POST['comment']))
	 {
	 	$_SESSION['captcha'] = true;
	 	$stmt = $conn->prepare("INSERT INTO honeypot(url, website, comment, name, email, message, ip) VALUES (:url, :website, :comment, :name, :email, :message, :ip)");
		$stmt->bindParam(':url', $_POST['url']);
		$stmt->bindParam(':website', $_POST['website']);
		$stmt->bindParam(':comment', $_POST['comment']);
		$stmt->bindParam(':name', $name);
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':message', $message);
		$stmt->bindParam(':ip', $_SERVER["REMOTE_ADDR"]);
		$stmt->execute();
	 }
	
	if(isset($_COOKIE['PHPSESSID']))
	{
		if(time() - $_SESSION['lastSubmitted'] < 30)
		{
		$errors[] = "Wait 30 seconds between subsequent posts";
		}
		if(time() - $_SESSION['time'] < 5)
		{
			$errors[] = "Please wait 5 seconds before posting";
		}
	}
	else $errors[] = "Enable browser cookies";

	if(!empty($name))
	{
		if(strlen($name) > 70)
		{
			$errors[] = "Name exceeds 70 character limit";
		}
		if(!preg_match("/^[a-zA-Z .]*$/", $name))
		{
			$errors[] = "Name contains invalid characters";
		}
	}
	else $errors[] = "Name field required";
	
	if(!empty($email))
	{
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$errors[] = "Email address is invalid";
		}
	}
	else $errors[] = "Email field required";

	if(strlen($message) > 1000)
	{
		$errors[] = "Message exceeds 1000 character limit";
	}
	if($message != strip_tags($message)) 
	{
		$errors[] = "Message contains HTML";
	}
	/*if(preg_match('/https?|www\.?|href\=|url\=/', $message))
	{
		$errors[] = "Message contains a url";
	}*/
	if (preg_match("#https?\:\/\/|www\.|\b(?<!@)((?=[a-z0-9-]{1,63}\.)(xn--)?[a-z0-9]+(-[a-z0-9]+)*\.)+('.$tld.')\b#i", $message))
	{
		$errors[] = "Message contains a url";
	}

	if($_SESSION['captcha'] == true)
	{
		$secret = '[google recaptcha secret id]';
		$recaptcha = new \ReCaptcha\ReCaptcha($secret);
		$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
		if(!$resp->isSuccess())
		{
			$errors[] = "Invalid or Incomplete ReCaptcha response";
		}
	}
	
	//Require captcha if user ip is listed in httpBL. Insert attempted comment into spam database
	if(httpBL() || blogspambl())
	{
		if($_SESSION['captcha'] == false)
		{
			$errors[] = "Please prove you are not a robot";
			$stmt = $conn->prepare("INSERT INTO spam(name, email, message, ip) VALUES (:name, :email, :message, :ip)");
			$stmt->bindParam(':name', $name);
			$stmt->bindParam(':email', $email);
			$stmt->bindParam(':message', $message);
			$stmt->bindParam(':ip', $_SERVER["REMOTE_ADDR"]);
			$stmt->execute();
			$_SESSION['captcha'] = true;
		}
	}
	
	//Insert into database
	if(empty($errors))
	{
		$stmt = $conn->prepare("INSERT INTO guestbook(name, email, message, ip) VALUES (:name, :email, :message, :ip)");
		$stmt->bindParam(':name', $name);
		$stmt->bindParam(':email', $email);
		$stmt->bindParam(':message', $message);
		$stmt->bindParam(':ip', $_SERVER["REMOTE_ADDR"]);
		$stmt->execute();
		$_SESSION['lastSubmitted'] = time();
		header("Location: index.php");
		exit();
	}
}

// Define Functions
function httpBL() //check if the user ip is listed as a comment spammer by httpbl.org or blogspambl.com
{
	$key = '[httpbl key]';
	$lookup = $key . '.' . implode('.', array_reverse(explode('.',$_SERVER['REMOTE_ADDR']))).'.dnsbl.httpbl.org';
	$result = explode( '.', gethostbyname($lookup));
	if($result[0] == 127)
	{
		$activity = $result[1];
		$threat = $result[2];
		$type = $result[3];

		if($type >= 4 && $threat > 0) // Comment spammer with any threat level
		{
			return true;
		}
	}
}
function blogspambl()
{
	$clientip = explode(".", $_SERVER['REMOTE_ADDR']);
	$revip = array_reverse($clientip);
	$query = implode(".", $revip) . ".list.blogspambl.com";
	$result = explode(".", gethostbyname($query));

	if ($result[0] == 127) 
	{
		return true;
	}
}
?>
