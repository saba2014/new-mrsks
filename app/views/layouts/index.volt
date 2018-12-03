
{{ getDocType() }}
<html ng-app="myApp" class="ng-scope">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Navikey Portal">
        <meta name="author" content="Navikey team Alex">
        <link rel="icon" type="image/x-icon" href="img/favicon.ico">
        <link rel="stylesheet" href="css/usual/index.css">
        {{ get_title() }}
        {{ assets.outputCss("lib_css") }}
        {{ assets.outputCss() }}
        {{ assets.outputJs("lib_js") }}
    </head>

    {{ content() }}

</html>
