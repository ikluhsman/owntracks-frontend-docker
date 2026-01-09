<!DOCTYPE html>
<head>
<title>My OwnTracks</title>
<style>
	body, html {
		height: 100%;
		min-height: 100%;
		font-family: 'Arial', sans-serif;
		color: #818181;
		font-size: 120%;
		margin: 20px;
	}

	h2 {
		font-size: 120%;
	}

	p, ul {
		text-align: left;
		margin: 15px;
		line-height: 140%;
		width: 90%;
	}

	.conf {
		font-size: 80%;
	}

	table {
		border: 1px solid black;
		table-layout: fixed;
		width: 80%
	}
	th, td {
		border: 1px solid black;
		overflow: hidden;
	}

	.cen {
		text-align: center;
	}

	pre {
		white-space:pre-wrap;
		overflow-y:scroll;
		max-width:400px;
		height:80px;
		border:0.5px solid lightgray; padding:5px;
	}

	a { text-decoration:  underline; color: inherit;}

	img {
		width: 96;
		height: 96;
		position: absolute;
		right:0;
		top:0;
	}
	div {
		position: absolute;
		left: 0;
		top: 20px;
		background-color: white;
		width: 90%;
	}
</style>
<link rel="icon" href="/owntracks/favicon.ico" type="image/x-icon">
</head>
<body>

<div id="logo">
	<img src="logo-owntracks-grayscale-96x96.jpg" alt="OwnTracks" />
</div>

<h2>My OwnTracks</h2>

<ul>
<li><a href="/owntracks/frontend/">Frontend</a></li>
<li><a href="/owntracks/table/">Device table</a></li>
<li><a href="/owntracks/last/">Live map</a></li>
<li style="margin-top: 50px;">
You (<code><?php echo htmlspecialchars($_SERVER['REMOTE_USER']); ?></code>)
have the following devices to configure:
<p class="conf">
To configure your Android or iOS OwnTracks app, open this page on the device
and either click on the URLconfig or download an <code>.otrc</code> file.
</p>

<table class="devicetable">
<tr>
<th>device</th>
<th class="cen">URLconfig</th>
<th>OTRC file</th>
</tr>

<tbody>
<?php
$user = $_SERVER['REMOTE_USER'];
$userdata = "/usr/local/owntracks/userdata/";

$otrcs = array_map('basename', glob($userdata . "{$user}-*.otrc"));
foreach ($otrcs as $f) {
    $path = "$userdata/$f";
    $json = @file_get_contents($path);
    if ($json === false) continue;

    $o = json_decode($json, false);
    if (!isset($o->deviceId)) continue;

    $d = $o->deviceId;
    $file = strtolower("otrc.php?d=$d");

    echo "<tr>";
    echo "<td>$d</td>";
    echo "<td class='cen'><a href='" . remoteconf($user, $d) . "'>click</a></td>";
    echo "<td><a href='$file'>$d</a></td>";
    echo "</tr>";
}
?>
</tbody>
</table>
</li>
</ul>

<?php
function remoteconf($username, $device) {
    $userdata = "/usr/local/owntracks/userdata";
    $fname = strtolower("{$userdata}/{$username}-{$device}.otrc");
    $data = file_get_contents($fname);
    $config = base64_encode($data);
    return "owntracks:///config?inline={$config}";
}
?>

</body>
</html>

