<?php

$base_url = 'http://localhost/s/';
$db['host'] = 'localhost';
$db['user'] = 'root';
$db['pass'] = '';
$db['db']   = 'database';
$db['table'] = 'shorturls';

if (isset($_GET['shorturl'])) {
    $idshort = trim($_GET['shorturl']);
    $id = base_convert($idshort, 36, 10);
    $db = new mysqli($db['host'], $db['user'], $db['pass'], $db['db']);
    if ($db->connect_errno) {
        die("MySQL Error (" . $db->connect_errno . "): " . $db->connect_error);
    }
    $query = "SELECT url FROM ".$db['table']." WHERE id = '$id'";
    if ($res = $db->query($query)) {
        if ($res->num_rows == 0) {
            header("Location: $base_url");
        } else {
            $fila = $res->fetch_assoc();
            $url = $fila['url'];
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: $url"); 
        }
    } else {
        echo "Query Error!";
    }
    $db->close();
    exit();
}

if (isset($_POST['url'])) {
    $url = trim($_POST['url']);
    
    if (!filter_var($url, FILTER_VALIDATE_URL) || 
        !preg_match('/^(ftp|http|https):\/\//', $url)) {
        exit("URL is invalid");
    }
    
    //Check URL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    if (!$result = curl_exec($ch)) {
            die("Error: Curl exec failed!");
    }
    $response = curl_getinfo($ch);
    curl_close($ch);
    
    if ($response['http_code'] == 404) {
        die("Error: Page not found. Error " . $response['http_code'] . ".");
    }

    $db = new mysqli($db['host'], $db['user'], $db['pass'], $db['db']);
    if ($db->connect_errno) {
        die("MySQL Error (" . $db->connect_errno . "): " . $db->connect_error);
    }
    $query = "SELECT id FROM ".$db['table']." WHERE url = '$url'";
    if ($res = $db->query($query)) {
        if ($res->num_rows == 0) {
            $query = "INSERT INTO ".$db['table']." (url) VALUES('$url')";
            $db->query($query);
            $id = $db->insert_id;
        } else {
            $fila = $res->fetch_assoc();
            $id = $fila['id'];
        }
        $shorturl = $base_url . base_convert($id, 10, 36);
        echo $shorturl;
    } else {
        echo "Query Error!";
    }
    $db->close();
} else {

?>

<!DOCTYPE html>
<html>
<head>
	<title>Short URLs</title>
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
</head>
<body>
	<div class="row vertical-offset-100">
		<div class="col-md-4 col-md-offset-4">
			<form role="form" id="form-url">
			<div class="form-group">
				<label for="url">URL</label>
			    <input type="url" placeholder="Enter URL" id="url" class="form-control">
			</div>
			<div class="form-group" id="url-corta"></div>
			<button class="btn btn-default btn-info" type="submit">Short</button>
			<button class="btn btn-default btn-info" type="reset">Reset</button>
			</form>
		</div>
	</div>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
	<script type="text/javascript">
		$('#form-url').submit(function() {
			$.ajax({
				type: "POST",
				url: "index.php",
				data: { url: $('#url').val() },
				success: function(data){
					$('#url-corta').html('<a href="'+data+'">'+data+'</a>');
					//alert(data);
				}
			});
		return false;
		});
		$('#form-url :reset').click(function() {
			$('#url-corta').empty();
		});
	</script>
</body>
</html>

<?php
}
?>
