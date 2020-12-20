<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$data.title}</title>
</head>
<body>
<h3>Hello {$data.name}.</h3>
<p>Your wallet on our platform was {$data.action} <b>{$data.currency} {$data.amount}</b>.</p>
<h3>Your total available balance:</h3>
<h2>{$data.currency} {$data.balance}</h2>
<hr/>
<p>Thanks for choosing {$data.app_name}</p>
</body>
</html>