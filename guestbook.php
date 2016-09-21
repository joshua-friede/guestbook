<?php
require_once __DIR__ . '/autoload.php';
$siteKey = '[google recaptcha site key]';
$conn = new PDO("mysql:host=[hostname];dbname=[database name]", "[username]", "[password]"); //Database
$perPage = 15; //Entries per page
$tld = file_get_contents('tld.txt'); // get list of valid domain names for verifying email addresses
session_start();
if(!isset($_SESSION['time']))
{
	$_SESSION['time'] = time();
}
if(!isset($_SESSION['captcha']))
{
	$_SESSION['captcha'] = false;
}
include 'add.php';
?>
<!DOCTYPE html>

<html lang="en">
<head>
	<meta content="IE=edge" http-equiv="X-UA-Compatible">
	<meta content="width=device-width, initial-scale=1" name="viewport">
	<title>Guestbook</title>
	<link href="/css/style.min.css" rel="stylesheet">
	<!--[if lt IE 9]>
	<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	<? if($_SESSION['captcha'] == true) echo '<script async src="//www.google.com/recaptcha/api.js"></script>'; ?>
</head>
<body>
<div id="wrapper">
	<header>
		<h1>Guestbook</h1>
		<p>A PHP/SQL guest commenting system with dyanmic anti-spam features.</p>
	</header>

		<main>
			<form action="/" method="post">
				<fieldset>
					<legend>Sign Guestbook</legend>
<?php
if(!empty($errors))
{
	echo '					<span class="error">';
	foreach($errors as $errorMessage)
	{
		echo $errorMessage.
		'<br>';
	}
	echo "</span>\n";
}
?>
					<input class="hide" name="url" type="url">
					<input class="hide" name="website" type="text">
					<textarea class="hide" name="comment"></textarea>
					<input maxlength="70" name="name" pattern="[A-Za-z. ]{1,70}" placeholder="Name" required title="May only contain A-z, period, and space" type="text" value="<? echo htmlspecialchars($name); ?>">
					<input maxlength="254" name="email" placeholder="Email" required type="email" value="<? echo htmlspecialchars($email); ?>">
					<textarea maxlength="1000" name="message" placeholder="Message" rows="4"><? echo htmlspecialchars($message); ?></textarea>
<? if($_SESSION['captcha'] == true) : ?>					<div class="g-recaptcha" data-sitekey="<?php echo $siteKey; ?>" style="width:304px;height:78px;margin:15px auto;"></div><? endif; ?>
					<button>Submit</button>
				</fieldset>
			</form>
			
			<section>
					
<?php
//Display Comments
if(isset($_GET["page"])){$page = $_GET["page"];}else{$page = 1;};
$start = ($page - 1) * $perPage;
$totalResults = $conn->query('SELECT COUNT(timestamp) FROM guestbook')->fetchColumn();
$totalPages = ceil($totalResults / $perPage);
echo "				<p>Page ".$page." of ".$totalPages." (".$totalResults." total entries)</p>\n\n";
if($page > $totalPages or $page < 1){header("Location: /");}
$stmt = $conn->prepare("SELECT * FROM guestbook ORDER BY timestamp DESC LIMIT $start,$perPage");
$stmt->execute();
while($data = $stmt->fetch()) :
?>
				<article>
					<header>
						<address>
							<? echo $data['name']; ?> &lt;<a href="mailto:<? echo $data['email']; ?>" rel="author"><? echo $data['email']; ?></a>&gt;
						</address>
						
						<time datetime="<? echo $data['timestamp']; ?>-04:00"><? echo date('g:i A - j M Y', strtotime($data['timestamp'])); ?></time>
					</header>
					<blockquote><? echo $data['message']; ?></blockquote>
				</article>
				
<?php
endwhile;
$conn = null;
//End Display Comments
if($page > 1){$prevLink = "?page=" . ($page - 1);$prevDisabled ="";} else{$prevLink = "#";$prevDisabled = "disabled";}
if($page < $totalPages){$nextLink = "?page=" . ($page + 1);$nextDisabled ="";} else{$nextLink = "#";$nextDisabled = "disabled";}
?>
			</section>
			
			<nav>
				<a <? echo $prevDisabled ?> href="<? echo $prevLink; ?>" rel="prev">Previous</a><a <? echo $nextDisabled ?> href="<? echo $nextLink; ?>" rel="next">Next</a>
			</nav>
				
		</main>

	<footer>
		<p><a href="https://github.com/joshua-friede/guestbook">View open source guestbook on github</p>
	</footer>
	
</div>
</body>
</html>
