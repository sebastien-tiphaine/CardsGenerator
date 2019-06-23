<html>
<head>
	<title>{{Title}} - Card Front and Back</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	{{CSS}}
</head>
<body>
<div class="previewContainer">
	<div class="previewCardDatas">
		Title : {{Title}}<br>
		Name  : {{Name}}<br>
		Tags  : {{Tags}}
	</div>
</div>
<?php require(__DIR__.'/anki-back.php'); ?>
</body>
</html>
